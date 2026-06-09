<div class="min-h-screen bg-slate-900 text-white p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center border-b border-slate-800 pb-6 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-white">System Administrative Panel</h1>
                <p class="text-sm text-slate-400 mt-1">Logged in as: <span class="text-indigo-400 font-semibold">{{ auth()->user()->name }}</span></p>
            </div>
            <a href="{{ route('logout') }}" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-500 transition shadow-sm">
                Log Out Securely
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-sm">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Registered Students</h3>
                <p class="text-3xl font-bold mt-2">1,248</p>
            </div>
            <div class="bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-sm">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Behavioral System Anomalies</h3>
                <p class="text-3xl font-bold mt-2 text-amber-500">14 Active</p>
            </div>
            <div class="bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-sm">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">AI API Token Health</h3>
                <p class="text-3xl font-bold mt-2 text-emerald-400">99.8% Online</p>
            </div>
        </div>

        <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-700">
                <h2 class="text-lg font-bold text-white">Live Application Security Audit Trail (D10 Activity Logs)</h2>
                <p class="text-xs text-slate-400 mt-0.5">Real-time recording of backend system modifications and core authentication events.</p>
            </div>
            <div class="p-6 text-sm text-slate-400">
                <p>Once we connect your database models, this area will fetch rows from your <code class="text-indigo-300 font-mono bg-slate-950 px-1.5 py-0.5 rounded text-xs">activity_logs</code> table to display system security trails directly to your thesis checking panel.</p>
            </div>
        </div>
    </div>
</div>