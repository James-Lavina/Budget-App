<div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold tracking-tight text-slate-950">Create your account</h2>
        <p class="mt-2 text-center text-sm text-slate-600">
            Or <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">sign in to your existing profile</a>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-xl sm:px-10 border border-slate-100">
            <form wire:submit.prevent="registerUser" class="space-y-5">
                
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Full Name</label>
                    <div class="mt-1">
                        <input id="name" type="text" wire:model.lazy="name" 
                            class="block w-full rounded-lg px-3 py-2 placeholder-slate-400 focus:outline-none focus:ring-2 sm:text-sm border transition
                            @error('name') border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500/20 bg-red-50/30 @else border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/20 text-slate-900 @enderror">
                    </div>
                    @error('name') 
                        <span class="text-xs text-red-600 mt-1.5 block font-medium flex items-center gap-1">
                            <span class="w-1 h-1 rounded-full bg-red-600 inline-block"></span> {{ $message }}
                        </span> 
                    @enderror
                </div>

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
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm Password</label>
                    <div class="mt-1">
                        <input id="password_confirmation" type="password" wire:model.lazy="password_confirmation" 
                            class="block w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 sm:text-sm text-slate-900 transition">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            {{ $lockoutSeconds > 0 ? 'disabled' : '' }}
                            wire:loading.attr="disabled"
                            class="flex w-full justify-center rounded-lg bg-indigo-600 py-2.5 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        
                        @if($lockoutSeconds > 0)
                            Locked out! Please try again later.
                        @else
                            Register Account
                        @endif

                    </button>
                </div>
            </form>
        </div>
    </div>
</div>