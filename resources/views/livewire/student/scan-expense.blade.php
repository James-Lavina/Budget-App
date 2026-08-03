<div class="min-h-screen py-10 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-2xl mx-auto space-y-6">

        <!-- Error Toast Notification -->
        @if (session()->has('error'))
            <div class="p-4 bg-rose-50 border border-rose-100 rounded-2xl text-rose-800 text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- STEP 1: UPLOAD RECEIPT -->
        @if($step === 1)
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Receipt Scanner</h1>
                        <p class="text-xs font-medium text-slate-500 mt-1">
                            Upload an image of your receipt to auto-extract transaction details.
                        </p>
                    </div>
                    <div>
                        <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-extrabold bg-indigo-50 text-indigo-600">
                            Step 1 of 2
                        </span>
                    </div>
                </div>

                <!-- Upload Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 space-y-6">
                    <form wire:submit.prevent="processReceipt" class="space-y-6">
                        
                        <!-- Drag and Drop Dropzone -->
                        <div class="relative border-2 border-dashed {{ $receiptImage ? 'border-indigo-500 bg-indigo-50/20' : 'border-slate-200 hover:border-indigo-400 bg-slate-50/50' }} rounded-3xl p-8 flex flex-col items-center justify-center text-center min-h-[220px] transition-all cursor-pointer">
                            <input type="file" id="receipt_upload" wire:model="receiptImage" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer {{ $isProcessing ? 'pointer-events-none' : '' }}">
                            
                            @if ($receiptImage)
                                <div class="space-y-3 w-full max-w-xs z-10">
                                    <img src="{{ $receiptImage->temporaryUrl() }}" class="rounded-2xl max-h-48 mx-auto object-cover shadow-sm border border-slate-200">
                                    <button type="button" wire:click="$set('receiptImage', null)" class="text-xs font-bold text-rose-600 hover:text-rose-700 transition-colors">
                                        Remove Image
                                    </button>
                                </div>
                            @else
                                <div class="space-y-3">
                                    <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center mx-auto text-indigo-600">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-800">Click to upload or drag & drop</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wide mt-0.5">JPEG / PNG (Max 4MB)</p>
                                    </div>
                                </div>
                            @endif

                            <!-- Loading Overlay -->
                            <div wire:loading.flex wire:target="receiptImage" class="absolute inset-0 bg-white/95 rounded-3xl flex-col justify-center items-center backdrop-blur-sm z-20">
                                <div class="animate-spin rounded-full h-8 w-8 border-2 border-indigo-600 border-t-transparent mb-2"></div>
                                <span class="text-xs font-extrabold text-slate-700">Preparing File...</span>
                            </div>
                        </div>

                        @error('receiptImage')
                            <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                        @enderror

                        <!-- Footer Actions -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <a href="{{ route('student.dashboard') }}"
                               class="px-5 py-2.5 rounded-full text-xs font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-all">
                               Cancel
                            </a>

                            @if ($receiptImage)
                                <button type="submit" wire:loading.attr="disabled"
                                    class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full font-bold text-xs shadow-lg shadow-indigo-200 transition-all transform active:scale-95 disabled:opacity-50 flex items-center gap-2">
                                    <span wire:loading.remove wire:target="processReceipt">Extract Receipt Data</span>
                                    <span wire:loading wire:target="processReceipt">Processing OCR...</span>
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- STEP 2: VERIFY EXTRACTED DATA -->
        @if($step === 2)
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Verify Extracted Data</h1>
                        <p class="text-xs font-medium text-slate-500 mt-1">
                            Review and adjust the extracted details before saving.
                        </p>
                    </div>
                    <div>
                        <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-extrabold bg-indigo-50 text-indigo-600">
                            Step 2 of 2
                        </span>
                    </div>
                </div>

                <!-- Single Expense Form Card -->
                <form wire:submit.prevent="saveVerifiedExpense" class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 space-y-6">
                    
                    <!-- Row 1: Store/Merchant & Item Name -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Store / Merchant -->
                        <div class="space-y-1.5">
                            <label for="merchant_name" class="block text-xs font-bold text-slate-700">
                                Store / Merchant <span class="font-normal text-slate-400">(optional)</span>
                            </label>
                            <input id="merchant_name" type="text" wire:model.defer="merchant_name" placeholder="e.g., Jollibee"
                                class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-semibold text-sm placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                            @error('merchant_name')
                                <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Item Name -->
                        <div class="space-y-1.5">
                            <label for="item_name" class="block text-xs font-bold text-slate-700">
                                Item Name
                            </label>
                            <input id="item_name" type="text" wire:model.defer="item_name" placeholder="e.g., Chickenjoy Meal"
                                class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-semibold text-sm placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                            @error('item_name')
                                <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Row 2: Amount & Date -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Amount -->
                        <div class="space-y-1.5">
                            <label for="amount" class="block text-xs font-bold text-slate-700">
                                Amount
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-800 font-extrabold text-base">
                                    ₱
                                </span>
                                <input id="amount" type="number" step="0.01" wire:model.defer="amount" placeholder="0.00"
                                    class="w-full pl-9 pr-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-extrabold text-base placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                            </div>
                            @error('amount')
                                <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Date -->
                        <div class="space-y-1.5">
                            <label for="transaction_date" class="block text-xs font-bold text-slate-700">
                                Date
                            </label>
                            <input id="transaction_date" type="date" wire:model.defer="transaction_date"
                                class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-800 font-semibold text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                            @error('transaction_date')
                                <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Row 3: Category (Dynamic Pills) -->
                    <div class="space-y-2 pt-1">
                        <label class="block text-xs font-bold text-slate-700">
                            Category
                        </label>
                        
                        <div class="flex flex-wrap gap-2 items-center">
                            @foreach($availableCategories as $cat)
                                @php
                                    $isSelected = $expense_category_id == $cat->id;
                                @endphp
                                <button type="button" 
                                    wire:click="$set('expense_category_id', {{ $cat->id }})"
                                    class="px-4 py-2 rounded-full text-xs font-bold flex items-center gap-2 transition-all duration-150 transform active:scale-95 {{ $isSelected ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200/80' }}">
                                    @if(!empty($cat->icon))
                                        <span>{{ $cat->icon }}</span>
                                    @endif
                                    <span>{{ $cat->name }}</span>
                                </button>
                            @endforeach
                        </div>
                        
                        @error('expense_category_id')
                            <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="flex items-center justify-between gap-3 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="$set('step', 1)"
                           class="px-5 py-2.5 rounded-full text-xs font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-all">
                           Back
                        </button>

                        <button type="submit" wire:loading.attr="disabled"
                            class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full font-bold text-xs shadow-lg shadow-indigo-200 transition-all transform active:scale-95 disabled:opacity-50 flex items-center gap-2">
                            <span wire:loading.remove wire:target="saveVerifiedExpense">Finalize Entry</span>
                            <span wire:loading wire:target="saveVerifiedExpense">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif

    </div>
</div>