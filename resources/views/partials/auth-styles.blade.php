@php
   $bwSettings = \App\Models\AppSetting::current();
   $bwPrimary  = $bwSettings->primary_color ?: '#4f39fa';
   if (!function_exists('bw_shade')) {
       function bw_shade(string $hex, float $pct, bool $lighten): string
       {
           $hex = ltrim($hex, '#');
           if (strlen($hex) === 3) {
               $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
           }
           $r = hexdec(substr($hex, 0, 2));
           $g = hexdec(substr($hex, 2, 2));
           $b = hexdec(substr($hex, 4, 2));
           $mix = $lighten ? 255 : 0;
           $r = (int) round($r + ($mix - $r) * $pct);
           $g = (int) round($g + ($mix - $g) * $pct);
           $b = (int) round($b + ($mix - $b) * $pct);
           return sprintf('#%02x%02x%02x', $r, $g, $b);
       }
   }
   $bwDark    = bw_shade($bwPrimary, 0.15, false);
   $bwDeep    = bw_shade($bwPrimary, 0.30, false);
   $bwTint50  = bw_shade($bwPrimary, 0.94, true);
   $bwTint100 = bw_shade($bwPrimary, 0.86, true);
   $bwTint300 = bw_shade($bwPrimary, 0.50, true);
   $bwPageA   = bw_shade($bwPrimary, 0.86, true);
   $bwPageB   = bw_shade($bwPrimary, 0.94, true);
