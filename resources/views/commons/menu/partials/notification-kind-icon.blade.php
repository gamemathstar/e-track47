{{--
    Inline SVG icon picker for the notification kind. Inlined (not data-lucide)
    so the icon renders even if Lucide's JS hasn't initialised on the host
    page. All paths sourced from Lucide:
        approval     → check-circle
        rejection    → x-circle
        submission   → upload-cloud
        discussion   → message-square
        deadline     → clock
        mention      → at-sign
        default      → bell

    Expected vars:
      $kind     string  notification kind
      $isUnread bool    drives the colour (primary if unread, slate-500 if read)
--}}
@php
    $iconColor = ($isUnread ?? false) ? 'text-primary' : 'text-slate-500';
@endphp
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
     class="w-5 h-5 {{ $iconColor }}" aria-hidden="true">
@switch($kind)
    @case('approval')
        <circle cx="12" cy="12" r="10"></circle>
        <path d="m9 12 2 2 4-4"></path>
        @break
    @case('rejection')
        <circle cx="12" cy="12" r="10"></circle>
        <path d="m15 9-6 6"></path>
        <path d="m9 9 6 6"></path>
        @break
    @case('submission')
        <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"></path>
        <path d="M12 12v9"></path>
        <path d="m16 16-4-4-4 4"></path>
        @break
    @case('discussion')
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        @break
    @case('deadline')
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
        @break
    @case('mention')
        <circle cx="12" cy="12" r="4"></circle>
        <path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"></path>
        @break
    @default
        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
@endswitch
</svg>
