@php
  $chartLabels    = $forecastResult['chart']['labels'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  $chartActual    = $forecastResult['chart']['actual'] ?? [];
  $chartPredicted = $forecastResult['chart']['predicted'] ?? [];
  $chartAllowance = $forecastResult['chart']['allowance'] ?? 1000;
  $isOffline      = !($forecastResult['is_online'] ?? false) || str_contains($forecastResult['source'] ?? '', 'Offline');
  
  $metrics        = $forecastResult['metrics'] ?? [];
  $isCritical     = $metrics['is_critical'] ?? false;
  $isFaster       = $metrics['is_faster'] ?? false;
  $daysLeft       = $metrics['days_left_in_week'] ?? 0;
  $dailyVelocity  = $metrics['daily_velocity'] ?? '0.00';
@endphp

<div class="min-h-screen py-6 px-4 sm:px-6 lg:px-8">
  <div class="max-w-5xl mx-auto space-y-6">
    
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
          <div>
              <h2 class="text-2xl font-black text-slate-900 tracking-tight">Spending Forecast</h2>
              <p class="text-xs text-slate-500 font-medium mt-0.5">
                  A quick look at where your money is heading this week.
              </p>
          </div>
      </div>

      @if(($forecastResult['status'] ?? '') === 'error')
          <div class="p-4 bg-rose-50 border border-rose-100 rounded-2xl text-slate-800 text-xs font-semibold shadow-sm flex items-center gap-2.5">
              <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <span>{{ $forecastResult['message'] ?? 'An error occurred while loading the forecast.' }}</span>
          </div>
      @else

          <!-- Top 3 Metric & Status Cards -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            
              <!-- Card 1: Dynamic Status Card -->
              @if($isCritical)
                  <div class="bg-white border border-rose-400 ring-2 ring-rose-400/20 p-5 rounded-3xl shadow-sm transition-all">
                      <div class="h-8 w-8 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center font-bold mb-3">
                          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                          </svg>
                      </div>
                      <h3 class="text-base font-bold text-slate-900">Over Budget Warning</h3>
                      <p class="text-xs text-slate-500 font-medium mt-0.5">Projected to run out of money.</p>
                  </div>
              @elseif($isFaster)
                  <div class="bg-white border border-amber-400 ring-2 ring-amber-400/20 p-5 rounded-3xl shadow-sm transition-all">
                      <div class="h-8 w-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-bold mb-3">
                          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a.5.5 0 00.71 0l7.152-7.153M21 7.5v4.5m0-4.5h-4.5" />
                          </svg>
                      </div>
                      <h3 class="text-base font-bold text-slate-900">Spending Ahead of Pace</h3>
                      <p class="text-xs text-slate-500 font-medium mt-0.5">Slightly faster than planned.</p>
                  </div>
              @else
                  <div class="bg-white border border-emerald-400 ring-2 ring-emerald-400/20 p-5 rounded-3xl shadow-sm transition-all">
                      <div class="h-8 w-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold mb-3">
                          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                          </svg>
                      </div>
                      <h3 class="text-base font-bold text-slate-900">On Track</h3>
                      <p class="text-xs text-slate-500 font-medium mt-0.5">Well within your allowance.</p>
                  </div>
              @endif

              <!-- Card 2: Daily Pace -->
              <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm">
                  <div class="h-8 w-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold mb-3">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                      </svg>
                  </div>
                  <h3 class="text-base font-bold text-slate-900">₱{{ $dailyVelocity }} / day</h3>
                  <p class="text-xs text-slate-500 font-medium mt-0.5">Average daily pace</p>
              </div>

              <!-- Card 3: Allowance Timeline -->
              <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-sm">
                  <div class="h-8 w-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold mb-3">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                      </svg>
                  </div>
                  <h3 class="text-base font-bold text-slate-900">
                      {{ $daysLeft === 0 ? 'Week Complete' : $daysLeft . ' Day' . ($daysLeft > 1 ? 's' : '') . ' Left' }}
                  </h3>
                  <p class="text-xs text-slate-500 font-medium mt-0.5">This week's allowance cycle</p>
              </div>

          </div>

          <!-- Main Weekly Spending Trend Graph -->
          <div class="bg-white p-6 sm:p-7 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
              <div class="flex items-center justify-between">
                  <h3 class="text-lg font-extrabold text-slate-900">Weekly Spending Trend</h3>
                  @if($isCritical)
                      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-600 border border-rose-100">
                          <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span> Over limit risk
                      </span>
                  @else
                      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-100">
                          <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Safe pace
                      </span>
                  @endif
              </div>

              <!-- Chart Canvas Container -->
              <div class="h-72 relative w-full pt-2" wire:ignore>
                  <canvas id="forecastTrajectoryChart"></canvas>
              </div>

              <!-- Custom Bottom Chart Legend -->
              <div class="flex items-center gap-6 pt-2 text-xs font-semibold text-slate-600">
                  <div class="flex items-center gap-2">
                      <span class="w-6 h-2 rounded-full bg-indigo-600 inline-block"></span>
                      <span>Spent so far</span>
                  </div>
                  <div class="flex items-center gap-2">
                      <span class="w-6 h-2 rounded-full bg-teal-400 border border-dashed border-teal-500 inline-block"></span>
                      <span>Predicted spending</span>
                  </div>
              </div>
          </div>

          <!-- Bottom Section: Remaining Budget Highlight & AI Insights -->
          <div class="grid grid-cols-1 md:grid-cols-5 gap-5">
            
              <!-- Left Indigo Highlight Card -->
              <div class="md:col-span-2 bg-indigo-600 text-white rounded-3xl p-7 flex flex-col justify-between shadow-lg shadow-indigo-600/10 min-h-[220px]">
                  <div class="space-y-1">
                      <span class="text-xs font-bold text-indigo-200 block uppercase tracking-wider">
                          {{ $daysLeft === 0 ? 'Final Balance Left' : 'Estimated Money Left' }}
                      </span>
                      <div class="text-4xl font-black tracking-tight font-mono my-1">
                          ₱{{ $metrics['predicted_remaining'] ?? '0' }}
                      </div>
                  </div>
                  <div class="text-xs text-indigo-200 font-medium">
                      {{ $daysLeft === 0 ? 'Cycle complete for this week' : 'by Sunday evening' }}
                  </div>
              </div>

              <!-- Right White Insights Card -->
              <div class="md:col-span-3 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                  <div>
                      <div class="flex items-center justify-between gap-2 mb-4">
                          <div class="flex items-center gap-2">
                              <div class="h-7 w-7 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.516 0c.85.493 1.508 1.333 1.508 2.316V18" />
                                  </svg>
                              </div>
                              <h3 class="text-base font-extrabold text-slate-900">Student Budget Tips</h3>
                          </div>

                          <!-- Connection Badge Indicator -->
                          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $isOffline ? 'bg-amber-50 text-amber-700 border border-amber-200/80' : 'bg-emerald-50 text-emerald-700 border border-emerald-200/80' }}">
                              <span class="w-1.5 h-1.5 rounded-full {{ $isOffline ? 'bg-amber-500' : 'bg-emerald-500 animate-pulse' }}"></span>
                              {{ $isOffline ? 'Offline Mode' : 'AI Active' }}
                          </span>
                      </div>

                      <!-- Insights Tips -->
                      <div class="space-y-3">
                          @if(!empty($forecastResult['ai_coach_text']))
                              @foreach(explode('|', $forecastResult['ai_coach_text']) as $tip)
                                  @php
                                      $cleanTip = trim(preg_replace('/^[\s\-\*•\d\.\)]+/', '', $tip));
                                  @endphp
                                  @if(!empty($cleanTip))
                                      <div class="p-3.5 bg-slate-50/80 border border-slate-100 rounded-2xl flex items-start gap-3">
                                          <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.516 0c.85.493 1.508 1.333 1.508 2.316V18" />
                                          </svg>
                                          <p class="text-xs font-semibold text-slate-600 leading-relaxed">
                                              {{ $cleanTip }}
                                          </p>
                                      </div>
                                  @endif
                              @endforeach
                          @endif
                      </div>
                  </div>

                  <!-- Cleaned Metadata Footer -->
                  <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                      <span class="flex items-center gap-1.5">
                          <span class="w-1.5 h-1.5 rounded-full {{ $isOffline ? 'bg-amber-400' : 'bg-emerald-400' }}"></span>
                          {{ $isOffline ? 'Standard Rules' : 'Smart Advice' }}
                      </span>
                      <span class="{{ $isOffline ? 'text-amber-500' : 'text-emerald-500' }}">
                          {{ $isOffline ? 'Offline' : 'Online' }}
                      </span>
                  </div>
              </div>

          </div>
      @endif
  </div>
