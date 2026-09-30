@php
    $appSettings = \App\Models\AppSetting::current();
@endphp

<div class="min-h-screen bg-slate-100/80 flex items-center justify-center p-4 sm:p-6 font-sans antialiased text-slate-800"
     style="--brand: {{ $appSettings->primary_color }}; --brand-rgb: {{ $appSettings->primaryColorRgb() }}; --brand-dark: {{ $appSettings->primaryColorDark() }};">

    <div class="max-w-md w-full bg-white rounded-[2rem] shadow-2xl shadow-indigo-950/10 border border-slate-100 p-7 sm:p-9 space-y-6">

        <div class="flex items-center gap-3 min-w-0">
            <div class="h-10 w-10 rounded-xl flex items-center justify-center text-white shadow-md shrink-0 overflow-hidden bg-[var(--brand)]">
                @if($appSettings->logo_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($appSettings->logo_path) }}" class="h-full w-full object-cover">
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12V7H5a2 2 0 010-4h14v4M3 5v14a2 2 0 002 2h16v-5M18 12a2 2 0 100 4 2 2 0 000-4z"/>
                    </svg>
                @endif
            </div>
            <span class="text-lg font-bold tracking-tight text-slate-900 truncate">{{ $appSettings->application_name }}</span>
        </div>

        <div class="space-y-1">
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Reset your password</h1>
            <p class="text-xs text-slate-500 font-medium">Choose a new password for your account.</p>
        </div>

        <form wire:submit.prevent="resetPassword" class="space-y-4">
            <div class="space-y-1">
                <label for="email" class="block text-xs font-bold text-slate-700">Email address</label>
                <input id="email" type="email" wire:model.lazy="email" placeholder="you@school.edu"
                    class="block w-full rounded-xl px-4 py-2.5 bg-slate-50 border placeholder-slate-400 focus:bg-white focus:outline-none sm:text-sm transition-all duration-200
                    @error('email') border-rose-300 text-rose-900 placeholder-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 @else border-slate-200 text-slate-900 focus:border-[var(--brand)] focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.18)] @enderror">
                @error('email')
                    <span class="text-xs text-rose-600 mt-1 font-medium flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                    </span>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="password" class="block text-xs font-bold text-slate-700">New password</label>
                <input id="password" type="password" wire:model.defer="password" placeholder="8+ characters"
                    class="block w-full rounded-xl px-4 py-2.5 bg-slate-50 border placeholder-slate-400 focus:bg-white focus:outline-none sm:text-sm transition-all duration-200
                    @error('password') border-rose-300 text-rose-900 placeholder-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 @else border-slate-200 text-slate-900 focus:border-[var(--brand)] focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.18)] @enderror">
                @error('password')
                    <span class="text-xs text-rose-600 mt-1 font-medium flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                    </span>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="password_confirmation" class="block text-xs font-bold text-slate-700">Confirm new password</label>
                <input id="password_confirmation" type="password" wire:model.defer="password_confirmation" placeholder="Repeat password"
                    class="block w-full rounded-xl px-4 py-2.5 bg-slate-50 border border-slate-200 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[var(--brand)] focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.18)] sm:text-sm text-slate-900 transition-all duration-200">
            </div>

            <button type="submit" wire:loading.attr="disabled"
                class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-sm font-bold text-white bg-[var(--brand)] hover:opacity-90 active:scale-[0.99] transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove>Reset Password</span>
                <span wire:loading>Resetting...</span>
            </button>
        </form>

        <p class="text-center text-xs text-slate-500">
            Remembered your password?
            <a href="{{ route('login') }}" class="font-bold text-[var(--brand)] hover:opacity-80 transition-colors">Back to login</a>
        </p>
    </div>
</div>