@endphp
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
 .bw-auth {
   --primary: {{ $bwPrimary }};
   --primary-dark: {{ $bwDark }};
   --primary-deep: {{ $bwDeep }};
   --tint-50: {{ $bwTint50 }};
   --tint-100: {{ $bwTint100 }};
   --tint-300: {{ $bwTint300 }};
   --text-dark: #111827;
   --input-bg: #f3f5f8;
   --green-dark: #047857;
   --green-accent: #10b981;
   min-height: 100vh;
   display: grid;
   place-items: center;
   padding: 24px;
   font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
   color: var(--text-dark);
   background: linear-gradient(135deg, {{ $bwPageA }} 0%, {{ $bwPageB }} 50%, {{ $bwPageA }} 100%);
   -webkit-font-smoothing: antialiased;
 }
 .bw-auth *, .bw-auth *::before, .bw-auth *::after { box-sizing: border-box; }
 /* ===== CARD ===== */
 .bw-card {
   width: 100%; max-width: 1020px; min-height: 640px;
   display: grid; grid-template-columns: .94fr 1.06fr;
   background: #fff; border-radius: 26px; overflow: hidden;
   box-shadow: 0 28px 65px rgba(15,23,42,.12);
 }
 /* ===== BRAND PANEL ===== */
 .bw-brand-panel {
   position: relative; color: #fff; padding: 36px 34px 32px;
   display: flex; flex-direction: column; justify-content: space-between; overflow: hidden;
   background: linear-gradient(155deg, var(--primary) 0%, var(--primary-dark) 58%, var(--primary-deep) 100%);
 }
 .bw-brand-panel::before {
   content: ""; position: absolute; top: -60px; right: -80px; width: 280px; height: 280px;
   background: radial-gradient(circle, rgba(255,255,255,.12) 0%, transparent 70%); pointer-events: none;
 }
 .bw-dot-grid {
   position: absolute; top: 44px; right: 28px; width: 60px; height: 75px; pointer-events: none;
   background-image: radial-gradient(rgba(255,255,255,.25) 1.8px, transparent 1.8px); background-size: 13px 13px;
 }
 .bw-sparkle {
   position: absolute; left: 48%; bottom: 222px; color: #facc15; font-size: 15px; pointer-events: none;
   animation: bwPulseStar 3s ease-in-out infinite;
 }
 @keyframes bwPulseStar { 0%,100% { transform: scale(1); opacity: .85; } 50% { transform: scale(1.3); opacity: 1; } }
 @keyframes bwFloatCoin { 0%,100% { transform: translateY(0) rotate(0); } 50% { transform: translateY(-7px) rotate(6deg); } }
 .bw-coin {
   position: absolute; bottom: 74px; right: 36px; width: 34px; height: 34px; border-radius: 50%; z-index: 3;
   background: radial-gradient(circle at 30% 30%, #fde047, #facc15); border: 2px solid #eab308;
   box-shadow: 0 6px 14px rgba(0,0,0,.2); display: grid; place-items: center;
   color: #a16207; font-weight: 800; font-size: 14px; animation: bwFloatCoin 3.6s ease-in-out infinite;
 }
 .bw-coin::after {
   content: "₱"; border: 1.5px solid rgba(161,98,7,.45); border-radius: 50%;
   width: 72%; height: 72%; display: grid; place-items: center;
 }
 .bw-brand-logo {
   display: flex; align-items: center; gap: 10px; width: fit-content;
   text-decoration: none; color: #fff; font-weight: 700; font-size: 18px;
   margin-bottom: 22px; position: relative; z-index: 2;
 }
 .bw-brand-logo img, .bw-brand-logo .bw-logo {
   width: 40px; height: 40px; border-radius: 12px; object-fit: cover; flex-shrink: 0;
   box-shadow: 0 0 0 2px rgba(255,255,255,.35);
 }
 .bw-brand-pill {
   display: flex; align-items: center; gap: 7px; width: fit-content;
   background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22);
   border-radius: 999px; padding: 6px 14px; font-size: 12px; font-weight: 500;
   color: #fff; margin-bottom: 18px; position: relative; z-index: 2;
 }
 .bw-pill-star { color: #fde047; font-size: 11px; }
 .bw-brand-heading {
   font-size: 28px; font-weight: 800; line-height: 1.2; letter-spacing: -.025em;
   margin: 0 0 12px; color: #fff; position: relative; z-index: 2;
 }
 .bw-brand-subtext { font-size: 13.5px; line-height: 1.55; color: rgba(255,255,255,.85); max-width: 330px; margin: 0; position: relative; z-index: 2; }
 .bw-widget { position: relative; margin: 34px 0 22px; }
 .bw-stat-card {
   background: #fff; border-radius: 18px; padding: 20px 18px 16px; color: var(--text-dark);
   display: flex; align-items: flex-end; justify-content: space-between; gap: 12px;
   box-shadow: 0 18px 36px rgba(15,23,42,.18);
 }
 .bw-stat-eyebrow { display: block; font-size: 9px; font-weight: 700; letter-spacing: .05em; color: #6b7280; margin-bottom: 4px; }
 .bw-stat-amount { font-size: 32px; font-weight: 800; color: var(--green-dark); line-height: 1.05; letter-spacing: -.03em; margin-bottom: 6px; }
 .bw-stat-sub { font-size: 10px; color: #6b7280; margin-bottom: 10px; }
 .bw-stat-track { width: 145px; height: 5px; background: #e5e7eb; border-radius: 999px; overflow: hidden; }
 .bw-stat-fill { width: 62%; height: 100%; background: var(--primary); border-radius: 999px; }
 .bw-chart { display: flex; align-items: flex-end; gap: 7px; }
 .bw-c-col { display: flex; flex-direction: column; align-items: center; gap: 5px; }
 .bw-c-bar { width: 11px; border-radius: 3px 3px 2px 2px; background: var(--tint-100); }
 .bw-c-bar.active { background: var(--primary); }
 .bw-c-col span { font-size: 8.5px; font-weight: 600; color: #6b7280; }
 .bw-badge {
   position: absolute; z-index: 4; background: #fff; color: var(--text-dark); border-radius: 14px;
   padding: 8px 13px; display: flex; align-items: center; gap: 9px; box-shadow: 0 12px 24px rgba(15,23,42,.16);
   cursor: default; transition: transform .25s cubic-bezier(.22,1,.36,1), box-shadow .25s ease;
 }
 .bw-badge:hover { transform: translateY(-4px) scale(1.03); box-shadow: 0 16px 30px rgba(15,23,42,.24); }
 .bw-badge-expense { top: -22px; right: 12px; }
 .bw-badge-savings { bottom: -24px; left: 12px; }
 .bw-badge-icon { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; background: #fff7ed; color: #ea580c; }
 .bw-badge-info { display: flex; flex-direction: column; }
 .bw-badge-tag { font-size: 8px; font-weight: 700; letter-spacing: .04em; color: #6b7280; text-transform: uppercase; }
 .bw-badge-val { font-size: 12px; font-weight: 700; color: #111827; }
 .bw-donut { width: 32px; height: 32px; border-radius: 50%; background: conic-gradient(var(--green-accent) 0% 70%, #e5e7eb 70% 100%); display: grid; place-items: center; }
 .bw-donut span { width: 23px; height: 23px; border-radius: 50%; background: #fff; display: grid; place-items: center; font-size: 8px; font-weight: 700; color: var(--green-dark); }
 .bw-brand-footer { display: flex; align-items: center; gap: 20px; font-size: 12px; color: rgba(255,255,255,.88); margin-top: 12px; position: relative; z-index: 2; }
 .bw-trust { display: flex; align-items: center; gap: 7px; }
 .bw-check { width: 16px; height: 16px; border-radius: 50%; background: rgba(255,255,255,.2); display: grid; place-items: center; font-size: 10px; color: #fff; }
 /* ===== FORM PANEL ===== */
 .bw-form-panel {
   padding: 40px 56px 36px;
   display: flex;
   flex-direction: column;
   justify-content: flex-start;
 }
 .bw-form-container { width: 100%; max-width: 390px; margin: 0 auto; }
 .bw-back-row {
   display: flex;
   justify-content: flex-end;
   margin-bottom: 28px;
 }
 .bw-back-link {
   display: inline-flex;
   align-items: center;
   gap: 6px;
   text-decoration: none;
   font-size: 13px;
   font-weight: 500;
   color: #6b7280;
   transition: color .15s ease;
 }
 .bw-back-link:hover { color: var(--text-dark); }
 .bw-mobile-brand { display: none; align-items: center; gap: 10px; margin-bottom: 22px; font-weight: 700; font-size: 17px; color: var(--text-dark); }
 .bw-mobile-brand img, .bw-mobile-brand .bw-logo { width: 36px; height: 36px; border-radius: 10px; object-fit: cover; }
 .bw-form-title { font-size: 27px; font-weight: 800; letter-spacing: -.025em; color: #111827; margin: 0 0 6px; }
 .bw-form-subtitle { font-size: 14px; color: #6b7280; margin: 0 0 26px; }
 .bw-alert {
   display: flex; align-items: flex-start; gap: 10px; border-radius: 12px; padding: 11px 14px;
   font-size: 12.5px; font-weight: 500; margin-bottom: 18px;
 }
 .bw-alert strong { display: block; font-weight: 700; margin-bottom: 1px; }
 .bw-alert svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }
 .bw-alert.error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
 .bw-alert.success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
 .bw-group { margin-bottom: 16px; }
 .bw-group label { display: block; font-size: 12.5px; font-weight: 600; color: #1f2937; margin-bottom: 7px; }
 .bw-optional { font-weight: 400; color: #9ca3af; }
 .bw-label-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 7px; }
 .bw-label-row label { margin-bottom: 0; }
 .bw-forgot { font-size: 12.5px; font-weight: 600; color: var(--primary); text-decoration: none; }
 .bw-forgot:hover { text-decoration: underline; }
 .bw-input-wrap { position: relative; display: flex; align-items: center; }
 .bw-input-icon { position: absolute; left: 14px; color: #9ca3af; display: grid; place-items: center; pointer-events: none; }
 .bw-input-wrap input {
   -webkit-appearance: none; appearance: none; width: 100%; height: 44px; margin: 0;
   background: var(--input-bg); border: 1.5px solid transparent; border-radius: 11px; box-shadow: none;
   padding: 0 40px 0 40px; font-family: inherit; font-size: 13.5px; color: #111827; outline: none;
   transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
 }
 .bw-input-wrap input::placeholder { color: #9ca3af; }
 .bw-input-wrap input:focus {
   background: #fff; border-color: var(--primary);
   box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 14%, transparent);
 }
 .bw-input-wrap.has-error input { background: #fff1f2; border-color: #fda4af; }
 .bw-input-wrap.has-error input:focus { border-color: #e11d48; box-shadow: 0 0 0 3px rgba(225,29,72,.14); }
 .bw-field-error { display: flex; align-items: center; gap: 6px; margin-top: 6px; font-size: 12px; font-weight: 500; color: #e11d48; }
 .bw-field-error::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #e11d48; flex-shrink: 0; }
 .bw-toggle {
   position: absolute; right: 12px; background: none; border: none; color: #9ca3af; cursor: pointer;
   display: grid; place-items: center; padding: 4px; transition: color .15s ease;
 }
 .bw-toggle:hover { color: #4b5563; }
 .bw-toggle.is-visible { color: var(--primary); }
 .bw-toggle .eye-closed { display: none; }
 .bw-toggle.is-visible .eye-open { display: none; }
 .bw-toggle.is-visible .eye-closed { display: block; }
 .bw-password-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
 .bw-submit {
   width: 100%; height: 46px; margin-top: 8px; background: var(--primary); color: #fff; border: none;
   border-radius: 11px; font-family: inherit; font-size: 14.5px; font-weight: 600;
   display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer;
   box-shadow: 0 8px 18px color-mix(in srgb, var(--primary) 28%, transparent);
   transition: background .15s ease, transform .15s ease;
 }
 .bw-submit:hover { background: var(--primary-dark); transform: translateY(-1px); }
 .bw-submit:disabled { opacity: .55; cursor: not-allowed; transform: none; }
 .bw-submit .bw-inline { display: inline-flex; align-items: center; gap: 8px; }
 .bw-spin { animation: bwSpin 1s linear infinite; }
 @keyframes bwSpin { to { transform: rotate(360deg); } }
 .bw-switch { margin: 22px 0 0; text-align: center; font-size: 13.5px; color: #6b7280; }
 .bw-switch a { font-weight: 600; color: var(--primary); text-decoration: none; margin-left: 3px; }
 .bw-switch a:hover { text-decoration: underline; }
 /* ===== RESPONSIVE ===== */
 @media (max-width: 880px) {
   .bw-card { grid-template-columns: 1fr; max-width: 480px; min-height: 0; }
   .bw-brand-panel { display: none; }
   .bw-form-panel { padding: 32px 26px; }
   .bw-back-row { margin-bottom: 18px; }
   .bw-mobile-brand { display: flex; }
 }
 @media (max-width: 480px) {
   .bw-auth { padding: 12px; }
   .bw-password-grid { grid-template-columns: 1fr; gap: 0; }
 }
</style>