{{-- Local-only fast-forward testing banner. Only renders when a fake date is active. --}}
@if(app()->environment('local'))
    <div class="mb-4 px-4 py-3 rounded-2xl bg-amber-50 border border-amber-200 flex flex-wrap items-center justify-between gap-3">
 
        @if(session()->has('test_fake_now'))
            <span class="flex items-center gap-2 text-xs font-bold text-amber-800">
                <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse shrink-0"></span>
                Testing as: {{ \Carbon\Carbon::now()->format('l, M d, Y') }}
            </span>
        @else
            <span class="flex items-center gap-2 text-xs font-bold text-amber-700">
                <span class="h-2 w-2 rounded-full bg-amber-300 shrink-0"></span>
                Fast-forward testing available (local only)
            </span>
        @endif
 
        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('test.fast-forward') }}" class="flex items-center gap-1.5">
                <input
                    type="date"
                    name="date"
                    value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                    class="text-[11px] font-semibold rounded-lg border border-amber-300 bg-white px-2 py-1.5 text-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-400"
                >
                <button
                    type="submit"
                    class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-[11px] font-bold transition-colors"
                >
                    Jump
                </button>
            </form>
 
            @if(session()->has('test_fake_now'))
                <a
                    href="{{ route('test.fast-forward.reset') }}"
                    class="px-3 py-1.5 bg-white border border-amber-300 rounded-lg text-[11px] font-bold text-amber-700 hover:bg-amber-100 transition-colors"
                >
                    Reset to real time
                </a>
            @endif
        </div>
    </div>
@endif
 






