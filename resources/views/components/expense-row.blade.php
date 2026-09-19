@props(['expense', 'locked' => false])
@php
    $isSavings = !is_null($expense->savings_goal_id);
@endphp
<div class="py-3.5 sm:py-4 flex items-center justify-between gap-3 group hover:bg-slate-50/70 -mx-2 px-2 rounded-2xl transition-all {{ $locked ? 'opacity-70' : '' }}">
    <div class="flex items-center gap-3.5 min-w-0">
        <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-2xl {{ $expense->icon_bg_color }} flex items-center justify-center shrink-0 shadow-xs">
            <x-category-icon :type="$expense->icon_type" class="w-5 h-5" />
        </div>
        <div class="min-w-0">
            <h4 class="font-bold text-slate-900 text-xs sm:text-sm truncate">
                {{ $expense->item_name }}
            </h4>
            <p class="text-[11px] sm:text-xs text-slate-400 font-medium truncate mt-0.5 flex items-center gap-1.5 flex-wrap">
                <span>{{ ucfirst($expense->category->name ?? 'General') }} · {{ $expense->formatted_date }}</span>
                @if($isSavings)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)] text-[9px] font-bold uppercase tracking-wide shrink-0">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Saved
                    </span>
                @endif
                @if($locked)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-400 text-[9px] font-bold uppercase tracking-wide shrink-0">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                        Previous cycle
                    </span>
                @endif
            </p>
        </div>
    </div>
    <div class="flex items-center gap-2 sm:gap-3 shrink-0">
        <span class="font-bold text-xs sm:text-sm font-mono tracking-tight {{ $isSavings ? 'text-[var(--brand)]' : 'text-slate-900' }}">
            {{ $isSavings ? '' : '-' }}₱{{ number_format($expense->amount, 2) }}
        </span>
        <div class="flex items-center gap-0.5 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
            @if($locked)
                <span class="p-1.5 text-slate-300 cursor-not-allowed" title="Locked — belongs to a previous budget cycle">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                </span>
            @else
                <a href="{{ route('student.expenses.edit', $expense->id) }}" class="p-1.5 text-slate-400 hover:text-[var(--brand)] rounded-lg hover:bg-slate-100 transition-colors" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
                    </svg>
                </a>
                <button wire:click="$set('confirmingDeleteId', {{ $expense->id }})" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-slate-100 transition-colors" title="Delete">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                    </svg>
                </button>
            @endif
        </div>
    </div>
</div>