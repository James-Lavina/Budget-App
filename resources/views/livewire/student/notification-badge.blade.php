<div>
    @if($unreadCount > 0)
        <span class="bg-rose-500 text-white text-[11px] font-extrabold px-2 py-0.5 rounded-full">
            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
        </span>
    @endif
</div>