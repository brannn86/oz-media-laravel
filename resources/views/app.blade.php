<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @php($meta = $page['props']['meta'] ?? null)
        @if (is_array($meta))
            {{-- Server-rendered so crawlers and link previews see them without SSR. --}}
            @if (filled($meta['description'] ?? null))
                <meta name="description" content="{{ $meta['description'] }}">
                <meta property="og:description" content="{{ $meta['description'] }}">
            @endif
            <meta property="og:title" content="{{ $meta['title'] }}">
            <meta property="og:type" content="{{ $meta['type'] ?? 'website' }}">
            <meta property="og:url" content="{{ $meta['url'] ?? request()->url() }}">
            <meta property="og:site_name" content="OZ Media">
            @if (filled($meta['image'] ?? null))
                <meta property="og:image" content="{{ $meta['image'] }}">
                <meta name="twitter:card" content="summary_large_image">
                <meta name="twitter:image" content="{{ $meta['image'] }}">
            @else
                <meta name="twitter:card" content="summary">
            @endif
            <meta name="twitter:title" content="{{ $meta['title'] }}">
            @if (filled($meta['published_at'] ?? null))
                <meta property="article:published_time" content="{{ $meta['published_at'] }}">
            @endif
            <link rel="canonical" href="{{ $meta['url'] ?? request()->url() }}">
        @endif

        @php($umami = config('oz.analytics.umami'))
        @if (filled($umami['script_url'] ?? null) && filled($umami['website_id'] ?? null) && str($page['component'] ?? '')->startsWith(['station/', 'corporate/']))
            {{-- Self-hosted Umami: cookieless page analytics for the public site only. --}}
            <script defer src="{{ $umami['script_url'] }}" data-website-id="{{ $umami['website_id'] }}"></script>
        @endif

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ is_array($meta) ? $meta['title'] : config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
