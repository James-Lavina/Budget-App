@props(['type' => 'default'])

@if($type === 'food')
    {{-- No food/dining icon exists in the installed Heroicons v1 set —
         kept as a manual inline SVG rather than a component. --}}
    <svg {{ $attributes->merge(['class' => 'w-3.5 h-3.5']) }} fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.871c1.355 0 2.697.056 4.024.166C17.155 8.51 18 9.473 18 10.608v2.513M15 8.25v-1.5m-6 1.5v-1.5m12 9.75-1.5.75m-15-.75 1.5.75m12-3.75h3m-18 0h3m0 0v2.25c0 .414.336.75.75.75h10.5a.75.75 0 0 0 .75-.75V12M6 12a2.25 2.25 0 0 0-2.25 2.25v.75c0 .414.336.75.75.75h15a.75.75 0 0 0 .75-.75v-.75A2.25 2.25 0 0 0 18 12H6Z" />
    </svg>
@else
    {{-- $type now stores the REAL Heroicon name directly (e.g. 'truck',
         'cash', 'heart') rather than a custom key needing translation.
         If it's ever invalid (bad data, icon removed from the package),
         fall back to document-text instead of throwing. --}}
    <x-dynamic-component
        :component="'heroicon-o-' . (\App\Support\HeroiconRegistry::exists($type) ? $type : 'document-text')"
        {{ $attributes->merge(['class' => 'w-3.5 h-3.5']) }}
    />
@endif