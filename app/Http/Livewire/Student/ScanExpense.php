<?php

namespace App\Http\Livewire\Student;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Receipt;
use App\Models\WeeklyBudget;
use App\Models\RiskLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\DatabaseNotification;
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
            'items.*.expense_category_id' => 'required|exists:expense_categories,id',
        ];
    }

    public function updatedReceiptImage()
    {
        $this->validate();
    }

    public function processReceipt()
    {
        $this->validate();
        $this->isProcessing = true;
        $currentYear = Carbon::today()->format('Y');

        try {
            // Filter out 'Savings' category for AI category extraction
            $dbCategories = ExpenseCategory::whereRaw('LOWER(name) != ?', ['savings'])
                ->pluck('name')
                ->toArray();

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
                    'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                ])->timeout(45)->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => env('GROQ_VISION_MODEL', 'qwen/qwen3.6-27b'),
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

            // Keep raw_ocr_text populated for reference/debugging even though OCR.space is no longer used
            $receipt->update(['raw_ocr_text' => json_encode($extracted)]);

            $this->merchant_name = $extracted['merchant_name'] ?? null;

            $aiDate = $extracted['transaction_date'] ?? null;
            $this->transaction_date = ($aiDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $aiDate))
                ? $aiDate
                : Carbon::today()->format('Y-m-d');

            $defaultCategoryId = ExpenseCategory::first()->id;

            $this->items = collect($extracted['items'])->map(function ($row) use ($defaultCategoryId) {
                $matched = ExpenseCategory::where('name', $row['category'] ?? '')->first();
                return [
                    'item_name' => $row['item_name'] ?? 'Item',
                    'amount' => number_format((float) ($row['amount'] ?? 0), 2, '.', ''),
                    'expense_category_id' => $matched ? $matched->id : $defaultCategoryId,
                ];
            })->toArray();

            $this->isProcessing = false;
            $this->step = 2;
        } catch (\Exception $e) {
            $this->isProcessing = false;
            session()->flash('error', $e->getMessage());
        }
    }

    public function addItem()
    {
        $this->items[] = [
            'item_name' => '',
            'amount' => '',
            'expense_category_id' => ExpenseCategory::first()->id ?? null,
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

        // NOTE: no longer blanket-deleting today's RiskLog rows or every
        // risk/low-allowance notification here — see LogExpense.php's
        // persistExpense() for the full rationale. RiskDetectionService
        // manages risk log + notification lifecycle on its own now.

        try {
            DB::transaction(function () use ($currentBudget, $total) {
                $formattedDateTime = $this->transaction_date . ' ' . Carbon::now()->format('H:i:s');
                $firstExpenseId = null;
            
                foreach ($this->items as $item) {
                    $expense = Expense::create([
                        'user_id'             => auth()->id(),
                        'expense_category_id' => $item['expense_category_id'],
                        'merchant_name'       => $this->merchant_name,
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
            });

            app(\App\Services\RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());

            $thresholdAmount = $currentBudget->total_allowance * 0.20;
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
                // Balance recovered above threshold — resolve any
                // still-open low allowance warnings from this cycle.
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

            session()->flash('success', count($this->items) . ' item(s) logged from your receipt!');
            return redirect()->route('student.dashboard');
        } catch (\Exception $e) {
            session()->flash('error', 'Verification Save Error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.student.scan-expense', [
            'availableCategories' => ExpenseCategory::whereRaw('LOWER(name) != ?', ['savings'])
                ->orderBy('name', 'asc')
                ->get(),
        ])->layout('layouts.student');
    }
}