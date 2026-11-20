<x-filament-panels::page>
    @php
        $broadcast = $this->broadcast;
        $count = $this->listenerCount;
        $pinned = $this->pinned;
    @endphp

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="grid min-w-0 gap-6 2xl:grid-cols-2">
        {{-- Chat feed --}}
        <x-filament::section class="studio-feed min-w-0" compact>
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

        {{-- Song request queue --}}
        <x-filament::section compact class="min-w-0">
            <x-slot name="heading">Song requests</x-slot>
            <x-slot name="description">
                {{ $this->songRequests->count() }} in the queue. Coming up first, then most upvoted.
            </x-slot>

            <ol class="h-[min(68vh,44rem)] space-y-2 overflow-y-auto pr-1" aria-label="Song request queue">
                @forelse ($this->songRequests as $songRequest)
                    <li
                        wire:key="request-{{ $songRequest->id }}"
                        @class([
                            'rounded-xl border p-3',
                            'border-success-300 bg-success-50 dark:border-success-500/40 dark:bg-success-500/10' => $songRequest->status === \App\Enums\SongRequestStatus::Approved,
                            'border-gray-200 dark:border-white/10' => $songRequest->status !== \App\Enums\SongRequestStatus::Approved,
                        ])
                    >
                        <div class="flex items-start gap-3">
                            <div class="grid w-12 shrink-0 place-items-center rounded-lg bg-gray-100 py-1 text-center dark:bg-white/5">
                                <span class="text-lg font-black tabular-nums text-gray-950 dark:text-white">{{ $songRequest->upvotes_count }}</span>
                                <span class="text-[0.65rem] text-gray-500 dark:text-gray-400">votes</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold leading-snug text-gray-950 dark:text-white">
                                    {{ $songRequest->song_title }}
                                    <span class="font-normal text-gray-500 dark:text-gray-400">· {{ $songRequest->artist }}</span>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    from {{ $songRequest->user?->name }}, {{ $songRequest->created_at?->setTimezone('Asia/Jakarta')->format('H:i') }}
                                    @if ($songRequest->status === \App\Enums\SongRequestStatus::Approved)
                                        <x-filament::badge size="sm" color="success" class="ml-1 inline-flex">Coming up</x-filament::badge>
                                    @endif
                                </p>
                                @if ($songRequest->shoutout_message)
                                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">“{{ $songRequest->shoutout_message }}”</p>
                                @endif
                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                                    @if ($songRequest->status === \App\Enums\SongRequestStatus::Pending)
                                        {{ ($this->approveRequestAction)(['request' => $songRequest->id]) }}
                                    @endif
                                    {{ ($this->playRequestAction)(['request' => $songRequest->id]) }}
                                    {{ ($this->rejectRequestAction)(['request' => $songRequest->id]) }}
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="grid h-full place-items-center text-center text-gray-500 dark:text-gray-400">
                        No requests yet. Listeners add them from the player.
                    </li>
                @endforelse
            </ol>
        </x-filament::section>
        </div>

        {{-- Monitor column --}}
        <div class="flex flex-col gap-6">
            @php($poll = $this->poll)
            <x-filament::section compact>
                <x-slot name="heading">
                    {{ $poll ? $poll->type->getLabel().($poll->isLive() ? ' (live)' : ' (closed)') : 'Live poll' }}
                </x-slot>
                @if ($poll)
                    @php($total = max(1, $poll->options->sum('votes_count')))
                    <p class="font-semibold text-gray-950 dark:text-white">{{ $poll->question }}</p>
                    <ul class="mt-3 space-y-2">
                        @foreach ($poll->options as $option)
                            @php($percent = (int) round($option->votes_count / $total * 100))
                            <li>
                                <div class="flex justify-between text-sm">
                                    <span @class(['font-semibold text-success-600 dark:text-success-400' => $option->is_correct, 'text-gray-800 dark:text-gray-100' => ! $option->is_correct])>
                                        {{ $option->label }} @if ($option->is_correct) ✓ @endif
                                    </span>
                                    <span class="tabular-nums text-gray-500 dark:text-gray-400">{{ $option->votes_count }} · {{ $percent }}%</span>
                                </div>
                                <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                    <div class="h-full rounded-full bg-primary-500 transition-all duration-500" style="width: {{ $percent }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-4">{{ $this->closePollAction }}</div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No poll running. Use "Launch a poll" at the top to start a song battle or trivia question.</p>
                @endif
            </x-filament::section>

            <x-filament::section compact>
                <x-slot name="heading">Reactions</x-slot>
                <div
                    x-data="{
                        counts: { fire: 0, clap: 0, heart: 0 },
                        floating: [],
                        emoji: { fire: '🔥', clap: '👏', heart: '❤️' },
                        init() {
                            if (! window.Echo) return;
                            window.Echo.channel(@js($station->reactionsChannel())).listen('.reaction.sent', (event) => {
                                if (! this.counts.hasOwnProperty(event.type)) return;
                                this.counts[event.type]++;
                                const id = Date.now() + Math.random();
                                this.floating.push({ id, type: event.type, left: 10 + Math.random() * 80 });
                                setTimeout(() => this.floating = this.floating.filter((f) => f.id !== id), 2500);
                            });
                        },
                        destroy() { window.Echo?.leave(@js($station->reactionsChannel())); },
                    }"
                    class="relative"
                    wire:ignore
                >
                    <div class="pointer-events-none absolute inset-x-0 -top-24 h-28 overflow-hidden" aria-hidden="true">
                        <template x-for="item in floating" :key="item.id">
                            <span class="studio-reaction absolute bottom-0 text-2xl" :style="`left: ${item.left}%`" x-text="emoji[item.type]"></span>
                        </template>
                    </div>
                    <div class="flex justify-between text-2xl">
                        <span>🔥 <span class="text-base font-bold tabular-nums" x-text="counts.fire"></span></span>
                        <span>👏 <span class="text-base font-bold tabular-nums" x-text="counts.clap"></span></span>
                        <span>❤️ <span class="text-base font-bold tabular-nums" x-text="counts.heart"></span></span>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Since this page opened. Viewers can react while the video livestream is on.</p>
                </div>
            </x-filament::section>

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
