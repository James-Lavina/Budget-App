<div class="min-h-screen bg-slate-50/50 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">

        <!-- Context Header with Integrated Breadcrumb -->
        <div class="mb-8">
            <nav class="flex items-center gap-1.5 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3">
                <a href="{{ route('student.dashboard') }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
                <svg class="w-2.5 h-2.5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                </svg>
                <span class="text-slate-500">
                    {{ $step === 1 ? 'AI-Assisted Extraction' : 'Data Verification' }}
                </span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">AI-Assisted Receipt Scanner</h1>
        </div>

        @if (session()->has('error'))
            <div class="mb-6 p-4 bg-rose-50 border border-rose-100 rounded-xl text-rose-800 text-[11px] font-bold flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- Step 1: Uploading -->
        @if($step === 1)
            <div class="bg-white rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-slate-200/80 p-8 space-y-6">
                <p class="text-xs text-slate-500 leading-relaxed">
                    Upload an image of your receipt. Our AI-assisted tool will help extract the transaction details to save you time on manual entry.
                </p>

                <form wire:submit.prevent="processReceipt" class="space-y-6">
                    <div class="relative border-2 border-dashed {{ $receiptImage ? 'border-indigo-500 bg-indigo-50/10' : 'border-slate-200 hover:border-indigo-500' }} rounded-2xl p-8 flex flex-col items-center justify-center text-center min-h-[220px] transition-all">
                        
                        <input type="file" id="receipt_upload" wire:model="receiptImage" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer {{ $isProcessing ? 'pointer-events-none' : '' }}">

                        @if ($receiptImage)
                            <div class="space-y-4 w-full max-w-xs z-10">
                                <img src="{{ $receiptImage->temporaryUrl() }}" class="rounded-xl max-h-48 mx-auto object-cover shadow-sm border border-slate-200">
                                <button type="button" wire:click="$set('receiptImage', null)" class="text-[11px] font-extrabold text-rose-600 hover:underline">Remove Image</button>
                            </div>
                        @else
                            <div class="space-y-3">
                                <svg class="w-10 h-10 text-slate-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <span class="font-extrabold text-xs text-slate-800 block">Click to upload or drag & drop</span>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wide block">JPEG/PNG (Max 1MB)</span>
                            </div>
                        @endif

                        <div wire:loading.flex wire:target="receiptImage" class="absolute inset-0 bg-white/95 rounded-2xl flex-col justify-center items-center backdrop-blur-sm z-20">
                            <div class="animate-spin rounded-full h-8 w-8 border-2 border-indigo-600 border-t-transparent mb-2"></div>
                            <span class="text-[11px] font-extrabold text-slate-700 tracking-wider">Preparing File...</span>
                        </div>
                    </div>

                    @error('receiptImage') 
                        <div class="text-[11px] font-bold text-rose-700 flex items-center gap-1.5">
                            <span class="h-1 w-1 rounded-full bg-rose-500"></span> {{ $message }}
                        </div>
                    @enderror

                    <!-- Updated Submit Button for Step 1 -->
                    @if ($receiptImage)
                    <button type="submit" wire:loading.attr="disabled" class="w-full flex items-center justify-center py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs shadow-sm transition-all disabled:opacity-50">
    
                        <!-- Ready State: Wrap in a flex span for consistency -->
                        <span wire:loading.remove wire:target="processReceipt" class="flex items-center">
                            Extract Receipt Data
                        </span>
                    
                        <!-- Loading State: Explicitly centered flex container -->
                        <span wire:loading wire:target="processReceipt" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                    @endif
                </form>
            </div>
        @endif

        <!-- Step 2: Verification -->
        @if($step === 2)
            <div class="bg-white rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-slate-200/80 p-8 space-y-6">
                <div>
                    <h2 class="text-lg font-black text-slate-900 tracking-tight">Verify Extracted Data</h2>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Please review the details below, which were assisted by our extraction tool, before finalizing your entry.</p>
                </div>

                <form wire:submit.prevent="saveVerifiedExpense" class="space-y-5">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">Merchant</label>
                            <input type="text" wire:model.defer="merchant_name" class="block w-full px-4 py-3 border border-slate-200 bg-slate-50/30 text-sm font-semibold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">Description</label>
                            <input type="text" wire:model.defer="item_name" class="block w-full px-4 py-3 border border-slate-200 bg-slate-50/30 text-sm font-semibold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">Date</label>
                            <input type="date" wire:model.defer="transaction_date" class="block w-full px-4 py-3 border border-slate-200 bg-slate-50/30 text-sm font-semibold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">Amount (₱)</label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-slate-400 font-bold text-sm">₱</span>
                                </div>
                                <input type="number" step="0.01" wire:model.defer="amount" class="block w-full pl-9 pr-4 py-3 border border-slate-200 bg-slate-50/30 text-sm font-bold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">Category</label>
                            <select wire:model.defer="expense_category_id" class="block w-full px-4 py-3 border border-slate-200 bg-slate-50/30 text-sm font-semibold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                                <option value="">Select...</option>
                                @foreach($availableCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 pt-4">
                        <button type="button" wire:click="$set('step', 1)" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                            Back
                        </button>
                        <button type="submit" class="flex-1 px-5 py-2.5 rounded-xl shadow-sm text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-all">
                            Finalize Entry
                        </button>
                    </div>
                </form>
            </div>
        @endif

    </div>
</div>