<div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between">
            <a href="{{ route('student.dashboard') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-600 transition">
                ← Back to Dashboard
            </a>
            <span class="text-xs text-indigo-600 font-bold bg-indigo-50 px-3 py-1 rounded-full">
                Step {{ $step }} of 2: {{ $step === 1 ? 'Upload Scanning' : 'AI Verification' }}
            </span>
        </div>

        @if (session()->has('error'))
            <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-medium">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        @if($step === 1)
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-8">
                <div class="mb-6">
                    <h1 class="text-xl font-bold text-slate-900">AI Assisted Receipt Scanner</h1>
                    <p class="text-xs text-slate-500 mt-1">Upload a photo of your receipt. Our AI will auto-extract payment details.</p>
                </div>

                <form wire:submit.prevent="processReceipt" class="space-y-6">
                    <div class="relative border-2 border-dashed {{ $receiptImage ? 'border-indigo-500 bg-indigo-50/10' : 'border-slate-200 hover:border-indigo-500' }} rounded-2xl p-8 flex flex-col items-center justify-center text-center min-h-[220px] transition-all">
                        
                        <input type="file" id="receipt_upload" wire:model="receiptImage" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer {{ $isProcessing ? 'pointer-events-none' : '' }}">

                        @if ($receiptImage)
                            <div class="space-y-4 w-full max-w-xs z-10">
                                <img src="{{ $receiptImage->temporaryUrl() }}" class="rounded-xl max-h-48 mx-auto object-cover shadow-md border border-slate-200">
                                <button type="button" wire:click="$set('receiptImage', null)" class="text-[11px] font-bold text-rose-600 hover:underline">Remove Image</button>
                            </div>
                        @else
                            <div class="space-y-2">
                                <span class="text-4xl block animate-pulse">📷</span>
                                <span class="font-bold text-sm text-slate-800 block">Click to browse or drop receipt file</span>
                                <span class="text-[11px] text-slate-400 block">Supports JPEG or PNG (Max 1MB)</span>
                            </div>
                        @endif

                        <div wire:loading.flex wire:target="receiptImage" class="absolute inset-0 bg-white/95 rounded-2xl flex-col justify-center items-center backdrop-blur-sm z-20">
                            <div class="animate-spin rounded-full h-8 w-8 border-2 border-indigo-600 border-t-transparent mb-2"></div>
                            <span class="text-xs font-bold text-slate-700">Uploading image to framework...</span>
                        </div>
                    </div>

                    @error('receiptImage') <span class="text-xs text-rose-600 font-medium block mt-1">⚠️ {{ $message }}</span> @enderror

                    @if ($receiptImage)
                        <button type="submit" wire:loading.attr="disabled" class="w-full flex justify-center py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-semibold text-sm transition shadow-md">
                            <span wire:loading.remove wire:target="processReceipt">✨ Scan & Analyze with AI</span>
                            <span wire:loading.inline-flex wire:target="processReceipt" class="items-center gap-2">
                                <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                                AI is reading patterns...
                            </span>
                        </button>
                    @endif
                </form>
            </div>
        @endif

        @if($step === 2)
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-8 space-y-6">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Verify AI Extractions 🔎</h1>
                    <p class="text-xs text-slate-500 mt-1">Review and tweak details extracted by the AI before completing your entry.</p>
                </div>

                <form wire:submit.prevent="saveVerifiedExpense" class="space-y-5">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Store Name (Optional)</label>
                            <input type="text" wire:model.defer="merchant_name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-medium text-slate-800">
                            @error('merchant_name') <span class="text-xs text-rose-600 mt-1 block">⚠️ {{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Purchase Summary Description</label>
                            <input type="text" wire:model.defer="item_name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-medium text-slate-800">
                            @error('item_name') <span class="text-xs text-rose-600 mt-1 block">⚠️ {{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Transaction Date</label>
                            <input type="date" wire:model.defer="transaction_date" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-bold text-slate-900 bg-white cursor-pointer">
                            @error('transaction_date') <span class="text-xs text-rose-600 mt-1 block">⚠️ {{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Total Amount (₱)</label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-slate-500 text-sm font-bold">₱</span>
                                </div>
                                <input type="number" step="0.01" wire:model.defer="amount" class="w-full pl-8 pr-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-bold text-slate-900">
                            </div>
                            @error('amount') <span class="text-xs text-rose-600 mt-1 block">⚠️ {{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Expense Category</label>
                            <select wire:model.defer="expense_category_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-bold text-slate-800 bg-white">
                                <option value="">-- Select --</option>
                                @foreach($availableCategories as $cat)
                                    <option value="{{ $cat->id }}"> {{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('expense_category_id') <span class="text-xs text-rose-600 mt-1 block">⚠️ {{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="$set('step', 1)" class="w-1/3 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs transition uppercase tracking-wider">
                            ← Rescan
                        </button>
                        <button type="submit" class="w-2/3 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs transition shadow-md uppercase tracking-wider">
                            Confirm & Log Expense ✓
                        </button>
                    </div>
                </form>
            </div>
        @endif

    </div>
</div>