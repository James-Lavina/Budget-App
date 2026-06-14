<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Active Savings Targets</h3>
            <p class="text-[10px] text-slate-400 font-medium">Real-time progress toward your active milestones.</p>
        </div>
        <a href="{{ route('student.goals') }}" class="text-[10px] font-extrabold uppercase text-indigo-600 hover:text-indigo-700 tracking-wider flex items-center gap-0.5">
            Manage All
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
        </a>
    </div>

    @if($topGoals->isEmpty())
        <div class="bg-white rounded-2xl border border-dashed border-slate-200 text-center py-8 px-4">
            <p class="text-[11px] font-bold text-slate-400">No active tracking targets initialized yet.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach($topGoals as $goal)
                <x-savings-card :goal="$goal" type="widget" />
            @endforeach
        </div>
    @endif
</div>