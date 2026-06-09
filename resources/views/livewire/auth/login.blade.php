<div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold tracking-tight text-slate-950">Sign in to your account</h2>
        <p class="mt-2 text-center text-sm text-slate-600">
            Or <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-500">register a new student profile</a>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-xl sm:px-10 border border-slate-100">
            
            @error('auth_failed')
                <div class="mb-5 p-4 bg-red-50 border-l-4 border-red-600 text-sm text-red-800 rounded-r-lg flex items-start gap-3 shadow-sm">
                    <svg class="h-5 w-5 text-red-600 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <span class="font-bold block">Authentication Failed</span>
                        <span class="text-red-700/90 text-xs block mt-0.5">{{ $message }}</span>
                    </div>
                </div>
            @enderror

            <form wire:submit.prevent="loginUser" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email Address</label>
                    <div class="mt-1">
                        <input id="email" type="email" wire:model.lazy="email" 
                            class="block w-full rounded-lg px-3 py-2 placeholder-slate-400 focus:outline-none focus:ring-2 sm:text-sm border transition
                            @error('email') border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500/20 bg-red-50/30 @else border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/20 text-slate-900 @enderror">
                    </div>
                    @error('email') 
                        <span class="text-xs text-red-600 mt-1.5 block font-medium flex items-center gap-1">
                            <span class="w-1 h-1 rounded-full bg-red-600 inline-block"></span> {{ $message }}
                        </span> 
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                    <div class="mt-1">
                        <input id="password" type="password" wire:model.lazy="password" 
                            class="block w-full rounded-lg px-3 py-2 placeholder-slate-400 focus:outline-none focus:ring-2 sm:text-sm border transition
                            @error('password') border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500/20 bg-red-50/30 @else border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/20 text-slate-900 @enderror">
                    </div>
                    @error('password') 
                        <span class="text-xs text-red-600 mt-1.5 block font-medium flex items-center gap-1">
                            <span class="w-1 h-1 rounded-full bg-red-600 inline-block"></span> {{ $message }}
                        </span> 
                    @enderror
                </div>

                <div>
                    <button type="submit" 
                            {{ $lockoutSeconds > 0 ? 'disabled' : '' }}
                            wire:loading.attr="disabled"
                            class="flex w-full justify-center rounded-lg bg-indigo-600 py-2.5 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        
                        @if($lockoutSeconds > 0)
                            Locked out! Please try again later.
                        @else
                            Sign In
                        @endif

                    </button>
                </div>
            </form>
        </div>
    </div>
</div>