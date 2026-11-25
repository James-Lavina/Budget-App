<div class="bw-auth">
  @include('partials.auth-styles')

  <div class="bw-card">
    @include('partials.auth-brand-panel')

    <main class="bw-form-panel">
      <div class="bw-form-container">
        <div class="bw-back-row">
          <a href="/" class="bw-back-link"><span>&larr;</span><span>Back to home</span></a>
        </div>

        <div class="bw-mobile-brand">
          @if($appSettings->logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($appSettings->logo_path) }}" alt="{{ $appSettings->application_name }}">
          @else
            <x-app-logo class="bw-logo" />
          @endif
          <span>{{ $appSettings->application_name }}</span>
        </div>

        {{-- ===================== STEP 1: DETAILS ===================== --}}
        @if($step === 1)
          <div wire:key="register-step-1">
            <h2 class="bw-form-title">Create your account</h2>
            <p class="bw-form-subtitle">Start tracking your student budget in under a minute.</p>

            <form wire:submit.prevent="registerUser" novalidate>
              <div class="bw-group">
                <label for="name">Full name</label>
                <div class="bw-input-wrap @error('name') has-error @enderror">
                  <span class="bw-input-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  </span>
                  <input id="name" type="text" wire:model.defer="name" placeholder="John Doe" autocomplete="name">
                </div>
                @error('name') <span class="bw-field-error">{{ $message }}</span> @enderror
              </div>

              <div class="bw-group">
                <label for="email">Email address</label>
                <div class="bw-input-wrap @error('email') has-error @enderror">
                  <span class="bw-input-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                  </span>
                  <input id="email" type="email" wire:model.defer="email" placeholder="you@school.edu" autocomplete="email">
                </div>
                @error('email') <span class="bw-field-error">{{ $message }}</span> @enderror
              </div>

              <div class="bw-group">
                <label for="school">School <span class="bw-optional">(optional)</span></label>
                <div class="bw-input-wrap @error('school') has-error @enderror">
                  <span class="bw-input-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                  </span>
                  <input id="school" type="text" wire:model.defer="school" placeholder="e.g., University of Santo Tomas">
                </div>
                @error('school') <span class="bw-field-error">{{ $message }}</span> @enderror
              </div>

              <div class="bw-password-grid">
                <div class="bw-group">
                  <label for="password">Password</label>
                  <div class="bw-input-wrap @error('password') has-error @enderror">
                    <span class="bw-input-icon">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input id="password" type="password" wire:model.defer="password" placeholder="8+ characters" autocomplete="new-password">
                    <button type="button" class="bw-toggle" data-bw-toggle aria-label="Show password">
                      <svg class="eye-open" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                      <svg class="eye-closed" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M10.58 10.58a2 2 0 102.83 2.83M9.88 4.24A10.2 10.2 0 0112 4c6 0 10 8 10 8a18.2 18.2 0 01-3.17 4.33M6.61 6.61C3.93 8.63 2 12 2 12s4 8 10 8a9.83 9.83 0 004.39-1.03"/></svg>
                    </button>
                  </div>
                  @error('password') <span class="bw-field-error">{{ $message }}</span> @enderror
                </div>

                <div class="bw-group">
                  <label for="password_confirmation">Confirm password</label>
                  <div class="bw-input-wrap">
                    <span class="bw-input-icon">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input id="password_confirmation" type="password" wire:model.defer="password_confirmation" placeholder="Repeat password" autocomplete="new-password">
                    <button type="button" class="bw-toggle" data-bw-toggle aria-label="Show password">
                      <svg class="eye-open" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                      <svg class="eye-closed" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M10.58 10.58a2 2 0 102.83 2.83M9.88 4.24A10.2 10.2 0 0112 4c6 0 10 8 10 8a18.2 18.2 0 01-3.17 4.33M6.61 6.61C3.93 8.63 2 12 2 12s4 8 10 8a9.83 9.83 0 004.39-1.03"/></svg>
                    </button>
                  </div>
                </div>
              </div>

              <button type="submit"
                class="bw-submit"
                {{ $lockoutSeconds > 0 ? 'disabled' : '' }}
                wire:loading.attr="disabled"
                wire:target="registerUser">
                @if($lockoutSeconds > 0)
                  <span>Too many attempts. Try again in {{ $lockoutSeconds }}s</span>
                @else
                  <span wire:loading.remove wire:target="registerUser" style="display: inline-flex; align-items: center; gap: 8px;">
                    <span>Send verification code</span>
                    <span>&rarr;</span>
                  </span>
                  <span wire:loading wire:target="registerUser" style="display: none; align-items: center; gap: 8px;">
                    <svg class="bw-spin" width="16" height="16" fill="none" viewBox="0 0 24 24">
                      <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span>Sending code...</span>
                  </span>
                @endif
              </button>
            </form>

            <p class="bw-switch">
              Already have an account?
              <a href="{{ route('login') }}">Log in</a>
            </p>
          </div>

        {{-- ===================== STEP 2: VERIFY OTP ===================== --}}
        @else
          <div wire:key="register-step-2">
            <h2 class="bw-form-title">Verify your email</h2>
            <p class="bw-form-subtitle">
              We sent a 6-digit code to <strong>{{ $otpEmail }}</strong>.
              It expires in {{ \App\Http\Livewire\Auth\Register::OTP_EXPIRES_MINUTES }} minutes.
            </p>

            @if($otpNotice)
              <div class="bw-alert success">
                <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>{{ $otpNotice }}</div>
              </div>
            @endif

            <form wire:submit.prevent="verifyOtp" novalidate>
              <div class="bw-group">
                <label for="otp">Verification code</label>
                <div class="bw-input-wrap @error('otp') has-error @enderror">
                  <span class="bw-input-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                  </span>
                  <input id="otp" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus
                    wire:model.defer="otp" placeholder="123456"
                    oninput="this.value = this.value.replace(/\D/g, '')"
                    style="letter-spacing: .35em; font-weight: 700; font-size: 16px;">
                </div>
                @error('otp') <span class="bw-field-error">{{ $message }}</span> @enderror
              </div>

              <button type="submit" class="bw-submit" wire:loading.attr="disabled" wire:target="verifyOtp">
                <span wire:loading.remove wire:target="verifyOtp" style="display: inline-flex; align-items: center; gap: 8px;">
                  <span>Verify &amp; create account</span>
                  <span>&rarr;</span>
                </span>
                <span wire:loading wire:target="verifyOtp" style="display: none; align-items: center; gap: 8px;">
                  <svg class="bw-spin" width="16" height="16" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  <span>Verifying...</span>
                </span>
              </button>
            </form>

            <p class="bw-switch">
              Didn't get the code?
              @if($resendWait > 0)
                {{-- Rendered only while cooling down, so the poll stops itself when this element disappears. --}}
                <span wire:poll.1s style="font-weight: 600; color: #9ca3af; margin-left: 3px;">Resend in {{ $resendWait }}s</span>
              @else
                <a href="#" wire:click.prevent="resendOtp" wire:loading.remove wire:target="resendOtp">Resend code</a>
                <span wire:loading wire:target="resendOtp" style="font-weight: 600; color: #9ca3af; margin-left: 3px;">Sending...</span>
              @endif
            </p>

            <p class="bw-switch" style="margin-top: 10px;">
              <a href="#" wire:click.prevent="backToDetails">&larr; Use a different email</a>
            </p>
          </div>
        @endif
      </div>
    </main>
  </div>

  @include('partials.auth-scripts')
</div>