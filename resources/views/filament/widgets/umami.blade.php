<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Website analytics (Umami)</x-slot>

        @if ($url = $this->shareUrl())
            <x-slot name="afterHeader">
                <x-filament::link :href="$url" target="_blank" icon="heroicon-m-arrow-top-right-on-square" size="sm">
                    Open in Umami
                </x-filament::link>
            </x-slot>
            <iframe
                src="{{ $url }}"
                title="Umami website analytics"
                class="h-[42rem] w-full rounded-lg border-0 bg-white"
                loading="lazy"
                referrerpolicy="no-referrer"
                sandbox="allow-scripts allow-same-origin allow-popups"
            ></iframe>
        @else
            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <p>Connect your self-hosted Umami to see page views, visitors and top pages here.</p>
                <ol class="list-decimal space-y-1 pl-5">
                    <li>In Umami, open the OZ website, then <strong>Edit &rarr; Share URL</strong> and enable it.</li>
                    <li>Put that link in <code>UMAMI_SHARE_URL</code> in <code>.env</code>.</li>
                    <li>To start tracking, also set <code>UMAMI_SCRIPT_URL</code> (e.g. https://umami.example.com/script.js) and <code>UMAMI_WEBSITE_ID</code>.</li>
                </ol>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
