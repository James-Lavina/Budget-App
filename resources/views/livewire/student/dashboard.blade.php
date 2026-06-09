<div class="p-8 max-w-4xl mx-auto mt-10 bg-white shadow rounded-xl border border-slate-100">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-950">Welcome to Your Financial Dashboard, {{ auth()->user()->name }}!</h1>
            <p class="text-sm text-slate-600">Your core custom authentication framework is fully operational.</p>
        </div>
        <a href="{{ route('logout') }}" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-medium hover:bg-slate-800 transition">
            Log Out
        </a>
    </div>
</div>