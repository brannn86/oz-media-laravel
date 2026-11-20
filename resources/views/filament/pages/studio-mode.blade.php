<x-filament-panels::page>
    @php
        $broadcast = $this->broadcast;
        $count = $this->listenerCount;
        $pinned = $this->pinned;
    @endphp

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        {{-- Chat feed --}}
        <x-filament::section class="studio-feed" compact>
            <x-slot name="heading">Live chat</x-slot>
            <x-slot name="description">
                {{ $this->messages->count() }} recent messages. New ones appear instantly.
            </x-slot>

            @if ($pinned)
                <div class="mb-4 flex items-start gap-3 rounded-xl border border-warning-300 bg-warning-50 p-3 dark:border-warning-500/40 dark:bg-warning-500/10">
                    <x-filament::icon icon="heroicon-m-map-pin" class="mt-0.5 h-5 w-5 shrink-0 text-warning-600 dark:text-warning-400" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $pinned->user?->name }}</p>
                        <p class="text-sm text-gray-700 dark:text-gray-200">{{ $pinned->body }}</p>
                    </div>
                    {{ ($this->pinAction)(['message' => $pinned->id]) }}
                </div>
            @endif

            <div
                x-data="{ stick: true }"
                x-init="
                    $el.scrollTop = $el.scrollHeight;
                    new MutationObserver(() => { if (stick) $el.scrollTop = $el.scrollHeight }).observe($el, { childList: true, subtree: true });
                "
                x-on:scroll.passive="stick = $el.scrollTop + $el.clientHeight >= $el.scrollHeight - 48"
                class="h-[min(68vh,44rem)] overflow-y-auto pr-1"
                role="log"
                aria-live="polite"
                aria-label="Live chat messages"
            >
                @forelse ($this->messages as $message)
                    <div
                        wire:key="message-{{ $message->id }}"
                        @class([
                            'group flex items-start gap-3 rounded-lg px-2 py-2.5 hover:bg-gray-50 dark:hover:bg-white/5',
                            'opacity-60' => $message->user?->isChatMuted(),
                        ])
                    >
                        @if ($message->user?->avatar_url)
                            <img src="{{ $message->user->avatar_url }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover">
                        @else
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gray-900 text-sm font-bold text-primary-400 dark:bg-white/10">
                                {{ mb_strtoupper(mb_substr($message->user?->name ?? '?', 0, 1)) }}
                            </span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-x-2 text-sm">
                                <span class="font-semibold text-gray-950 dark:text-white">{{ $message->user?->name }}</span>
                                @if ($message->user?->isStaff())
                                    <x-filament::badge size="sm" color="primary">OZ</x-filament::badge>
                                @endif
                                @if ($message->user?->isChatMuted())
                                    <x-filament::badge size="sm" color="gray">Muted</x-filament::badge>
                                @endif
                                @if ($message->is_pinned)
                                    <x-filament::badge size="sm" color="warning">Pinned</x-filament::badge>
                                @endif
                                <time class="text-xs text-gray-500 dark:text-gray-400" datetime="{{ $message->created_at?->toIso8601String() }}">
                                    {{ $message->created_at?->setTimezone('Asia/Jakarta')->format('H:i') }}
                                </time>
                            </p>
                            <p class="mt-0.5 break-words text-base leading-snug text-gray-800 dark:text-gray-100">{{ $message->body }}</p>
                        </div>

                        <div class="flex shrink-0 items-center gap-0.5">
                            {{ ($this->pinAction)(['message' => $message->id]) }}
                            @unless ($message->user?->isStaff())
                                {{ ($this->muteAction)(['message' => $message->id]) }}
                            @endunless
                            {{ ($this->deleteMessageAction)(['message' => $message->id]) }}
                        </div>
                    </div>
                @empty
                    <div class="grid h-full place-items-center text-center text-gray-500 dark:text-gray-400">
                        <p>No messages yet. They will show up here as listeners chat.</p>
                    </div>
                @endforelse
            </div>
        </x-filament::section>

        {{-- Monitor column --}}
        <div class="flex flex-col gap-6">
            <x-filament::section compact wire:poll.5s>
                <x-slot name="heading">Listening now</x-slot>
                <p class="text-6xl font-black tabular-nums tracking-tight text-gray-950 dark:text-white">
                    {{ $count === null ? '–' : number_format($count) }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if ($count === null)
                        Can't reach Reverb. Is <code>php artisan reverb:start</code> running?
                    @else
                        Unique listeners with the player on. Updates every few seconds.
                    @endif
                </p>
            </x-filament::section>

            <x-filament::section compact>
                <x-slot name="heading">On air</x-slot>
                @if ($broadcast->current)
                    <p class="text-xl font-bold text-gray-950 dark:text-white">{{ $broadcast->current->program->title }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $broadcast->currentStartsAt?->format('H:i') }}–{{ $broadcast->currentEndsAt?->format('H:i') }} WIB
                        @if ($broadcast->current->program->announcers->isNotEmpty())
                            with {{ $broadcast->current->program->announcers->map->displayName()->join(' & ') }}
                        @endif
                    </p>
                @else
                    <p class="text-gray-500 dark:text-gray-400">Nothing scheduled right now.</p>
                @endif
                @if ($broadcast->next)
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                        Next: <span class="font-semibold">{{ $broadcast->next->program->title }}</span>
                        at {{ $broadcast->nextStartsAt?->format('H:i') }}
                    </p>
                @endif
                <div class="mt-4">
                    <x-filament::badge :color="$station->is_video_live ? 'danger' : 'gray'" :icon="$station->is_video_live ? 'heroicon-m-video-camera' : 'heroicon-m-radio'">
                        {{ $station->is_video_live ? 'Video livestream' : 'Audio only' }}
                    </x-filament::badge>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Switch with the button at the top of the page.</p>
                </div>
            </x-filament::section>

            <x-filament::section compact>
                <x-slot name="heading">Slow mode</x-slot>
                <x-slot name="description">Minimum time between messages from each listener.</x-slot>
                <div class="flex flex-wrap gap-2" role="group" aria-label="Slow mode">
                    @foreach ($this->slowModeOptions() as $seconds)
                        <x-filament::button
                            size="sm"
                            :color="$station->chat_slow_mode_seconds === $seconds ? 'primary' : 'gray'"
                            :outlined="$station->chat_slow_mode_seconds !== $seconds"
                            wire:click="setSlowMode({{ $seconds }})"
                            :aria-pressed="$station->chat_slow_mode_seconds === $seconds ? 'true' : 'false'"
                        >
                            {{ $seconds === 0 ? 'Off' : $seconds.'s' }}
                        </x-filament::button>
                    @endforeach
                </div>
            </x-filament::section>

            <x-filament::section compact>
                <x-slot name="heading">Mute length</x-slot>
                <x-slot name="description">Used by the mute button on each message.</x-slot>
                <div class="flex flex-wrap gap-2" role="group" aria-label="Mute length">
                    @foreach ($this->muteOptions() as $minutes)
                        <x-filament::button
                            size="sm"
                            :color="$muteMinutes === $minutes ? 'primary' : 'gray'"
                            :outlined="$muteMinutes !== $minutes"
                            wire:click="setMuteMinutes({{ $minutes }})"
                        >
                            {{ $this->muteLabel($minutes) }}
                        </x-filament::button>
                    @endforeach
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
