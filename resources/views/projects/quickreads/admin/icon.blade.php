<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('dashboard')
        <rect x="3" y="3" width="7" height="7" rx="1.5"/>
        <rect x="14" y="3" width="7" height="7" rx="1.5"/>
        <rect x="3" y="14" width="7" height="7" rx="1.5"/>
        <rect x="14" y="14" width="7" height="7" rx="1.5"/>
        @break
        @case('statistics')
        <path d="M4 3v17h17M8 15l4-5 4 2 5-7"/>
        @break
        @case('story-activity')
        <path d="M3 5h18M3 12h6M3 19h6m4-6 3 3 5-7"/>
        @break
        @case('stories')
        <path d="M12 5v15M12 5C9 3 5 3 2 4v15c3-1 7-1 10 1 3-2 7-2 10-1V4c-3-1-7-1-10 1Z"/>
        @break
        @case('users')
        <circle cx="9" cy="8" r="3"/>
        <path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6M21 21v-3a6 6 0 0 0-4-5.65"/>
        @break
        @case('authors')
        <path d="m16 3 5 5-12 12H4v-5L16 3ZM13 6l5 5M3 22h19"/>
        @break
        @case('categories')
        <rect x="3" y="4" width="18" height="16" rx="2"/>
        <path d="M3 9h18M9 9v11"/>
        @break
        @case('explore')
        <circle cx="12" cy="12" r="9"/>
        <path d="m16 8-2.5 5.5L8 16l2.5-5.5L16 8Z"/>
        @break
        @case('plus')
        <path d="M12 5v14M5 12h14"/>
        @break
        @case('search')
        <circle cx="10.5" cy="10.5" r="6.5"/>
        <path d="m16 16 5 5"/>
        @break
        @case('arrow')
        <path d="M5 12h14m-5-5 5 5-5 5"/>
        @break
        @case('logout')
        <path d="M9 4H4v16h5M10 12h11m-4-4 4 4-4 4"/>
        @break
        @case('check')
        <path d="m5 12 4 4L20 5"/>
        @break
        @case('menu')
        <path d="M4 6h16M4 12h16M4 18h16"/>
        @break
        @case('clock')
        <circle cx="12" cy="12" r="9"/>
        <path d="M12 7v5l3 2"/>
        @break
        @case('bookmark')
        <path d="M6 3h12v18l-6-4-6 4V3Z"/>
        @break
        @default
        <circle cx="12" cy="12" r="9"/>
        <path d="M12 8v5m0 3h.01"/>
    @endswitch
</svg>
