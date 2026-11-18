@php $bwBrand = \App\Models\AppSetting::current(); @endphp
<aside class="bw-brand-panel">
  <div class="bw-dot-grid" aria-hidden="true"></div>
  <span class="bw-sparkle" aria-hidden="true">&#10022;</span>
  <div class="bw-coin" aria-hidden="true"></div>

  <div>
    <a href="/" class="bw-brand-logo">
      @if($bwBrand->logo_path)
        <img src="{{ \Illuminate\Support\Facades\Storage::url($bwBrand->logo_path) }}" alt="{{ $bwBrand->application_name }}">
      @else
        <x-app-logo class="bw-logo" />
      @endif
      <span>{{ $bwBrand->application_name }}</span>
    </a>

    <div class="bw-brand-pill">
      <span class="bw-pill-star">&#10022;</span>
      <span>Simple spending for students</span>
    </div>

    <h1 class="bw-brand-heading">Financial confidence starts with one small habit.</h1>

    <p class="bw-brand-subtext">
      {{ $bwBrand->application_name }} turns your weekly allowance into clear, encouraging next steps.
    </p>
  </div>

  <div class="bw-widget">
    <div class="bw-badge bw-badge-expense">
      <div class="bw-badge-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg>
      </div>
      <div class="bw-badge-info">
        <span class="bw-badge-tag">Expense</span>
        <span class="bw-badge-val">₱120 Coffee</span>
      </div>
    </div>

    <div class="bw-stat-card">
      <div>
        <span class="bw-stat-eyebrow">TODAY'S SAFE-TO-SPEND</span>
        <div class="bw-stat-amount">₱280</div>
        <div class="bw-stat-sub">₱930 left of ₱1,500 this week</div>
        <div class="bw-stat-track"><div class="bw-stat-fill"></div></div>
      </div>
      <div class="bw-chart">
        <div class="bw-c-col"><div class="bw-c-bar" style="height:24px"></div><span>M</span></div>
        <div class="bw-c-col"><div class="bw-c-bar" style="height:34px"></div><span>T</span></div>
        <div class="bw-c-col"><div class="bw-c-bar" style="height:28px"></div><span>W</span></div>
        <div class="bw-c-col"><div class="bw-c-bar" style="height:44px"></div><span>T</span></div>
        <div class="bw-c-col"><div class="bw-c-bar active" style="height:36px"></div><span>F</span></div>
        <div class="bw-c-col"><div class="bw-c-bar" style="height:54px"></div><span>S</span></div>
        <div class="bw-c-col"><div class="bw-c-bar" style="height:22px"></div><span>S</span></div>
      </div>
    </div>

    <div class="bw-badge bw-badge-savings">
      <div class="bw-donut"><span>70%</span></div>
      <div class="bw-badge-info">
        <span class="bw-badge-tag">Savings goal</span>
        <span class="bw-badge-val">70% saved</span>
      </div>
    </div>
  </div>

  <div class="bw-brand-footer">
    <div class="bw-trust"><span class="bw-check">&#10003;</span><span>Built for students</span></div>
    <div class="bw-trust"><span class="bw-check">&#10003;</span><span>Free to start</span></div>
  </div>
</aside>