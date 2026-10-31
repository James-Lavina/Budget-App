<div>
  @if($showModal)
      <div class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm transform-gpu" wire:click.self="closeModal">
          <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl border border-slate-100 p-6 sm:p-7 space-y-6 max-h-[90vh] overflow-y-auto">

              <div class="flex items-start justify-between">
                  <div>
                      <h3 class="text-lg font-black text-slate-900 tracking-tight">Export My Expenses</h3>
                      <p class="text-xs text-slate-500 font-medium mt-1">Download your own transaction history. Only your data is included.</p>
                  </div>
                  <button wire:click="closeModal" class="text-slate-400 hover:text-slate-600 p-1 shrink-0">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="space-y-1.5">
                      <label class="block text-xs font-bold text-slate-700">Start Date</label>
                      <input type="date" wire:model="startDate"
                          class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.2)] focus:border-[var(--brand)] transition-all">
                      @error('startDate') <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span> @enderror
                  </div>
                  <div class="space-y-1.5">
                      <label class="block text-xs font-bold text-slate-700">End Date</label>
                      <input type="date" wire:model="endDate"
                          class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.2)] focus:border-[var(--brand)] transition-all">
                      @error('endDate') <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span> @enderror
                  </div>
              </div>

              <div class="space-y-1.5">
                  <label class="block text-xs font-bold text-slate-700">Category</label>
                  <select wire:model="selectedCategory"
                      class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.2)] focus:border-[var(--brand)] transition-all">
                      <option value="">All Categories</option>
                      @foreach($categories as $cat)
                          <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                      @endforeach
                  </select>
              </div>

              <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                  <div class="p-3.5 bg-slate-50 rounded-2xl">
                      <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Transactions</span>
                      <span class="text-lg font-black text-slate-900 font-mono block mt-0.5">{{ number_format($total_expenses) }}</span>
                  </div>
                  <div class="p-3.5 bg-slate-50 rounded-2xl">
                      <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Amount</span>
                      <span class="text-lg font-black text-slate-900 font-mono block mt-0.5">₱{{ number_format($total_amount, 2) }}</span>
                  </div>
              </div>

              <div class="pt-2 space-y-2">
                  <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Download as</span>
                  <div class="grid grid-cols-3 gap-2">
                      <button wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf"
                          class="flex flex-col items-center justify-center gap-1.5 py-3 rounded-2xl border border-slate-200 text-slate-700 hover:border-[var(--brand)] hover:bg-[rgba(var(--brand-rgb),0.06)] transition-all disabled:opacity-50">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                          <span class="text-[11px] font-bold">
                              <span wire:loading.remove wire:target="exportPdf">PDF</span>
                              <span wire:loading wire:target="exportPdf">...</span>
                          </span>
                      </button>
                      <button wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                          class="flex flex-col items-center justify-center gap-1.5 py-3 rounded-2xl border border-slate-200 text-slate-700 hover:border-[var(--brand)] hover:bg-[rgba(var(--brand-rgb),0.06)] transition-all disabled:opacity-50">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                          <span class="text-[11px] font-bold">
                              <span wire:loading.remove wire:target="exportExcel">Excel</span>
                              <span wire:loading wire:target="exportExcel">...</span>
                          </span>
                      </button>
                      <button wire:click="exportCsv" wire:loading.attr="disabled" wire:target="exportCsv"
                          class="flex flex-col items-center justify-center gap-1.5 py-3 rounded-2xl border border-slate-200 text-slate-700 hover:border-[var(--brand)] hover:bg-[rgba(var(--brand-rgb),0.06)] transition-all disabled:opacity-50">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                          <span class="text-[11px] font-bold">
                              <span wire:loading.remove wire:target="exportCsv">CSV</span>
                              <span wire:loading wire:target="exportCsv">...</span>
                          </span>
                      </button>
                  </div>
              </div>

          </div>
      </div>
  @endif
</div>