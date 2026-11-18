<div class="bw-auth">
    @include('partials.auth-styles')
     <div class="bw-card">
      @include('partials.auth-brand-panel')
       <main class="bw-form-panel">
        <div class="bw-form-container">
          <div class="bw-back-row">
            <a href="/" class="bw-back-link"><span>&larr;</span><span>Back to home</span></a>
          </div>
           @php $bwApp = \App\Models\AppSetting::current(); @endphp
          <div class="bw-mobile-brand">
            @if($bwApp->logo_path)
              <img src="{{ \Illuminate\Support\Facades\Storage::url($bwApp->logo_path) }}" alt="{{ $bwApp->application_name }}">
            @else
              <x-app-logo class="bw-logo" />
            @endif
            <span>{{ $bwApp->application_name }}</span>
          </div>
           <h2 class="bw-form-title">Welcome back</h2>
          <p class="bw-form-subtitle">Pick up where you left off — your budget is waiting.</p>
           @if (session()->has('success'))
            <div class="bw-alert success">
              <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <div>{{ session('success') }}</div>
            </div>
          @enderror
           @error('auth_failed')
            <div class="bw-alert error">
              <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
              <div><strong>Couldn't sign you in</strong>{{ $message }}</div>
            </div>
          @enderror
           <form wire:submit.prevent="loginUser" novalidate>
            <div class="bw-group">
              <label for="email">Email address</label>
              <div class="bw-input-wrap @error('email') has-error @enderror">
                <span class="bw-input-icon">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </span>
                <input id="email" type="email" wire:model.lazy="email" placeholder="you@school.edu" autocomplete="email">
              </div>
              @error('email') <span class="bw-field-error">{{ $message }}</span> @enderror
            </div>
             <div class="bw-group">
              <div class="bw-label-row">
                <label for="password">Password</label>
                @if (Route::has('password.request'))
                  <a href="{{ route('password.request') }}" class="bw-forgot">Forgot password?</a>
                @endif
              </div>
              <div class="bw-input-wrap @error('password') has-error @enderror">
                <span class="bw-input-icon">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <input id="password" type="password" wire:model.lazy="password" placeholder="Enter your password" autocomplete="current-password">
                <button type="button" class="bw-toggle" data-bw-toggle aria-label="Show password">
                  <svg class="eye-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg class="eye-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M10.58 10.58a2 2 0 102.83 2.83M9.88 4.24A10.2 10.2 0 0112 4c6 0 10 8 10 8a18.2 18.2 0 01-3.17 4.33M6.61 6.61C3.93 8.63 2 12 2 12s4 8 10 8a9.83 9.83 0 004.39-1.03"/></svg>
                </button>
              </div>
              @error('password') <span class="bw-field-error">{{ $message }}</span> @enderror
            </div>
             <button type="submit"
                class="bw-submit"
                {{ $lockoutSeconds > 0 ? 'disabled' : '' }}
                wire:loading.attr="disabled"
                wire:target="loginUser">
                @if($lockoutSeconds > 0)
                    <span>Too many attempts. Try again in {{ $lockoutSeconds }}s</span>
                @else
                    <span wire:loading.remove wire:target="loginUser" style="display: inline-flex; align-items: center; gap: 8px;">
                        <span>Sign in</span>
                        <span>&rarr;</span>
                    </span>
                    <span wire:loading wire:target="loginUser" style="display: none; align-items: center; gap: 8px;">
                        <svg class="bw-spin" width="16" height="16"
                            fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:.25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"/>
                            <path style="opacity:.75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span>Signing in...</span>
                    </span>
                @endif
            </button>
          </form>
           <p class="bw-switch">
            New to {{ $bwApp->application_name }}?
            <a href="{{ route('register') }}">Sign up</a>
          </p>
        </div>
      </main>
    </div>
     @include('partials.auth-scripts')
  </div>