<div class="bg-white rounded-3xl border border-slate-200/70 shadow-sm p-6 w-full h-full flex flex-col">
    
    <div class="border-b border-slate-100 pb-4 mb-5 flex items-center justify-between shrink-0">
        <div>
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-widest flex items-center gap-2">
                <span class="w-1.5 h-3 bg-rose-500 rounded-full"></span>
                Active Savings Goal
            </h3>
            <p class="text-[11px] text-slate-400 font-medium mt-0.5">Real-time progress toward your active milestones.</p>
        </div>
        
        <a href="{{ route('student.goals') }}" class="text-[10px] font-extrabold uppercase text-indigo-600 hover:text-indigo-700 tracking-wider flex items-center gap-0.5 shrink-0">
            Manage All
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>
    </div>

    @if($topGoals->isEmpty())
        <div class="flex-1 flex flex-col items-center justify-center text-center py-8 space-y-3 min-h-[168px]">
            <div class="h-10 w-10 bg-slate-50 border border-slate-100 text-slate-400 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-xs text-slate-400 font-semibold max-w-[220px]">No active tracking targets initialized yet.</p>
        </div>
    @else
        <div class="flex-1 space-y-2.5 max-h-[168px] overflow-y-auto pr-1.5 custom-dashboard-scrollbar">
            @foreach($topGoals as $goal)
                <div class="p-1 bg-slate-50/30 rounded-2xl border border-slate-100/50 hover:bg-slate-50/80 transition-colors">
                    <x-savings-card :goal="$goal" type="widget" />
                </div>
            @endforeach
        </div>
    @endif

</div>