@extends('layouts.app')

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto ml-3">Notifications</h2>
        @php
            $unread = ($topbarNotifications['unreadCount'] ?? 0);
        @endphp
        @if ($unread > 0)
            <form method="POST" action="{{ route('notifications.mark-all-read') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i data-lucide="check-check" class="w-4 h-4 mr-2"></i>
                    Mark all read ({{ $unread }})
                </button>
            </form>
        @endif
    </div>

    <div class="intro-y mt-5">
        {{-- Tab filter — `all`, `unread`, `mentions` (matches mobile §11.14.1) --}}
        <nav class="flex flex-wrap gap-2 mb-5">
            @foreach (['all' => 'All', 'unread' => 'Unread', 'mentions' => 'Mentions'] as $key => $label)
                @php
                    $isActive = ($activeTab ?? 'all') === $key;
                    $url = $key === 'all' ? route('notifications.index') : route('notifications.index', ['tab' => $key]);
                @endphp
                <a href="{{ $url }}"
                   class="px-4 py-2 rounded-md text-sm {{ $isActive ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-darkmode-400 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        @php
            $hasAny = collect($payload['sections'])->sum(fn ($s) => count($s['notifications']));
        @endphp

        @if (! $hasAny)
            <div class="box p-10 text-center text-slate-400">
                <i data-lucide="bell-off" class="w-10 h-10 mx-auto mb-3"></i>
                <div class="text-base">No notifications in this view.</div>
                @if (($activeTab ?? 'all') !== 'all')
                    <a href="{{ route('notifications.index') }}" class="text-primary text-sm hover:underline">Show all</a>
                @endif
            </div>
        @endif

        @foreach ($payload['sections'] as $section)
            @if (count($section['notifications']) === 0)
                @continue
            @endif

            <div class="box mb-5">
                <div class="flex items-center px-5 py-3 border-b border-slate-200/60 dark:border-darkmode-400">
                    <h3 class="font-medium">{{ $section['label'] }}</h3>
                    <span class="ml-2 text-xs text-slate-400">({{ count($section['notifications']) }})</span>
                </div>

                <div class="divide-y divide-slate-200/60 dark:divide-darkmode-400">
                    @foreach ($section['notifications'] as $item)
                        <a href="{{ $item['followUrl'] }}"
                           class="flex items-start px-5 py-4 hover:bg-slate-50 dark:hover:bg-darkmode-500/30 {{ $item['isUnread'] ? 'bg-primary/5' : '' }}">
                            <div class="w-10 h-10 flex-none flex items-center justify-center rounded-full bg-slate-100 dark:bg-darkmode-400 mr-3">
                                <i data-lucide="{{ $item['iconKey'] }}" class="w-5 h-5 {{ $item['isUnread'] ? 'text-primary' : 'text-slate-500' }}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center">
                                    <div class="font-medium {{ $item['isUnread'] ? 'text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300' }}">
                                        {{ $item['title'] }}
                                        @if ($item['isUnread'])
                                            <span class="ml-2 inline-block w-2 h-2 bg-primary rounded-full align-middle"></span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-400 ml-auto whitespace-nowrap">{{ $item['timeAgoLabel'] }}</div>
                                </div>
                                <div class="text-sm text-slate-500 mt-1">{{ $item['body'] }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endsection
