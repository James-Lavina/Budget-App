<?php
    $appSettings = \App\Models\AppSetting::current();
    $primary     = $appSettings->primary_color ?: '#4f39fa';

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

    $primaryDark = bw_shade($primary, 0.15, false);
    $tint50      = bw_shade($primary, 0.94, true);
    $tint100     = bw_shade($primary, 0.86, true);
    $tint200     = bw_shade($primary, 0.72, true);
    $tint300     = bw_shade($primary, 0.50, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $appSettings->application_name }}</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('images/budgetwise-icon 1 · 42 × 42.svg') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: {{ $primary }};
      --primary-hover: {{ $primaryDark }};
      --tint-50: {{ $tint50 }};
      --tint-100: {{ $tint100 }};
      --tint-200: {{ $tint200 }};
      --tint-300: {{ $tint300 }};
      --text-dark: #111827;
      --text-body: #4b5563;
      --text-muted: #6b7280;
      --bg-page: #f8f9fc;
      --green-bg: #ecfdf5;
      --green-text: #047857;
      --green-accent: #10b981;
      --amber-bg: #fef9ec;
      --amber-text: #92400e;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
      font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg-page);
      color: var(--text-dark);
      -webkit-font-smoothing: antialiased;
    }
    section[id] { scroll-margin-top: 90px; }

    /* ===== NAVBAR ===== */
    .navbar {
      position: sticky; top: 0; z-index: 50;
      background: #fff; border-bottom: 1px solid #f1f5f9;
    }
    .nav-container {
      max-width: 1200px; margin: 0 auto; padding: 18px 32px;
      display: flex; align-items: center; justify-content: space-between; gap: 16px;
    }
    .logo {
      display: flex; align-items: center; gap: 10px; min-width: 0;
      text-decoration: none; color: var(--text-dark);
      font-weight: 700; font-size: 19px; letter-spacing: -0.02em;
    }
    .logo img, .logo .app-logo { width: 38px; height: 38px; border-radius: 10px; object-fit: cover; flex-shrink: 0; }
    .logo span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .nav-links { display: flex; align-items: center; gap: 32px; list-style: none; }
    .nav-links a {
      text-decoration: none; color: #374151; font-size: 14.5px; font-weight: 500;
      transition: color .15s ease;
    }
    .nav-links a:hover { color: var(--primary); }
    .btn-login {
      text-decoration: none; color: var(--primary); font-size: 14px; font-weight: 600;
      padding: 9px 22px; border: 1.5px solid var(--tint-300); border-radius: 999px;
      transition: all .15s ease; white-space: nowrap;
    }
    .btn-login:hover { background: var(--tint-50); border-color: var(--primary); }

    /* ===== HERO ===== */
    .hero {
      max-width: 1200px; margin: 0 auto; padding: 64px 32px 84px;
      display: grid; grid-template-columns: 1.05fr .95fr; align-items: center; gap: 56px;
    }
    .badge {
      display: inline-block; background: var(--tint-50); border: 1px solid var(--tint-100);
      color: var(--primary); font-size: 13px; font-weight: 600;
      padding: 7px 16px; border-radius: 999px; margin-bottom: 24px;
    }
    .hero-title {
      font-size: clamp(38px, 4.4vw, 54px); font-weight: 800; line-height: 1.12;
      letter-spacing: -0.03em; color: #18181b; margin-bottom: 22px;
    }
    .hero-title .highlight { color: var(--primary); }
    .hero-subtitle { font-size: 17px; line-height: 1.6; color: var(--text-body); max-width: 470px; margin-bottom: 32px; }
    .hero-actions { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; flex-wrap: wrap; }
    .btn-primary {
      background: var(--primary); color: #fff; text-decoration: none;
      font-size: 15px; font-weight: 600; padding: 14px 28px; border-radius: 999px;
      box-shadow: 0 6px 16px color-mix(in srgb, var(--primary) 25%, transparent);
      transition: background .15s ease, transform .15s ease; display: inline-block;
    }
    .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
    .btn-secondary {
      background: #fff; color: var(--text-dark); text-decoration: none;
      font-size: 15px; font-weight: 600; padding: 13px 26px; border-radius: 999px;
      border: 1px solid #d1d5db; transition: background .15s ease, border-color .15s ease; display: inline-block;
    }
    .btn-secondary:hover { background: #f9fafb; border-color: #9ca3af; }
    .hero-meta { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--text-muted); flex-wrap: wrap; }

    /* ===== SHOWCASE CARD ===== */
    .hero-visual { display: flex; justify-content: center; }
    .showcase-card {
      position: relative; width: 100%; max-width: 440px;
      background: var(--tint-100); border-radius: 36px; padding: 28px 24px 24px;
      display: flex; flex-direction: column; align-items: center;
    }
    .showcase-card::before {
      content: ""; position: absolute; top: 0; left: 0; width: 195px; height: 230px;
      background: var(--primary); border-top-left-radius: 36px; border-bottom-right-radius: 170px;
      z-index: 0; pointer-events: none;
    }
    .showcase-card::after {
      content: ""; position: absolute; bottom: 0; right: 0; width: 190px; height: 175px;
      background: var(--tint-200); border-bottom-right-radius: 36px; border-top-left-radius: 170px;
      z-index: 0; pointer-events: none;
    }
    .dot-grid {
      position: absolute; right: 26px; top: 155px; width: 52px; height: 76px;
      background-image: radial-gradient(var(--tint-300) 2px, transparent 2px);
      background-size: 13px 13px; z-index: 1; pointer-events: none;
    }
    .sparkle {
      position: absolute; color: var(--primary); font-size: 18px; line-height: 1;
      z-index: 1; user-select: none; pointer-events: none;
      animation: pulseSparkle 3.2s ease-in-out infinite;
    }
    .sparkle-left { left: 34px; bottom: 185px; }
    .sparkle-right { right: 46px; bottom: 270px; color: var(--tint-300); font-size: 15px; animation-delay: 1.4s; }

    /* ===== COINS ===== */
    @keyframes floatCoinA { 0%,100% { transform: translateY(0) rotate(0); } 50% { transform: translateY(-9px) rotate(6deg); } }
    @keyframes floatCoinB { 0%,100% { transform: translateY(0) rotate(0); } 50% { transform: translateY(-11px) rotate(-6deg); } }
    @keyframes pulseSparkle { 0%,100% { transform: scale(1); opacity: .85; } 50% { transform: scale(1.25); opacity: 1; } }
    .coin {
      position: absolute; border-radius: 50%;
      background: radial-gradient(circle at 30% 30%, #fde047, #facc15);
      border: 2px solid #eab308; box-shadow: 0 6px 14px rgba(202,138,4,.25);
      display: grid; place-items: center; color: #a16207; font-weight: 800;
      z-index: 3; cursor: pointer; transition: box-shadow .25s ease; will-change: transform;
    }
    .coin:hover { box-shadow: 0 10px 22px rgba(202,138,4,.45); }
    .coin::after {
      content: "₱"; border: 1.5px solid rgba(161,98,7,.45); border-radius: 50%;
      width: 72%; height: 72%; display: grid; place-items: center;
    }
    .coin-top-left { width: 30px; height: 30px; top: 34px; left: 20px; font-size: 13px; animation: floatCoinA 3.6s ease-in-out infinite; }
    .coin-top-right { width: 50px; height: 50px; top: 52px; right: 18px; font-size: 20px; animation: floatCoinB 4.2s ease-in-out infinite .5s; }
    .coin-bottom-right { width: 36px; height: 36px; bottom: 18px; right: 36px; font-size: 15px; animation: floatCoinA 3.9s ease-in-out infinite 1s; }

    /* ===== FLOATING PILLS ===== */
    .floating-pill {
      position: absolute; z-index: 10; background: #fff; border: 1.5px solid transparent;
      border-radius: 16px; padding: 10px 14px; display: flex; align-items: center; gap: 10px;
      box-shadow: 0 12px 28px rgba(15,23,42,.11); cursor: pointer; user-select: none;
      transition: transform .25s cubic-bezier(.22,1,.36,1), box-shadow .25s ease, border-color .25s ease;
    }
    .floating-pill:hover, .floating-pill:focus-visible {
      transform: translateY(-6px) scale(1.04);
      box-shadow: 0 18px 36px color-mix(in srgb, var(--primary) 20%, transparent);
      border-color: var(--tint-300); outline: none;
    }
    .floating-pill:active { transform: translateY(-2px) scale(.99); }
    .pill-icon { width: 32px; height: 32px; border-radius: 10px; display: grid; place-items: center; flex-shrink: 0; transition: transform .25s ease; }
    .floating-pill:hover .pill-icon { transform: scale(1.1); }
    .pill-content { display: flex; flex-direction: column; }
    .pill-tag { font-size: 9px; font-weight: 700; letter-spacing: .04em; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1px; }
    .pill-value { font-size: 13px; font-weight: 700; color: var(--text-dark); white-space: nowrap; }
    .pill-coffee { top: 90px; left: -34px; }
    .pill-coffee .pill-icon { background: #fff7ed; color: #ea580c; }
    .pill-school { top: 288px; right: -34px; }
    .pill-school .pill-icon { background: var(--tint-50); color: var(--primary); }
    .pill-savings { bottom: 78px; left: -38px; padding: 10px 16px 10px 12px; }
    .ring-chart {
      width: 36px; height: 36px; border-radius: 50%;
      background: conic-gradient(var(--green-accent) 0% 70%, #e5e7eb 70% 100%);
      display: grid; place-items: center; flex-shrink: 0; transition: transform .3s ease;
    }
    .floating-pill:hover .ring-chart { transform: rotate(15deg) scale(1.08); }
    .ring-chart span {
      width: 26px; height: 26px; border-radius: 50%; background: #fff;
      display: grid; place-items: center; font-size: 9px; font-weight: 700; color: var(--green-text);
    }

    /* ===== PHONE ===== */
    .phone-frame {
      position: relative; z-index: 2; width: 266px; height: 454px; background: #fff;
      border: 7px solid #18181b; border-radius: 36px; padding: 20px 15px 18px;
      box-shadow: 0 22px 45px rgba(15,23,42,.14); overflow: hidden;
    }
    .slides-viewport { position: relative; width: 100%; height: 100%; }
    .phone-slide {
      position: absolute; inset: 0; display: flex; flex-direction: column;
      opacity: 0; transform: translateX(22px) scale(.98); pointer-events: none;
      transition: opacity .32s cubic-bezier(.22,1,.36,1), transform .32s cubic-bezier(.22,1,.36,1);
    }
    .phone-slide.active { opacity: 1; transform: translateX(0) scale(1); pointer-events: auto; }
    .phone-day { font-size: 12px; font-weight: 600; color: #4b5563; margin-bottom: 12px; padding-left: 2px; }
    .budget-card {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
      color: #fff; border-radius: 18px; padding: 16px; margin-bottom: 16px;
    }
    .budget-label { font-size: 11px; color: var(--tint-100); margin-bottom: 4px; }
    .budget-amount { font-size: 28px; font-weight: 800; letter-spacing: -.02em; margin-bottom: 12px; }
    .progress-track { width: 100%; height: 6px; background: rgba(255,255,255,.28); border-radius: 999px; overflow: hidden; margin-bottom: 8px; }
    .progress-fill { width: 62%; height: 100%; background: #fff; border-radius: 999px; }
    .budget-sub { font-size: 10.5px; color: var(--tint-100); }
    .section-label { font-size: 9.5px; font-weight: 700; letter-spacing: .05em; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; padding-left: 2px; }
    .section-label.green { color: var(--green-text); }
    .expense-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; }
    .expense-row {
      display: flex; align-items: center; justify-content: space-between;
      border: 1px solid #f1f5f9; border-radius: 11px; padding: 10px 12px;
      font-size: 12px; font-weight: 600; color: #1f2937; background: #fff;
    }
    .status-banner { background: var(--green-bg); color: var(--green-text); border-radius: 12px; padding: 11px 12px; font-size: 11px; font-weight: 600; line-height: 1.45; }
    .receipt-dropzone {
      background: var(--tint-50); border: 1.5px dashed var(--primary); border-radius: 16px;
      padding: 22px 18px; margin-bottom: 18px; display: flex; flex-direction: column; gap: 11px;
    }
    .skeleton-line { height: 7px; border-radius: 999px; background: var(--tint-200); }
    .w-65 { width: 65%; } .w-85 { width: 85%; } .w-55 { width: 55%; } .w-45 { width: 45%; }
    .field-list { display: flex; flex-direction: column; gap: 9px; }
    .field-row {
      display: flex; align-items: center; justify-content: space-between;
      border: 1px solid #f1f5f9; border-radius: 11px; padding: 10px 12px; font-size: 11.5px; background: #fff;
    }
    .field-key { color: var(--text-muted); font-weight: 500; }
    .field-val { color: var(--text-dark); font-weight: 700; }
    .bar-chart { display: flex; align-items: flex-end; justify-content: space-between; height: 165px; padding: 12px 6px 4px; margin-bottom: 16px; }
    .bar-col { display: flex; flex-direction: column; align-items: center; gap: 8px; }
    .bar { width: 18px; border-radius: 6px 6px 4px 4px; }
    .bar.solid { background: var(--primary); }
    .bar.predicted { background: var(--tint-300); }
    .bar.warning { background: #fcd34d; }
    .bar-col span { font-size: 10px; font-weight: 600; color: var(--text-muted); }
    .amber-banner { background: var(--amber-bg); color: var(--amber-text); border-radius: 12px; padding: 12px; font-size: 11px; font-weight: 600; line-height: 1.45; margin-bottom: 12px; }
    .chart-legend { font-size: 10px; color: var(--text-muted); padding-left: 2px; }
    .item-input-box {
      display: flex; align-items: center; justify-content: space-between;
      border: 1.5px solid var(--primary); border-radius: 12px; padding: 11px 14px;
      font-size: 12.5px; font-weight: 700; color: var(--text-dark); margin-bottom: 12px;
    }
    .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; }
    .stat-box { border-radius: 12px; padding: 11px 12px; display: flex; flex-direction: column; gap: 4px; }
    .stat-box.gray { background: #f3f4f6; }
    .stat-box.gray .stat-label { color: var(--text-muted); }
    .stat-box.gray .stat-value { color: var(--text-dark); }
    .stat-box.amber { background: var(--amber-bg); }
    .stat-box.amber .stat-label, .stat-box.amber .stat-value { color: var(--amber-text); }
    .stat-label { font-size: 10px; font-weight: 600; }
    .stat-value { font-size: 16px; font-weight: 800; }
    .phone-cta-btn {
      width: 100%; background: var(--primary); color: #fff; border: none; border-radius: 12px;
      padding: 12px; font-size: 12px; font-weight: 600; cursor: pointer; margin-top: 2px; font-family: inherit;
      transition: background .15s ease;
    }
    .phone-cta-btn:hover { background: var(--primary-hover); }

    /* ===== CAROUSEL FOOTER ===== */
    .showcase-footer { position: relative; z-index: 4; margin-top: 18px; text-align: center; }
    .showcase-caption { font-size: 13.5px; font-weight: 700; color: var(--text-dark); margin-bottom: 12px; min-height: 20px; }
    .carousel-controls { display: flex; align-items: center; justify-content: center; gap: 14px; }
    .arrow-btn {
      width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center;
      cursor: pointer; font-size: 16px; font-family: inherit; transition: transform .15s ease, background .15s ease;
    }
    .arrow-btn.prev { background: #fff; border: 1.5px solid var(--tint-300); color: var(--primary); }
    .arrow-btn.prev:hover { background: var(--tint-50); transform: scale(1.06); }
    .arrow-btn.next { background: var(--primary); border: none; color: #fff; box-shadow: 0 4px 10px color-mix(in srgb, var(--primary) 30%, transparent); }
    .arrow-btn.next:hover { background: var(--primary-hover); transform: scale(1.06); }
    .dots { display: flex; align-items: center; gap: 6px; }
    .dot { width: 7px; height: 7px; border-radius: 50%; border: none; background: var(--tint-300); cursor: pointer; padding: 0; transition: all .25s ease; }
    .dot.active { width: 22px; border-radius: 999px; background: var(--primary); }

    /* ===== SECTIONS ===== */
    .container { max-width: 1200px; margin: 0 auto; padding: 0 32px; }

    .section-heading { text-align: center; margin-bottom: 40px; }
    .section-heading h2 { font-size: clamp(28px, 3.2vw, 38px); font-weight: 800; letter-spacing: -.03em; color: #18181b; margin-bottom: 10px; }
    .section-heading p { font-size: 16px; color: var(--text-body); }

    .features { padding: 40px 0 70px; }
    .feature-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 24px; max-width: 1080px; margin: 0 auto; }
    .feature-card {
      flex: 0 0 calc((100% - 48px) / 3); background: #fff; border: 1px solid #eef0f4;
      border-radius: 24px; padding: 28px; min-height: 190px;
      transition: transform .2s ease, box-shadow .2s ease;
    }
    .feature-card:hover { transform: translateY(-3px); box-shadow: 0 16px 32px rgba(15,23,42,.07); }
    .feature-icon { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; margin-bottom: 18px; }
    .feature-icon svg { width: 22px; height: 22px; }
    .feature-card h3 { font-size: 19px; font-weight: 700; color: #18181b; margin-bottom: 8px; letter-spacing: -.01em; }
    .feature-card p { font-size: 15px; line-height: 1.6; color: var(--text-body); }

    .steps { padding: 0 0 70px; }
    .steps-band { background: var(--primary); border-radius: 36px; padding: 56px 48px; color: #fff; }
    .steps-band h2 { text-align: center; font-size: clamp(28px, 3.2vw, 38px); font-weight: 800; letter-spacing: -.03em; margin-bottom: 40px; }
    .steps-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .step-card { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18); border-radius: 22px; padding: 24px; }
    .step-num {
      width: 40px; height: 40px; border-radius: 50%; background: #fff; color: var(--primary);
      display: grid; place-items: center; font-weight: 800; font-size: 15px; margin-bottom: 16px;
    }
    .step-card h3 { font-size: 19px; font-weight: 700; margin-bottom: 8px; }
    .step-card p { font-size: 15px; line-height: 1.6; color: rgba(255,255,255,.85); }

    .privacy { padding: 0 0 70px; }
    .privacy-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .privacy-card { background: #fff; border: 1px solid #eef0f4; border-radius: 22px; padding: 28px 32px; }
    .privacy-card h3 { font-size: 19px; font-weight: 700; color: #18181b; margin-bottom: 8px; }
    .privacy-card p { font-size: 15px; line-height: 1.6; color: var(--text-body); }

    .cta { padding: 0 0 80px; }
    .cta-card { background: #fff; border: 1px solid #eef0f4; border-radius: 36px; padding: 72px 32px; text-align: center; }
    .cta-card h2 { font-size: clamp(30px, 4vw, 44px); font-weight: 800; letter-spacing: -.03em; line-height: 1.15; color: #18181b; max-width: 640px; margin: 0 auto 16px; }
    .cta-card p { font-size: 16px; line-height: 1.6; color: var(--text-body); max-width: 480px; margin: 0 auto 28px; }

    .footer { border-top: 1px solid #eceff4; background: #fff; }
    .footer-inner { max-width: 1200px; margin: 0 auto; padding: 22px 32px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; font-size: 13px; color: var(--text-muted); }
    .footer-inner nav { display: flex; gap: 24px; }
    .footer-inner a { color: var(--text-muted); text-decoration: none; transition: color .15s ease; }
    .footer-inner a:hover { color: var(--primary); }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 960px) {
      .hero { grid-template-columns: 1fr; text-align: center; padding-top: 44px; }
      .hero-subtitle { margin-left: auto; margin-right: auto; }
      .hero-actions, .hero-meta { justify-content: center; }
      .hero-visual { margin-top: 16px; }
      .feature-card { flex-basis: calc((100% - 24px) / 2); }
      .steps-grid { grid-template-columns: 1fr; }
      .steps-band { padding: 40px 24px; }
      .privacy-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
      .nav-links { display: none; }
      .nav-container { padding: 16px 20px; }
      .hero { padding: 36px 20px 64px; }
      .container { padding: 0 20px; }
      .feature-card { flex-basis: 100%; }
      .pill-coffee { left: -10px; }
      .pill-school { right: -10px; }
      .pill-savings { left: -10px; }
      .footer-inner { padding: 20px; justify-content: center; }
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <header class="navbar">
    <div class="nav-container">
      <a href="/" class="logo">
        @if($appSettings->logo_path)
          <img src="{{ \Illuminate\Support\Facades\Storage::url($appSettings->logo_path) }}" alt="{{ $appSettings->application_name }}">
        @else
          <x-app-logo class="app-logo" />
        @endif
        <span>{{ $appSettings->application_name }}</span>
      </a>

      <nav>
        <ul class="nav-links">
          <li><a href="#features">Features</a></li>
          <li><a href="#how-it-works">How it works</a></li>
          <li><a href="#privacy">Privacy</a></li>
        </ul>
      </nav>

      <a href="{{ route('login') }}" class="btn-login">Log in</a>
    </div>
  </header>

  <!-- HERO -->
  <main class="hero">
    <div class="hero-content">
      <span class="badge">Made for weekly allowances, not monthly paychecks</span>

      <h1 class="hero-title">
        Know what’s safe<br>
        to spend, <span class="highlight">every<br>day this week.</span>
      </h1>

      <p class="hero-subtitle">
        Track expenses, predict your spending, and build better money habits in one friendly place.
      </p>

      <div class="hero-actions">
        <a href="{{ route('register') }}" class="btn-primary">Get started free</a>
        <a href="#how-it-works" class="btn-secondary">See how it works</a>
      </div>

      <div class="hero-meta">
        <span>Free to use</span><span>&middot;</span>
        <span>No bank connection needed</span><span>&middot;</span>
        <span>Set up in 10 seconds</span>
      </div>
    </div>

    <div class="hero-visual">
      <div class="showcase-card" id="showcaseCard">
        <div class="dot-grid" aria-hidden="true"></div>
        <span class="sparkle sparkle-left" aria-hidden="true">&#10022;</span>
        <span class="sparkle sparkle-right" aria-hidden="true">&#10022;</span>

        <div class="coin coin-top-left" aria-hidden="true"></div>
        <div class="coin coin-top-right" aria-hidden="true"></div>
        <div class="coin coin-bottom-right" aria-hidden="true"></div>

        <div class="floating-pill pill-coffee" data-slide-target="0" role="button" tabindex="0" aria-label="View coffee expense">
          <div class="pill-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg>
          </div>
          <div class="pill-content">
            <span class="pill-tag">Expense</span>
            <span class="pill-value">₱120 Coffee</span>
          </div>
        </div>

        <div class="floating-pill pill-school" data-slide-target="1" role="button" tabindex="0" aria-label="View school receipt scan">
          <div class="pill-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
          </div>
          <div class="pill-content">
            <span class="pill-tag">School</span>
            <span class="pill-value">₱450 Books</span>
          </div>
        </div>

        <div class="floating-pill pill-savings" data-slide-target="3" role="button" tabindex="0" aria-label="View savings goal check">
          <div class="ring-chart"><span>70%</span></div>
          <div class="pill-content">
            <span class="pill-tag">Savings goal</span>
            <span class="pill-value">70% saved</span>
          </div>
        </div>

        <div class="phone-frame">
          <div class="slides-viewport">

            <div class="phone-slide active" data-caption="Your safe-to-spend number">
              <div class="phone-day">Monday</div>
              <div class="budget-card">
                <div class="budget-label">Safe to spend today</div>
                <div class="budget-amount">₱300</div>
                <div class="progress-track"><div class="progress-fill"></div></div>
                <div class="budget-sub">₱930 left of ₱1,500</div>
              </div>
              <div class="section-label">Recent expenses</div>
              <div class="expense-list">
                <div class="expense-row"><span>Coffee</span><span>₱120</span></div>
                <div class="expense-row"><span>School books</span><span>₱450</span></div>
              </div>
              <div class="status-banner">On track: you’ll reach Sunday with ₱120 to spare</div>
            </div>

            <div class="phone-slide" data-caption="Log a receipt in seconds">
              <div class="phone-day">Scan a receipt</div>
              <div class="receipt-dropzone">
                <div class="skeleton-line w-65"></div>
                <div class="skeleton-line w-85"></div>
                <div class="skeleton-line w-55"></div>
                <div class="skeleton-line w-45"></div>
              </div>
              <div class="section-label green">Filled in for you</div>
              <div class="field-list">
                <div class="field-row"><span class="field-key">Store</span><span class="field-val">Campus Bookstore</span></div>
                <div class="field-row"><span class="field-key">Amount</span><span class="field-val">₱450</span></div>
                <div class="field-row"><span class="field-key">Category</span><span class="field-val">School</span></div>
              </div>
            </div>

            <div class="phone-slide" data-caption="See trouble before it happens">
              <div class="phone-day">This week</div>
              <div class="bar-chart">
                <div class="bar-col"><div class="bar solid" style="height:56px"></div><span>M</span></div>
                <div class="bar-col"><div class="bar solid" style="height:82px"></div><span>T</span></div>
                <div class="bar-col"><div class="bar solid" style="height:66px"></div><span>W</span></div>
                <div class="bar-col"><div class="bar predicted" style="height:100px"></div><span>T</span></div>
                <div class="bar-col"><div class="bar predicted" style="height:116px"></div><span>F</span></div>
                <div class="bar-col"><div class="bar warning" style="height:132px"></div><span>S</span></div>
                <div class="bar-col"><div class="bar predicted" style="height:92px"></div><span>S</span></div>
              </div>
              <div class="amber-banner">At this pace you may overspend on Saturday. Try keeping Thursday under ₱200.</div>
              <div class="chart-legend">Solid = spent &middot; Light = predicted</div>
            </div>

            <div class="phone-slide" data-caption="Try before you buy">
              <div class="phone-day">Can I afford it?</div>
              <div class="item-input-box"><span>Sneakers</span><span>₱850</span></div>
              <div class="stat-grid">
                <div class="stat-box gray"><span class="stat-label">Left now</span><span class="stat-value">₱930</span></div>
                <div class="stat-box amber"><span class="stat-label">After buying</span><span class="stat-value">₱80</span></div>
              </div>
              <div class="amber-banner">You can afford it, but only ₱80 would be left for the next 4 days.</div>
              <button type="button" class="phone-cta-btn">Add to my plan</button>
            </div>

          </div>
        </div>

        <div class="showcase-footer">
          <div class="showcase-caption" id="showcaseCaption">Your safe-to-spend number</div>
          <div class="carousel-controls">
            <button type="button" class="arrow-btn prev" id="prevBtn" aria-label="Previous slide">&#8249;</button>
            <div class="dots" id="dotsContainer">
              <button type="button" class="dot active" data-index="0" aria-label="Slide 1"></button>
              <button type="button" class="dot" data-index="1" aria-label="Slide 2"></button>
              <button type="button" class="dot" data-index="2" aria-label="Slide 3"></button>
              <button type="button" class="dot" data-index="3" aria-label="Slide 4"></button>
            </div>
            <button type="button" class="arrow-btn next" id="nextBtn" aria-label="Next slide">&#8250;</button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- FEATURES -->
  <section id="features" class="features">
    <div class="container">
      <div class="section-heading">
        <h2>Everything you need to feel in control</h2>
        <p>Powerful tools wrapped in an interface that never makes money feel scary.</p>
      </div>

      <div class="feature-grid">
        <div class="feature-card">
          <div class="feature-icon" style="background:var(--tint-50);color:var(--primary)">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <h3>AI Receipt Scanner</h3>
          <p>Log a purchase in seconds. Snap a receipt and the store, date, amount and category fill in for you.</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon" style="background:#fff7ed;color:#ea580c">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
          </div>
          <h3>Spending Forecast</h3>
          <p>Know before the weekend whether you’ll overspend, with simple predictions based on your own habits.</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon" style="background:#ecfdf5;color:#059669">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <h3>Savings Goals</h3>
          <p>Set a goal, save a little each day, and celebrate every milestone without the stress.</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon" style="background:#fffbeb;color:#d97706">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
          </div>
          <h3>Purchase Simulator</h3>
          <p>Wondering if you can afford it? Try the what-if before you buy and see how your week changes.</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon" style="background:#faf5ff;color:#9333ea">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          </div>
          <h3>Smart Nudges</h3>
          <p>Gentle reminders that keep your spending balanced, right when it matters.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section id="how-it-works" class="steps">
    <div class="container">
      <div class="steps-band">
        <h2>Up and running in 3 easy steps</h2>
        <div class="steps-grid">
          <div class="step-card">
            <div class="step-num">1</div>
            <h3>Set your allowance</h3>
            <p>Tell us your weekly budget. It takes 10 seconds and no bank connection is needed.</p>
          </div>
          <div class="step-card">
            <div class="step-num">2</div>
            <h3>Log expenses fast</h3>
            <p>Snap a receipt or tap a category. Logging takes seconds, not minutes.</p>
          </div>
          <div class="step-card">
            <div class="step-num">3</div>
            <h3>Stay on track</h3>
            <p>See your daily safe-to-spend number and build the habit.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- PRIVACY -->
  <section id="privacy" class="privacy">
    <div class="container">
      <div class="privacy-grid">
        <div class="privacy-card">
          <h3>Your data, your control</h3>
          <p>Your budgeting information is managed through your own account. No bank account connection is required.</p>
        </div>
        <div class="privacy-card">
          <h3>Review before saving</h3>
          <p>Check and correct the details extracted from your receipts before adding them to your expense records.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="cta">
    <div class="container">
      <div class="cta-card">
        <h2>Ready to take control of your allowance?</h2>
        <p>Set your allowance, track what matters, and always know what’s safe to spend, one week at a time.</p>
        <a href="{{ route('register') }}" class="btn-primary">Get started free</a>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="footer-inner">
      <span>&copy; {{ $appSettings->application_name }}. {{ now()->year }}</span>
      <nav>
        <a href="#privacy">Privacy</a>
      </nav>
    </div>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var slides   = document.querySelectorAll('.phone-slide');
      var dots     = document.querySelectorAll('.dot');
      var pills    = document.querySelectorAll('.floating-pill[data-slide-target]');
      var captionEl = document.getElementById('showcaseCaption');
      var current  = 0;

      function goTo(index) {
        current = (index + slides.length) % slides.length;
        slides.forEach(function (s, i) { s.classList.toggle('active', i === current); });
        dots.forEach(function (d, i) { d.classList.toggle('active', i === current); });
        var caption = slides[current].getAttribute('data-caption');
        if (caption && captionEl) captionEl.textContent = caption;
      }

      document.getElementById('prevBtn').addEventListener('click', function () { goTo(current - 1); });
      document.getElementById('nextBtn').addEventListener('click', function () { goTo(current + 1); });

      dots.forEach(function (dot) {
        dot.addEventListener('click', function () { goTo(Number(dot.getAttribute('data-index'))); });
      });

      pills.forEach(function (pill) {
        var jump = function () { goTo(Number(pill.getAttribute('data-slide-target'))); };
        pill.addEventListener('click', jump);
        pill.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); jump(); }
        });
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft') goTo(current - 1);
        else if (e.key === 'ArrowRight') goTo(current + 1);
      });
    });
  </script>
</body>
</html>