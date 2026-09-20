<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Receipt;
use App\Models\RiskLog;
use App\Models\RiskSetting;
use App\Models\WeeklyBudget;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ScanExpense extends Component
{
    use WithFileUploads;

    public $step = 1;
    public $isProcessing = false;
    public $receiptImage;
    public $merchantName; // kept in-memory only, for the ActivityLog summary string
    public $transaction_date;
    public $items = [];
    public $receiptId;
    public $usedBackupOcr = false; // true when OCR.space produced the result instead of Groq

    protected $rules = [
        'receiptImage' => 'required|image|max:4096',
    ];

    protected function verifyRules()
    {
        return [
            'transaction_date' => 'required|date|before_or_equal:today',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01|max:999999',
            // Rejects disabled categories server-side, even if the id is tampered with.
            'items.*.expense_category_id' => 'required|exists:expense_categories,id,status,enabled',
        ];
    }

    public function updatedReceiptImage()
    {
        $this->validate();
    }

    /**
     * Called from the Blade "Remove Image" / "Re-select image" buttons.
     * Centralizes clearing the upload so the temp file reference is never
     * left dangling on the component between renders.
     */
    public function clearReceiptImage()
    {
        $this->receiptImage = null;
        $this->resetErrorBag('receiptImage');
    }

    public function processReceipt()
    {
        if (!$this->receiptImage) {
            session()->flash('error', 'Please wait for the image to finish uploading before submitting.');
            return;
        }

        $this->validate();
        $this->isProcessing = true;
        $this->usedBackupOcr = false;

        $currentYear = Carbon::today()->format('Y');

        try {
            $settings = \App\Models\IntegrationSetting::current();
            $apiKey = $settings->groq_api_key ?: env('GROQ_API_KEY');

            // Only enabled, non-Savings categories are offered to the AI.
            $dbCategories = ExpenseCategory::selectable()
                ->pluck('name')
                ->toArray();

            if (empty($dbCategories)) {
                throw new \Exception('No enabled expense categories are available. Please contact an administrator.');
            }

            $categoryListString = implode(', ', array_map(fn ($cat) => "'$cat'", $dbCategories));

            $storedPath = $this->receiptImage->store('receipts', 'public');
            $receipt = Receipt::create([
                'user_id' => auth()->id(),
                'image_path' => $storedPath,
                'status' => 'pending',
            ]);
            $this->receiptId = $receipt->id;

            // Encode the image as base64 for the vision model
            $imageBinary = Storage::disk('public')->get($storedPath);
            $mimeType = Storage::disk('public')->mimeType($storedPath) ?? 'image/jpeg';
            $base64Image = base64_encode($imageBinary);
            $dataUri = "data:{$mimeType};base64,{$base64Image}";

            $systemInstruction = "You are a precise data extraction engine for a student budget tracker application.\n"
                . "You will be shown a photo of a receipt. Read it directly and output a strict JSON object.\n\n"
                . "CRITICAL EXTRACTION RULES:\n"
                . "1. Output Format: Respond ONLY with the raw JSON object. No markdown, no backticks, no conversational text.\n"
                . "2. Merchant Name: reconstruct a clean brand name even if the logo/print is stylized or partly unclear.\n"
                . "3. Items: List EVERY distinct purchased line item separately. Do not merge them into one summary string. "
                . "Ignore subtotal/tax/discount/change lines — those are not items.\n"
                . "4. Amount per item: the price actually paid for that line (after any per-line discount).\n"
                . "5. Date Normalization: 'YYYY-MM-DD'. If year is missing, use {$currentYear}. If no date is visible, return null.\n"
                . "6. Strict Category Matching: each item's 'category' MUST exactly match one entry from the permitted list.\n"
                . "7. If the receipt truly has only one purchasable item, return an 'items' array with a single entry.\n"
                . "8. If the image is not a legible receipt at all, return an empty 'items' array.";

            $userText = "Permitted Categories (choose EXACTLY one per item):\n[ {$categoryListString} ]\n\n"
                . "Target JSON Schema:\n"
                . "{\n"
                . "  \"merchant_name\": \"String or null\",\n"
                . "  \"transaction_date\": \"String (YYYY-MM-DD) or null\",\n"
                . "  \"items\": [\n"
                . "    { \"item_name\": \"String\", \"amount\": Float, \"category\": \"String\" }\n"
                . "  ]\n"
                . "}\n\nRead the attached receipt image and extract the data.";

            // ---------------------------------------------------------
            // PRIMARY: Groq Vision
            // ---------------------------------------------------------
            $maxAttempts = 2;
            $extracted = null;
            $lastFailureReason = null;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $groqResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                    ])->timeout(45)->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => $settings->groq_vision_model,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemInstruction],
                            [
                                'role' => 'user',
                                'content' => [
                                    ['type' => 'text', 'text' => $userText],
                                    ['type' => 'image_url', 'image_url' => ['url' => $dataUri]],
                                ],
                            ],
                        ],
                        'temperature' => 0.0,
                        'response_format' => ['type' => 'json_object'],
                    ]);
                } catch (\Throwable $e) {
                    // Timeout / connection failure: skip straight to the backup.
                    Log::error('Groq Vision request failed', ['attempt' => $attempt, 'error' => $e->getMessage()]);
                    $lastFailureReason = 'AI vision service could not be reached.';
                    break;
                }

                if ($groqResponse->successful()) {
                    $groqData = $groqResponse->json();
                    $aiOutput = $groqData['choices'][0]['message']['content'] ?? '';
                    $cleanJson = trim(preg_replace('/^```json|```$/m', '', $aiOutput));
                    $extracted = json_decode($cleanJson, true);

                    Log::info('Groq Vision success', [
                        'attempt' => $attempt,
                        'raw_output' => $aiOutput,
                    ]);

                    if ($extracted) {
                        break; // success
                    }
                    $lastFailureReason = 'AI returned an unreadable response.';
                } else {
                    Log::error('Groq Vision error', [
                        'attempt' => $attempt,
                        'status' => $groqResponse->status(),
                        'body' => $groqResponse->body(),
                    ]);
                    $lastFailureReason = 'AI vision service returned an error (HTTP ' . $groqResponse->status() . ').';

                    // Rate limit / auth problems won't fix themselves on a retry:
                    // go to the backup immediately.
                    if (in_array($groqResponse->status(), [401, 403, 429], true)) {
                        break;
                    }
                }

                if ($attempt < $maxAttempts) {
                    sleep(3);
                }
            }

            // ---------------------------------------------------------
            // BACKUP: OCR.space (runs only if Groq produced no usable items)
            // ---------------------------------------------------------
            if (!$this->hasItems($extracted)) {
                $backup = $this->extractWithOcrSpace($imageBinary, $mimeType);

                if ($this->hasItems($backup)) {
                    $extracted = $backup;
                    $this->usedBackupOcr = true;
                }
            }

            if (!$this->hasItems($extracted)) {
                $receipt->update(['status' => 'failed']);
                throw new \Exception(
                    $lastFailureReason
                        ? $lastFailureReason . ' The backup reader could not find any items either. Try a clearer, well-lit photo or add the expense manually.'
                        : 'Could not identify any line items on this receipt. Try a clearer, well-lit photo.'
                );
            }

            $receipt->update([
                'raw_ocr_text' => json_encode(
                    $this->usedBackupOcr
                        ? ['source' => 'ocr.space'] + $extracted
                        : $extracted
                ),
            ]);

            // Merchant name is held in-memory for the ActivityLog summary line only.
            $this->merchantName = $extracted['merchant_name'] ?? null;

            $aiDate = $extracted['transaction_date'] ?? null;
            $this->transaction_date = ($aiDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $aiDate))
                ? $aiDate
                : Carbon::today()->format('Y-m-d');

            $defaultCategoryId = $this->defaultCategoryId();

            $this->items = collect($extracted['items'])->map(function ($row) use ($defaultCategoryId) {
                // selectable(): an AI-returned name that matches a disabled or
                // Savings category falls back to the default instead.
                $matched = ExpenseCategory::selectable()
                    ->where('name', $row['category'] ?? '')
                    ->first();

                return [
                    'item_name' => $row['item_name'] ?? 'Item',
                    'amount' => number_format((float) ($row['amount'] ?? 0), 2, '.', ''),
                    'expense_category_id' => $matched ? $matched->id : $defaultCategoryId,
                ];
            })->toArray();

            $this->isProcessing = false;
            $this->step = 2;

            // The temp upload has been persisted via ->store() above. Null it so
            // Step 1's Blade never calls temporaryUrl() against an expired tmp file.
            $this->receiptImage = null;
        } catch (\Exception $e) {
            $this->isProcessing = false;
            $this->receiptImage = null;
            session()->flash('error', $e->getMessage());
        }
    }

    private function hasItems($extracted): bool
    {
        return is_array($extracted)
            && !empty($extracted['items'])
            && is_array($extracted['items']);
    }

    private function defaultCategoryId()
    {
        return ExpenseCategory::selectable()
            ->orderBy('name', 'asc')
            ->value('id');
    }

    /**
     * Backup reader. OCR.space only returns plain text, so merchant / date /
     * line items are parsed locally with regex. Returns the same shape the
     * Groq path produces (plus 'raw_text'), or null on any failure.
     */
    private function extractWithOcrSpace(string $imageBinary, string $mimeType): ?array
    {
        $key = config('services.ocr_space.key');

        if (empty($key)) {
            Log::warning('OCR.space backup skipped: OCR_SPACE_API_KEY is not set.');
            return null;
        }

        try {
            $extension = $mimeType === 'image/png' ? 'png' : 'jpg';

            $response = Http::timeout(30)
                ->attach('file', $imageBinary, 'receipt.' . $extension)
                ->post('https://api.ocr.space/parse/image', [
                    'apikey'            => $key,
                    'language'          => 'eng',
                    'OCREngine'         => 2,
                    'scale'             => 'true',
                    'isTable'           => 'true',
                    'isOverlayRequired' => 'false',
                ]);

            if (!$response->successful()) {
                Log::error('OCR.space HTTP error', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $json = $response->json();

            if (($json['IsErroredOnProcessing'] ?? true) || empty($json['ParsedResults'][0]['ParsedText'])) {
                Log::error('OCR.space processing error', ['response' => $json]);
                return null;
            }

            $text = $json['ParsedResults'][0]['ParsedText'];
            $parsed = $this->parseOcrText($text);
            $parsed['raw_text'] = $text;

            Log::info('OCR.space backup used', ['item_count' => count($parsed['items'])]);

            return $parsed;
        } catch (\Throwable $e) {
            Log::error('OCR.space request failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Heuristic receipt parser for raw OCR text.
     * Item line = "<name> ... <price>" where price has a dot and 2 decimals.
     * Totals, tax, change, payment and contact lines are skipped.
     */
    private function parseOcrText(string $text): array
    {
        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $text)),
            fn ($l) => $l !== ''
        ));

        $skip = '/\b(sub\s*-?\s*total|total|tax|vat|vatable|vat[\s-]?exempt|zero[\s-]?rated|change|cash|tenders?|tendered|amount\s*due|balance|discount|payment|credit|debit|card|gcash|maya|tin|tel|invoice|ref|thank)\b/i';
        $priceRegex = '/^(.*?)[\s:.\-_]*(?:PHP|P|₱)?\s*((?:\d{1,3}(?:,\d{3})+|\d+))\.(\d{2})\s*[A-Za-z]?$/u';

        $items = [];
        $merchant = null;

        foreach ($lines as $i => $line) {
            $isPriceLine = preg_match($priceRegex, $line, $m);

            // Merchant: first plain-text line near the top.
            if ($merchant === null && $i < 6 && !$isPriceLine && !preg_match($skip, $line)
                && preg_match('/[A-Za-z]{3,}/', $line)) {
                $merchant = mb_substr($line, 0, 60);
            }

            if (!$isPriceLine || preg_match($skip, $line)) {
                continue;
            }

            $name = trim($m[1]);
            $amount = (float) (str_replace(',', '', $m[2]) . '.' . $m[3]);

            // "Coke 2 x 25.00" → "Coke", and "2 x Coke" → "Coke"
            $name = preg_replace('/\s+\d+\s*[xX@]\s*[\d.,]+$/', '', $name);
            $name = preg_replace('/^\d+\s*[xX]\s+/', '', $name);
            $name = trim($name, " \t-:._");

            if ($amount <= 0 || $amount >= 999999 || !preg_match('/[A-Za-z]{2,}/', $name)) {
                continue;
            }

            $items[] = [
                'item_name' => mb_substr($name, 0, 255),
                'amount'    => $amount,
                'category'  => null, // falls back to the default category in processReceipt()
            ];
        }

        return [
            'merchant_name'    => $merchant,
            'transaction_date' => $this->findDateInText($text),
            'items'            => $items,
        ];
    }

    /**
     * Finds the first plausible, non-future date in OCR text.
     * Supports YYYY-MM-DD and MM/DD/YYYY (Philippine receipt convention);
     * a first part above 12 is treated as day (DD/MM/YYYY).
     */
    private function findDateInText(string $text): ?string
    {
        $today = Carbon::today();

        if (preg_match_all('/\b(\d{4})-(\d{2})-(\d{2})\b/', $text, $isoMatches, PREG_SET_ORDER)) {
            foreach ($isoMatches as $d) {
                if (checkdate((int) $d[2], (int) $d[3], (int) $d[1])) {
                    $date = Carbon::create((int) $d[1], (int) $d[2], (int) $d[3]);
                    if ($date->lte($today)) {
                        return $date->format('Y-m-d');
                    }
                }
            }
        }

        if (preg_match_all('/\b(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})\b/', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $d) {
                $a = (int) $d[1];
                $b = (int) $d[2];
                $year = (int) $d[3];
                if ($year < 100) {
                    $year += 2000;
                }

                [$month, $day] = $a > 12 ? [$b, $a] : [$a, $b];

                if (checkdate($month, $day, $year)) {
                    $date = Carbon::create($year, $month, $day);
                    if ($date->lte($today)) {
                        return $date->format('Y-m-d');
                    }
                }
            }
        }

        return null;
    }

    public function addItem()
    {
        $this->items[] = [
            'item_name' => '',
            'amount' => '',
            'expense_category_id' => $this->defaultCategoryId(),
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function getReceiptTotalProperty()
    {
        return collect($this->items)->sum(fn ($i) => (float) ($i['amount'] ?? 0));
    }

    public function saveVerifiedExpense()
    {
        $this->validate($this->verifyRules());

        $currentBudget = WeeklyBudget::where('user_id', auth()->id())->latest()->first();
        if (!$currentBudget) {
            session()->flash('error', 'No active budget found. Set up your allowance first.');
            return redirect()->route('student.budget-setup');
        }

        $total = collect($this->items)->sum(fn ($i) => (float) $i['amount']);
        if ($total > $currentBudget->remaining_allowance) {
            $this->addError('items', 'Insufficient allowance. Total ₱' . number_format($total, 2) .
                ' exceeds your remaining ₱' . number_format($currentBudget->remaining_allowance, 2) . '.');
            return;
        }

        try {
            DB::transaction(function () use ($currentBudget, $total) {
                $formattedDateTime = $this->transaction_date . ' ' . Carbon::now()->format('H:i:s');
                $firstExpenseId = null;

                foreach ($this->items as $item) {
                    $expense = Expense::create([
                        'user_id'             => auth()->id(),
                        'expense_category_id' => $item['expense_category_id'],
                        'item_name'           => $item['item_name'],
                        'amount'              => $item['amount'],
                        'transaction_date'    => $formattedDateTime,
                        'tracking_type'       => 'ocr',
                    ]);

                    // attach the receipt only to the first created expense
                    if ($firstExpenseId === null) {
                        $firstExpenseId = $expense->id;
                        $receiptModel = Receipt::find($this->receiptId);
                        if ($receiptModel) {
                            $receiptModel->update([
                                'expense_id' => $expense->id,
                                'status'     => 'processed',
                            ]);
                        }
                    }
                }

                $currentBudget->decrement('remaining_allowance', $total);

                // One summarized log per receipt scan, same pattern as
                // AllExpenses::bulkDelete()'s expense_bulk_deleted.
                $itemNames = collect($this->items)->pluck('item_name')->take(5)->implode(', ');
                ActivityLog::create([
                    'user_id'    => auth()->id(),
                    'event_type' => 'expense_scanned',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'details'    => "Scanned receipt" . ($this->merchantName ? " from \"{$this->merchantName}\"" : '') .
                                    ($this->usedBackupOcr ? ' (backup OCR)' : '') .
                                    " — " . count($this->items) . " item(s), ₱" . number_format($total, 2) . " total: {$itemNames}" .
                                    (count($this->items) > 5 ? '...' : ''),
                ]);
            });

            app(\App\Services\RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());
            app(\App\Services\RiskDetectionService::class)->resolveNoExpenseLogsAlert(auth()->user());

            // Low Remaining Budget Alert — threshold driven by the admin's Risk Detection Rules.
            $riskSettings = RiskSetting::current();
            if ($riskSettings->low_remaining_budget_enabled) {
                $thresholdAmount = $currentBudget->total_allowance * ($riskSettings->low_remaining_budget_threshold / 100);
                if ($currentBudget->remaining_allowance <= $thresholdAmount) {
                    $alreadyNotified = DatabaseNotification::where('notifiable_id', auth()->id())
                        ->where('notifiable_type', 'App\Models\User')
                        ->where('data', 'LIKE', '%"anomaly_type":"low_allowance_threshold"%')
                        ->where('data', 'LIKE', '%"resolved":false%')
                        ->where('created_at', '>=', $currentBudget->created_at)
                        ->exists();

                    if (!$alreadyNotified) {
                        $percentageLeft = round(($currentBudget->remaining_allowance / $currentBudget->total_allowance) * 100);
                        auth()->user()->notify(new \App\Notifications\LowAllowanceWarning(
                            $percentageLeft,
                            $currentBudget->remaining_allowance
                        ));
                    }
                } else {
                    // Balance recovered above threshold — resolve still-open warnings from this cycle.
                    DatabaseNotification::where('notifiable_id', auth()->id())
                        ->where('notifiable_type', 'App\Models\User')
                        ->where('data', 'LIKE', '%"anomaly_type":"low_allowance_threshold"%')
                        ->where('data', 'LIKE', '%"resolved":false%')
                        ->where('created_at', '>=', $currentBudget->created_at)
                        ->get()
                        ->each(function ($notification) {
                            $data = $notification->data;
                            $data['resolved'] = true;
                            $notification->update(['data' => $data]);
                        });
                }
            }

            session()->flash('success', count($this->items) . ' item(s) logged from your receipt!');
            return redirect()->route('student.dashboard');
        } catch (\Exception $e) {
            session()->flash('error', 'Verification Save Error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.student.scan-expense', [
            'availableCategories' => ExpenseCategory::selectable()
                ->orderBy('name', 'asc')
                ->get(),
        ])->layout('layouts.student');
    }
}