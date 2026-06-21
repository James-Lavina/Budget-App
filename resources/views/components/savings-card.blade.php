@props(['goal', 'type' => 'manager'])

@php
    $percentage = $goal->target_amount > 0 ? min(100, round(($goal->current_saved / $goal->target_amount) * 100)) : 0;
    $daysLeft = null;
    $isOverdue = false;
    
    if ($goal->target_date) {
        $target = \Carbon\Carbon::parse($goal->target_date)->startOfDay();
        $today = now()->startOfDay();
        $daysLeft = $today->diffInDays($target, false);
        $isOverdue = $daysLeft < 0;
    }
@endphp

<div class="bg-white rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-slate-200/80 p-5 flex flex-col justify-between space-y-4 transition-all hover:border-slate-300">
    <div class="space-y-1">
        <div class="flex items-start justify-between gap-2">
            <h4 class="text-sm font-black text-slate-800 tracking-tight line-clamp-1">{{ $goal->target_name }}</h4>
            
            @if($goal->status === 'active' && $daysLeft !== null)
                <span class="shrink-0 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider rounded-md {{ $isOverdue ? 'bg-rose-50 text-rose-600' : ($daysLeft <= 3 ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-500') }}">
                    {{ $isOverdue ? 'Overdue' : ($daysLeft == 0 ? 'Today' : $daysLeft . ' Days Left') }}
                </span>
            @elseif($goal->status === 'achieved')
                <span class="shrink-0 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider rounded-md bg-emerald-50 text-emerald-600">Achieved</span>
            @elseif($goal->status === 'abandoned')
                <span class="shrink-0 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider rounded-md bg-slate-100 text-slate-400">Archived</span>
            @endif
        </div>

        <p class="text-[10px] text-slate-400 font-bold tracking-wide uppercase">
            {{ $goal->target_date ? 'Target: ' . \Carbon\Carbon::parse($goal->target_date)->format('M d, Y') : 'Continuous Goal' }}
        </p>
    </div>

    <div class="space-y-1.5">
        <div class="flex justify-between items-baseline text-slate-500">
            <span class="text-[10px] font-extrabold uppercase tracking-wider">Saved Balance</span>
            <div class="text-right">
                <span class="text-sm font-black text-slate-900">₱{{ number_format($goal->current_saved, 2) }}</span>
                <span class="text-[10px] font-bold text-slate-400">/ ₱{{ number_format($goal->target_amount, 0) }}</span>
            </div>
        </div>

        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500 {{ $goal->status === 'achieved' ? 'bg-emerald-500' : ($isOverdue ? 'bg-rose-500' : 'bg-indigo-600') }}" style="width: {{ $percentage }}%"></div>
        </div>
        <div class="text-[10px] font-bold text-slate-400 text-right uppercase tracking-wider">{{ $percentage }}% Completed</div>
    </div>

    @if($type === 'manager')
        <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-1.5">
            @if($goal->status === 'active')
                <button type="button" wire:click="openFundingModal({{ $goal->id }})" class="flex-1 py-1.5 px-3 bg-indigo-50 hover:bg-indigo-100/80 text-indigo-700 font-bold text-[11px] rounded-lg transition-colors text-center">Add Savings</button>
                <button type="button" wire:click="abandonGoal({{ $goal->id }})" class="py-1.5 px-2.5 border border-slate-200 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all text-center"><svg class="w-3.5 h-3.5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-14v4M1 7h22"></path></svg></button>
            
            @elseif($goal->status === 'abandoned')
                <button type="button" wire:click="unarchiveGoal({{ $goal->id }})" class="flex-1 py-1.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] rounded-lg transition-colors text-center">
                    Unarchive
                </button>
                <button type="button" wire:click="deleteGoal({{ $goal->id }})" class="py-1.5 px-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg transition-all text-center">
                    <svg class="w-3.5 h-3.5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-14v4M1 7h22"></path></svg>
                </button>

            @else
                <button type="button" wire:click="deleteGoal({{ $goal->id }})" class="w-full py-1.5 px-3 bg-slate-50 hover:bg-rose-50 text-slate-400 hover:text-rose-600 font-bold text-[11px] rounded-lg transition-colors flex items-center justify-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-14v4M1 7h22"></path></svg>Delete Permanently</button>
            @endif
        </div>
    @endif
</div>