</div>

<script>
  document.addEventListener('livewire:load', function () {
      const ctx = document.getElementById('forecastTrajectoryChart').getContext('2d');
      const allowanceAmount = {{ $chartAllowance }};
     
      // Plugin to render Allowance line label
      const allowanceLabelPlugin = {
          id: 'allowanceLabel',
          afterDraw(chart) {
              const { ctx, chartArea, scales } = chart;
              if (!chartArea) return;
              const yPos = scales.y.getPixelForValue(allowanceAmount);
              if (yPos >= chartArea.top && yPos <= chartArea.bottom) {
                  ctx.save();
                  ctx.font = 'bold 10px sans-serif';
                  ctx.fillStyle = '#f43f5e';
                  ctx.textAlign = 'right';
                  ctx.fillText('Allowance Limit', chartArea.right - 8, yPos - 8);
                  ctx.restore();
              }
          }
      };

      let trajectoryChart = new Chart(ctx, {
          type: 'line',
          plugins: [allowanceLabelPlugin],
          data: {
              labels: @json($chartLabels),
              datasets: [
                  {
                      label: 'Spent so far',
                      data: @json($chartActual),
                      borderColor: '#6366f1',
                      backgroundColor: '#6366f1',
                      borderWidth: 3,
                      tension: 0.35,
                      pointRadius: 5,
                      pointHoverRadius: 7,
                      pointBackgroundColor: '#6366f1',
                      pointBorderColor: '#ffffff',
                      pointBorderWidth: 2,
                      spanGaps: false
                  },
                  {
                      label: 'Predicted',
                      data: @json($chartPredicted),
                      borderColor: '#2dd4bf',
                      backgroundColor: '#2dd4bf',
                      borderWidth: 3,
                      borderDash: [6, 6],
                      tension: 0.35,
                      pointRadius: 4,
                      pointHoverRadius: 6,
                      pointBackgroundColor: '#2dd4bf',
                      pointBorderColor: '#ffffff',
                      pointBorderWidth: 2,
                      spanGaps: false
                  },
                  {
                      label: 'Allowance Limit',
                      data: Array(7).fill(allowanceAmount),
                      borderColor: '#f43f5e',
                      borderWidth: 1.5,
                      borderDash: [5, 5],
                      pointRadius: 0,
                      fill: false
                  }
              ]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                  legend: { display: false },
                  tooltip: {
                      padding: 10,
                      titleFont: { size: 11, weight: '700' },
                      bodyFont: { size: 12, weight: '600' },
                      callbacks: {
                          label: (ctx) => ` ${ctx.dataset.label}: ₱${ctx.raw ? ctx.raw.toLocaleString(undefined, {minimumFractionDigits: 2}) : 0}`
                      }
                  }
              },
              scales: {
                  x: {
                      grid: { display: false },
                      ticks: { font: { size: 11, weight: '600' }, color: '#94a3b8' }
                  },
                  y: {
                      grid: { color: '#f1f5f9' },
                      suggestedMax: allowanceAmount * 1.15,
                      ticks: {
                          font: { size: 10, weight: '600' },
                          color: '#94a3b8',
                          callback: (value) => value
                      }
                  }
              }
          }
      });

      window.addEventListener('renderForecastChart', event => {
          const data = event.detail;
          trajectoryChart.data.labels = data.labels;
          trajectoryChart.data.datasets[0].data = data.actual;
          trajectoryChart.data.datasets[1].data = data.predicted;
          trajectoryChart.data.datasets[2].data = Array(7).fill(data.allowance);
          trajectoryChart.options.scales.y.suggestedMax = data.allowance * 1.15;
          trajectoryChart.update();
      });
  });
</script>