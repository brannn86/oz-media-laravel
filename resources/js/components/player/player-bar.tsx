import {
    LoaderCircle,
    Maximize2,
    MessageCircle,
    Music,
    Minimize2,
    Pause,
    Play,
    RotateCcw,
    Video,
    Volume1,
    Volume2,
    VolumeX,
} from 'lucide-react';
import { useChat } from '@/components/chat/chat-context';
import { useLive } from '@/components/live/live-context';
import type { DrawerTab } from '@/components/live/live-context';
import { usePlayer } from '@/components/player/player-context';
import { cn } from '@/lib/utils';
import type { Broadcast } from '@/types';

/**
 * The sticky bottom bar. Rendered once by StationLayout.
 */
export function PlayerBar({ broadcast }: { broadcast: Broadcast }) {
    const player = usePlayer();
    const { mode, active, status } = player;

    const current = broadcast.current;
    const hosts = current?.program.announcers
        ?.map((announcer) => announcer.display_name)
        .join(' & ');

    const isVideo = mode === 'video';
    const playing = isVideo ? active : status === 'playing';

    const buttonLabel = isVideo
        ? active
            ? 'Close the video livestream'
            : 'Watch the livestream'
        : status === 'error'
          ? 'Retry the stream'
          : status === 'blocked'
            ? 'Tap to start the radio'
            : active
              ? 'Pause the radio'
              : 'Play the radio live';

    // What the second line says when the stream needs attention.
    const notice = isVideo
        ? null
        : status === 'blocked'
          ? 'Your browser paused the sound. Tap play to listen.'
          : status === 'reconnecting'
            ? 'Connection dropped. Reconnecting…'
            : status === 'error'
              ? 'The stream is not responding. Tap play to try again.'
              : null;

    return (
        <div
            role="region"
            aria-label="Live player"
            className="fixed inset-x-0 bottom-0 z-40 bg-oz-black text-oz-white"
        >
            <div className="mx-auto flex h-[4.5rem] max-w-7xl items-center gap-3 px-3 sm:gap-5 sm:px-6">
                <button
                    type="button"
                    onClick={player.toggle}
                    aria-label={buttonLabel}
                    title={buttonLabel}
                    className="grid size-12 shrink-0 place-items-center rounded-full bg-oz-yellow text-oz-black transition-transform hover:scale-105 focus-visible:ring-4 focus-visible:ring-oz-yellow/50 focus-visible:outline-none active:scale-95 sm:size-14"
                >
                    <PlayIcon
                        isVideo={isVideo}
                        active={active}
                        status={status}
                    />
                </button>

                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                        <LiveBadge live={current !== null} pulsing={playing} />
                        {isVideo && (
                            <span className="inline-flex items-center gap-1 rounded-full bg-oz-white/10 px-2 py-0.5 text-xs font-semibold text-oz-white">
                                <Video className="size-3.5" aria-hidden />
                                Video livestream
                            </span>
                        )}
                    </div>
                    <p className="mt-1 truncate font-oz-display text-sm font-extrabold sm:text-base">
                        {current?.program.title ?? player.station.name}
                    </p>
                    <p
                        className={cn(
                            'truncate text-xs',
                            notice
                                ? 'font-semibold text-oz-yellow'
                                : 'text-oz-white/60',
                        )}
                        aria-live="polite"
                    >
                        {notice ??
                            (hosts
                                ? `With ${hosts}`
                                : (player.station.tagline ?? 'Live radio'))}
                    </p>
                </div>

                {isVideo && active && (
                    <button
                        type="button"
                        onClick={() =>
                            player.setVideoLayout(
                                player.videoLayout === 'docked'
                                    ? 'expanded'
                                    : 'docked',
                            )
                        }
                        className="grid size-10 place-items-center rounded-full text-oz-white/80 hover:bg-oz-white/10 hover:text-oz-white focus-visible:ring-2 focus-visible:ring-oz-yellow focus-visible:outline-none"
                        aria-label={
                            player.videoLayout === 'docked'
                                ? 'Enlarge video'
                                : 'Shrink video'
                        }
                    >
                        {player.videoLayout === 'docked' ? (
                            <Maximize2 className="size-5" />
                        ) : (
                            <Minimize2 className="size-5" />
                        )}
                    </button>
                )}

                {!isVideo && <VolumeControl />}

                <DrawerToggles />
            </div>
        </div>
    );
}

