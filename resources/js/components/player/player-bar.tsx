import {
    Headphones,
    LoaderCircle,
    MessageCircle,
    Music,
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

/**
 * The sticky bottom bar. Rendered once by StationLayout.
 *
 * Left: play/pause and the song on air (title, artist). Right, one evenly
 * spaced row of same-height controls: volume, radio/video, request, chat.
 * Nothing in the row changes size between radio and video, so the buttons
 * never jump.
 */
export function PlayerBar() {
    const player = usePlayer();
    const live = useLive();
    const { mode, active, status } = player;

    // Hidden until the listener tunes in, and while the home hero player is
    // on screen (it has the same controls); chat and requests stay one tap
    // away meanwhile, except while the drawer is open (it has its own tabs
    // and close button, and the buttons would sit on top of it).
    if (!player.barVisible) {
        if (live.drawerOpen) {
            return null;
        }

        return (
            <div className="fixed right-3 bottom-3 z-40 rounded-full bg-oz-black p-1.5 shadow-xl sm:right-6 sm:bottom-6">
                <DrawerToggles />
            </div>
        );
    }

    const track = player.nowPlaying.track;
    const isVideo = mode === 'video';

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
            <div className="mx-auto flex h-[4.5rem] max-w-7xl items-center gap-3 px-3 sm:gap-4 sm:px-6">
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

                <div className="min-w-0 flex-1" aria-live="polite">
                    <p className="truncate font-oz-display text-sm font-extrabold sm:text-base">
                        {track && (
                            <span className="sr-only">Now playing: </span>
                        )}
                        {track?.title ?? player.station.name}
                    </p>
                    <p
                        className={cn(
                            'truncate text-xs sm:text-sm',
                            notice
                                ? 'font-semibold text-oz-yellow'
                                : 'text-oz-white/60',
                        )}
                    >
                        {notice ??
                            (track
                                ? (track.artist ?? player.station.name)
                                : 'Live radio')}
                    </p>
                </div>

                <div className="flex shrink-0 items-center gap-2">
                    <VolumeControl />
                    <ModeSwitch />
                    <DrawerToggles />
                </div>
            </div>
        </div>
    );
}

/**
 * Radio ⇄ video, when the other one is on air. A fixed size, so the row
 * doesn't shift when it toggles between "Video" and "Radio".
 */
function ModeSwitch() {
    const { mode, play, audioAvailable, videoAvailable } = usePlayer();
    const other = mode === 'audio' ? 'video' : 'audio';
    const available = other === 'video' ? videoAvailable : audioAvailable;

    if (!available) {
        return null;
    }

    const label =
        other === 'video'
            ? 'Switch to the video livestream'
            : 'Switch to radio';

    return (
        <button
            type="button"
            onClick={() => play(other)}
            aria-label={label}
            title={label}
            className={cn(controlButton(false), 'justify-center lg:w-[6.5rem]')}
        >
            {other === 'video' ? (
                <Video className="size-5" aria-hidden />
            ) : (
                <Headphones className="size-5" aria-hidden />
            )}
            <span className="hidden lg:inline">
                {other === 'video' ? 'Video' : 'Radio'}
            </span>
        </button>
    );
}

/** The bar's round pill buttons, all one size. */
function controlButton(on: boolean): string {
    return cn(
        'relative inline-flex h-11 min-w-11 shrink-0 items-center gap-2 rounded-full px-3 text-sm font-bold transition-colors focus-visible:ring-2 focus-visible:ring-oz-yellow focus-visible:outline-none lg:px-4',
        on
            ? 'bg-oz-yellow text-oz-black'
            : 'bg-oz-white/10 text-oz-white hover:bg-oz-white/20',
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

    const buttonClass = controlButton;

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

/** Mute and a volume slider (for dark backgrounds). Hidden on phones unless `className` says otherwise. */
export function VolumeControl({
    className = 'hidden md:flex',
}: {
    className?: string;
}) {
    const { volume, muted, setVolume, toggleMute } = usePlayer();
    const level = muted ? 0 : volume;
    const Icon = level === 0 ? VolumeX : level < 0.5 ? Volume1 : Volume2;

    return (
        <div className={cn('items-center gap-2', className)}>
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
