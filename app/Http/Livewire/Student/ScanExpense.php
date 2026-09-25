<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Receipt;
use App\Models\RiskSetting;
use App\Models\WeeklyBudget;
use App\Services\CategorySuggester;
use App\Services\RiskDetectionService;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ScanExpense extends Component
{
    use WithFileUploads;

    public $step = 1;
    public $isProcessing = false;

    public $receiptImage;

    public $merchant_name;
    public $transaction_date;
    public $items = [];
    public $receiptId;

    public $usedBackupOcr = false;

    protected $rules = [
        'receiptImage' => 'required|image|max:4096',
    ];

    protected function verifyRules()
    {
        return [
            'merchant_name' => 'nullable|string|max:255',
            'transaction_date' => 'required|date|before_or_equal:today',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01|max:999999',
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

    /**
     * Fallback ("Other") category id, or the first selectable category if the admin
     * hasn't designated one. Null only when no selectable category exists at all.
     */
    private function defaultCategoryId()
    {
        return optional(ExpenseCategory::fallback())->id
            ?? optional(ExpenseCategory::selectable()->orderBy('name')->first())->id;
    }

    public function processReceipt()
    {
        if (!$this->receiptImage) {
            session()->flash('error', 'Please wait for the image to finish uploading before submitting.');
            return;
        }

        $this->validate();

        $this->isProcessing = true;
        $currentYear = Carbon::today()->format('Y');

        try {
            $settings = \App\Models\IntegrationSetting::current();
            $apiKey = $settings->groq_api_key ?: env('GROQ_API_KEY');

            // Enabled, non-Savings categories only (includes the fallback "Other").
            $dbCategories = ExpenseCategory::selectable()->pluck('name')->toArray();

            if (empty($dbCategories)) {
                throw new \Exception('Please seed your expense_categories table first.');
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

            $maxAttempts = 2;
            $extracted = null;
            $lastFailureReason = null;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
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

                if ($groqResponse->successful()) {
                    $groqData = $groqResponse->json();
                    $aiOutput = $groqData['choices'][0]['message']['content'] ?? '';
                    $cleanJson = trim(preg_replace('/^```json|```$/m', '', $aiOutput));
                    $extracted = json_decode($cleanJson, true);

                    \Illuminate\Support\Facades\Log::info('Groq Vision success', [
                        'attempt' => $attempt,
                        'raw_output' => $aiOutput,
                    ]);

                    if ($extracted) {
                        break; // success
                    }

                    $lastFailureReason = 'AI returned an unreadable response.';
                } else {
                    \Illuminate\Support\Facades\Log::error('Groq Vision error', [
                        'attempt' => $attempt,
                        'status' => $groqResponse->status(),
                        'body' => $groqResponse->body(),
                    ]);

                    $lastFailureReason = 'AI vision service returned an error (HTTP ' . $groqResponse->status() . ').';
                }

                if ($attempt < $maxAttempts) {
                    sleep(3);
                }
            }

            if (!$extracted || empty($extracted['items']) || !is_array($extracted['items'])) {
                $receipt->update(['status' => 'failed']);

                throw new \Exception(
                    $lastFailureReason ?? 'Could not identify any line items on this receipt. Try a clearer, well-lit photo.'
                );
            }

            // Keep raw_ocr_text populated for reference/debugging
            $receipt->update(['raw_ocr_text' => json_encode($extracted)]);

            $this->merchant_name = $extracted['merchant_name'] ?? null;

            $aiDate = $extracted['transaction_date'] ?? null;
            $this->transaction_date = ($aiDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $aiDate))
                ? $aiDate
                : Carbon::today()->format('Y-m-d');

            $suggester  = app(CategorySuggester::class);
            $fallbackId = $this->defaultCategoryId();

            $this->items = collect($extracted['items'])->map(function ($row) use ($suggester, $fallbackId) {
                $itemName = $row['item_name'] ?? 'Item';
                $aiPick   = ExpenseCategory::selectable()->where('name', $row['category'] ?? '')->first();

                // Trust Groq's pick when it names a specific category. If it returned
                // "Other" or nothing valid, let history/keywords try before falling back.
                if ($aiPick && !$aiPick->is_fallback) {
                    $categoryId = $aiPick->id;
                } else {
                    $suggested  = $suggester->suggest(auth()->user(), $itemName);
                    $categoryId = $suggested ? $suggested->id : ($aiPick->id ?? $fallbackId);
                }

                return [
                    'item_name' => $itemName,
                    'amount' => number_format((float) ($row['amount'] ?? 0), 2, '.', ''),
                    'expense_category_id' => $categoryId,
                ];
            })->toArray();

            $this->isProcessing = false;
            $this->step = 2;

            // IMPORTANT: the temp upload has now been persisted into
            // storage/app/public/receipts via ->store() above. Null the
            // property out so Step 1's Blade template never tries to call
            // temporaryUrl() against a tmp file that may since have expired
            // or been cleaned up — that call was the actual source of the
            // FileNotFoundException, thrown during view rendering rather
            // than inside this try/catch.
            $this->receiptImage = null;
        } catch (\Exception $e) {
            $this->isProcessing = false;
            $this->receiptImage = null;
            session()->flash('error', $e->getMessage());
        }
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

        $requestedIds = collect($this->items)->pluck('expense_category_id')->unique()->values();
        $allowedCount = ExpenseCategory::selectable()->whereIn('id', $requestedIds)->count();

        if ($allowedCount !== $requestedIds->count()) {
            $this->addError('items', 'One or more categories are no longer available. Please re-select them.');
            return;
        }

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
            $createdExpenses = [];

            DB::transaction(function () use ($currentBudget, $total, &$createdExpenses) {
                $formattedDateTime = $this->transaction_date . ' ' . Carbon::now()->format('H:i:s');
                $firstExpenseId    = null;

                foreach ($this->items as $item) {
                    $expense = Expense::create([
                        'user_id'             => auth()->id(),
                        'expense_category_id' => $item['expense_category_id'],
                        'item_name'           => $item['item_name'],
                        'amount'              => $item['amount'],
                        'transaction_date'    => $formattedDateTime,
                        'tracking_type'       => 'ocr',
                    ]);

                    $createdExpenses[] = $expense;

                    if ($firstExpenseId === null) {
                        $firstExpenseId = $expense->id;
                        $receiptModel   = Receipt::find($this->receiptId);

                        if ($receiptModel) {
                            $receiptModel->update([
                                'expense_id' => $expense->id,
                                'status'     => 'processed',
                            ]);
                        }
                    }
                }

                $currentBudget->decrement('remaining_allowance', $total);

                $itemNames = collect($this->items)->pluck('item_name')->take(5)->implode(', ');

                ActivityLog::create([
                    'user_id'    => auth()->id(),
                    'event_type' => 'expense_scanned',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'details'    => 'Scanned receipt' . ($this->merchant_name ? " from \"{$this->merchant_name}\"" : '') .
                                    ' — ' . count($this->items) . ' item(s), ₱' . number_format($total, 2) . " total: {$itemNames}" .
                                    (count($this->items) > 5 ? '...' : ''),
                ]);
            });

            $riskService = app(RiskDetectionService::class);
            $riskService->evaluateSpendingRisk(auth()->user()); // includes the low-allowance alert

            foreach ($createdExpenses as $created) {
                $riskService->checkLargeTransaction(auth()->user(), $created);
            }

            $riskService->resolveNoExpenseLogsAlert(auth()->user());

            session()->flash('success', count($this->items) . ' item(s) logged from your receipt!');

            return redirect()->route('student.dashboard');
        } catch (\Exception $e) {
            session()->flash('error', 'Verification Save Error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.student.scan-expense', [
            // Enabled, non-Savings categories; the fallback ("Other") is listed last.
            'availableCategories' => ExpenseCategory::selectable()
                ->orderBy('is_fallback')
                ->orderBy('name', 'asc')
                ->get(),
        ])->layout('layouts.student');
    }
}