function DrawerToggles() {
    const { unread } = useChat();
    const live = useLive();

    const toggle = (tab: DrawerTab) =>
        live.drawerOpen && live.drawerTab === tab
            ? live.closeDrawer()
            : live.openDrawer(tab);

    const isOn = (tab: DrawerTab) => live.drawerOpen && live.drawerTab === tab;

    const buttonClass = (on: boolean) =>
        cn(
            'relative inline-flex h-11 shrink-0 items-center gap-2 rounded-full px-3 text-sm font-bold transition-colors focus-visible:ring-2 focus-visible:ring-oz-yellow focus-visible:outline-none sm:px-4',
            on
                ? 'bg-oz-yellow text-oz-black'
                : 'bg-oz-white/10 text-oz-white hover:bg-oz-white/20',
        );

    return (
        <div className="flex shrink-0 gap-2">
            <button
                type="button"
                onClick={() => toggle('requests')}
                aria-expanded={isOn('requests')}
                aria-label="Request a song"
                className={cn(
                    buttonClass(isOn('requests')),
                    'hidden sm:inline-flex',
                )}
            >
                <Music className="size-5" aria-hidden />
                <span className="hidden lg:inline">Request</span>
            </button>
            <button
                type="button"
                onClick={() => toggle('chat')}
                aria-expanded={isOn('chat')}
                aria-label={
                    unread > 0
                        ? `Live chat, ${unread} new messages`
                        : 'Live chat'
                }
                className={buttonClass(isOn('chat'))}
            >
                <MessageCircle className="size-5" aria-hidden />
                <span className="hidden sm:inline">Chat</span>
                {unread > 0 && !isOn('chat') && (
                    <span className="absolute -top-1 -right-1 grid h-5 min-w-5 place-items-center rounded-full bg-oz-red px-1 text-[0.7rem] font-bold text-oz-white tabular-nums">
                        {unread > 99 ? '99+' : unread}
                    </span>
                )}
            </button>
        </div>
    );
}

function PlayIcon({
    isVideo,
    active,
    status,
}: {
    isVideo: boolean;
    active: boolean;
    status: string;
}) {
    const className = 'size-6 fill-current';

    if (isVideo) {
        return active ? (
            <Pause className={className} />
        ) : (
            <Play className={cn(className, 'translate-x-0.5')} />
        );
    }

    if (status === 'loading' || status === 'reconnecting') {
        return <LoaderCircle className="size-6 motion-safe:animate-spin" />;
    }

    if (status === 'error') {
        return <RotateCcw className="size-6" />;
    }

    if (status === 'blocked') {
        // Needs one tap: show play, not pause.
        return <Play className={cn(className, 'translate-x-0.5')} />;
    }

    return active ? (
        <Pause className={className} />
    ) : (
        <Play className={cn(className, 'translate-x-0.5')} />
    );
}

function LiveBadge({ live, pulsing }: { live: boolean; pulsing: boolean }) {
    return (
        <span className="inline-flex items-center gap-1.5 rounded-full bg-oz-red px-2 py-0.5 text-xs font-bold text-oz-white">
            <span className="relative flex size-2">
                {pulsing && (
                    <span className="absolute inline-flex size-full rounded-full bg-oz-white opacity-75 motion-safe:animate-ping" />
                )}
                <span className="relative inline-flex size-2 rounded-full bg-oz-white" />
            </span>
            {live ? 'On air' : 'Live'}
        </span>
    );
}

function VolumeControl() {
    const { volume, muted, setVolume, toggleMute } = usePlayer();
    const level = muted ? 0 : volume;
    const Icon = level === 0 ? VolumeX : level < 0.5 ? Volume1 : Volume2;

    return (
        <div className="hidden items-center gap-2 md:flex">
            <button
                type="button"
                onClick={toggleMute}
                aria-label={muted ? 'Unmute' : 'Mute'}
                className="grid size-10 place-items-center rounded-full text-oz-white/80 hover:bg-oz-white/10 hover:text-oz-white focus-visible:ring-2 focus-visible:ring-oz-yellow focus-visible:outline-none"
            >
                <Icon className="size-5" />
            </button>
            <input
                type="range"
                min={0}
                max={1}
                step={0.01}
                value={level}
                onChange={(event) => setVolume(Number(event.target.value))}
                aria-label="Volume"
                className="w-28 accent-oz-yellow"
            />
        </div>
    );
}
