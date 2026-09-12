@props(['route'])
@php $active = request()->routeIs($route); @endphp
<a href="{{ route($route) }}"
    {{ $attributes->merge([
        'class' => 'flex items-center gap-3 px-4 py-3 rounded-2xl transition-all '
            . ($active
                ? 'bg-[var(--brand)] text-white shadow-md shadow-[0_4px_12px_-2px_rgba(var(--brand-rgb),0.35)] font-bold text-sm'
                : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70 font-semibold text-sm')
    ]) }}>
    {{ $slot }}
</a>