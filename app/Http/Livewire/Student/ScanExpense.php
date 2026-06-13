<?php

namespace App\Http\Livewire\Student;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Receipt;
use App\Models\WeeklyBudget;
use Carbon\Carbon;
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
    public $item_name;
    public $amount;
    public $transaction_date;
    public $expense_category_id;
    public $receiptId;

    protected $rules = [
        'receiptImage' => 'required|image|max:4096',
    ]; 

    public function updatedReceiptImage() {
        $this->validate();
    }

    public function processReceipt() {
        $this->validate();
        $this->isProcessing = true;

        try {
            $dbCategories = ExpenseCategory::pluck('name')->toArray();
            if(empty($dbCategories)) {
                throw new \Exception('Please seed your expense_categories table first.');
            }
            $categoryListString = implode(', ', array_map(fn($cat) => "'$cat'", $dbCategories));

            $storedPath = $this->receiptImage->store('receipts', 'public');
            // $realPath = Storage::disk('public')->readStream($storedPath);

            $receipt = Receipt::create([
                'user_id' => auth()->id(),
                'image_path' => $storedPath,
                'status' => 'pending',
            ]);

            $this->receiptId = $receipt->id;

            $ocrResponse = Http::asMultipart()
                ->post('https://api.ocr.space/parse/image', [
                    'apiKey' => env('OCR_SPACE_API_KEY'),
                    'language' => 'eng',
                    'file' => Storage::disk('public')->readStream($storedPath),
                ]);

            $ocrData = $ocrResponse->json();
            $rawText = $ocrData['ParsedResults'][0]['ParsedText'] ?? '';
            $receipt->update(['raw_ocr_text' => $rawText]);

            if(empty(trim($rawText))) {
                $receipt->update(['status' => 'failed']);
                throw new \Exception('No text detected on the receipt image');
            }

            $systemInstruction = "You are a precise data extraction engine for a student budget tracker application.\n"
                    . "Your job is to parse raw OCR text from a receipt and output a strict JSON object.\n\n"
                    . "CRITICAL EXTRACTION RULES:\n"
                    . "1. Output Format: Respond ONLY with the raw JSON object. Do not wrap it in markdown blocks (```json), do not use backticks, and do not include any conversational text.\n"
                    . "2. Merchant Name: Look closely at the first 5-10 lines of the text where business names usually reside. Extract a clean, recognizable brand or store name. If the OCR text is mangled, poorly spaced, or slightly misspelled due to stylized logos (e.g., '7-E1EVEИ' or 'WArMART'), intelligently reconstruct it to the correct standard brand name (e.g., '7-Eleven' or 'Walmart'). Strip out branch codes or phone numbers, but prioritize returning the actual business identity. Do not leave it empty if a business identity can be reasonably inferred.\n"
                    . "3. Item Name Summary: If there are multiple items on the receipt, synthesize a concise summary or comma-separated list of the primary items purchased (e.g., 'Notebooks & Pens' or 'Lunch Meal'). Do not just pick one random item line.\n"
                    . "4. Amount Verification: Look for the final Grand Total or Net Amount paid after discounts and taxes. Never extract subtotals, cash tendered, or change amounts. Return as a plain floating-point number.\n"
                    . "5. Date Normalization: Convert the purchase date to 'YYYY-MM-DD' format. If the year is missing but month/day are visible, use the current year (2026). If absolutely no date is found, return null.\n"
                    . "6. Strict Category Matching: The 'category' key MUST exactly match one of the string items provided in the user's permitted list. Choose the single best match. Do not create new categories.";

            $userContext = "Permitted Categories (Choose EXACTLY one):\n"
            . "[ " . $categoryListString . " ]\n\n"
            . "Target JSON Schema:\n"
            . "{\n"
            . "  \"merchant_name\": \"String or null\",\n"
            . "  \"item_name\": \"String\",\n"
            . "  \"amount\": Float,\n"
            . "  \"transaction_date\": \"String (YYYY-MM-DD) or null\",\n"
            . "  \"category\": \"String\"\n"
            . "}\n\n"
            . "Raw Receipt Text to Parse:\n" . $rawText;

            $groqResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('GROQ_API_KEY'), 
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.1-8b-instant',
                'messages' => [
                    ['role' => 'system', 'content' => $systemInstruction],
                    ['role' => 'user', 'content' => $userContext],
                ],
                'temperature' => 0.0,
            ]);

            $groqData = $groqResponse->json();
            $aiOutput = $groqData['choices'][0]['message']['content'] ?? '';
            $cleanJson = trim(preg_replace('/^```json|```$/m', '', $aiOutput));
            $extracted = json_decode($cleanJson, true);

            if(!$extracted || !isset($extracted['amount'])) {
                $receipt->update(['status' => 'failed']);
                throw new \Exception('AI data extraction validation structural failure');
            }

            $aiCategoryName = $extracted['category'] ?? '';
            $matchedCategory = ExpenseCategory::where('name', $aiCategoryName)->first();
            $this->expense_category_id = $matchedCategory ? $matchedCategory->id : ExpenseCategory::first()->id;

            $this->merchant_name = $extracted['merchant_name'] ?? null;
            $this->item_name = $extracted['item_name'] ?? '';
            $this->amount = (float) $extracted['amount'];

            $aiDate = $extracted['transaction_date'] ?? null;
            if($aiDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $aiDate)) {
                $this->transaction_date = $aiDate;
            } else {
                $this->transaction_date = Carbon::today()->format('M-d-y');
            }

            $this->isProcessing = false;
            $this->step = 2;

        } catch (\Exception $e) {
            $this->isProcessing = false;
            session()->flash('error', $e->getMessage());
        }
    }

    public function saveVerifiedExpense() {
        $this->validate([
            'merchant_name' => 'nullable|string|max:255', 
            'item_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date|before_or_equal:today',
            'expense_category_id' => 'required|exists:expense_categories,id', 
        ]);

        try{
            $expense = Expense::create([
                'user_id' => auth()->id(),
                'expense_category_id' => $this->expense_category_id,
                'merchant_name' => $this->merchant_name,
                'item_name' => $this->item_name,
                'amount' => $this->amount,
                'transaction_date' => $this->transaction_date,
                'tracking_type' => 'ocr',
            ]);

            $receipt = Receipt::find($this->receiptId);
            if($receipt) {
                $receipt->update([
                    'expense_id' => $expense->id,
                    'status' => 'processed',
                ]);
            }

            $currentBudget = WeeklyBudget::where('user_id', auth()->id())->latest()->first();
            if($currentBudget) {
                $currentBudget->decrement('remaining_allowance', $this->amount);
            }

            app(\App\Services\RiskDetectionService::class)->evaluateSpendingRisk(auth()->user());

            session()->flash('success', 'Transaction processed and added to active expense ledger tracking!');
            return redirect()->route('student.dashboard');

        } catch (\Exception $e) {
            session()->flash('error', 'Verification Save Error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.student.scan-expense', [
            'availableCategories' => ExpenseCategory::all(),
        ]);
    }
}
