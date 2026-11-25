import { Headphones, Maximize2, Minimize2, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { RefObject } from 'react';
import { useLive } from '@/components/live/live-context';
import {
    ReactionButtons,
    ReactionOverlay,
    useReactions,
} from '@/components/live/reactions';
import { attachStream } from '@/components/player/attach-stream';
import { usePlayer } from '@/components/player/player-context';
import {
    YT_STATE,
    loadYouTubeApi,
    withJsApi,
} from '@/components/player/youtube-api';
import type { YouTubePlayer } from '@/components/player/youtube-api';
import { cn } from '@/lib/utils';
import type { VideoSource } from '@/types';

/** connecting: just opened; live: the feed is playing; offline: it isn't. */
export type FeedStatus = 'connecting' | 'live' | 'offline';

/** How long a feed may take to start before the intermission shows. */
const CONNECT_GRACE_MS = 10_000;

/** While a YouTube feed isn't live, look again this often (late shows). */
const YOUTUBE_RETRY_MS = 60_000;

/**
 * Floating video panel, shown while the listener watches video.
 *
 * Docked: small card above the player bar. Expanded: large centred panel.
 * Only the container's classes change between the two, so the video keeps
 * playing. While the livestream is blank or offline (a show starting late,
 * or not live on YouTube yet) the station's intermission video plays over
 * it, and the feed keeps trying in the background, taking over once it's on.
 */
export function VideoDock() {
    const player = usePlayer();
    const drawerOpen = useLive().drawerOpen;
    const reactions = useReactions(player.station.slug);
    const { stream, mode, active, videoLayout } = player;
    const source = stream.video;
    // On the home page the hero shows the video itself.
    const showing =
        mode === 'video' && active && source !== null && !player.videoInHero;
    const [status, setStatus] = useFeedStatus(source, showing);

    if (!showing || !source) {
        return null;
    }

    const expanded = videoLayout === 'expanded';
    const live = status === 'live';

    return (
        <>
            {expanded && (
                <button
                    type="button"
                    aria-label="Shrink video"
                    onClick={() => player.setVideoLayout('docked')}
                    className="fixed inset-0 z-30 bg-oz-black/70"
                />
            )}
            <section
                aria-label={`${player.station.name} video livestream`}
                className={cn(
                    'fixed z-40 overflow-hidden bg-oz-black shadow-2xl ring-1 ring-oz-white/10',
                    expanded
                        ? 'inset-x-3 top-1/2 mx-auto max-w-5xl -translate-y-[calc(50%+2.25rem)] rounded-2xl sm:inset-x-8'
                        : cn(
                              'right-3 bottom-[calc(var(--oz-player-bar)+0.75rem)] left-3 rounded-2xl sm:left-auto sm:w-[24rem]',
                              // Sit beside the drawer instead of under it.
                              drawerOpen && 'sm:right-[25rem]',
                          ),
                )}
            >
                <div className="flex items-center justify-between gap-2 px-3 py-2 text-oz-white">
                    <p className="flex min-w-0 items-center gap-2 text-sm font-semibold">
                        <span
                            aria-hidden
                            className={cn(
                                'size-2 shrink-0 rounded-full',
                                live ? 'bg-oz-red' : 'bg-oz-white/40',
                            )}
                        />
                        <span className="truncate">
                            {player.station.name} {feedLabel(status)}
                        </span>
                    </p>
                    <div className="flex items-center gap-1">
                        <ReactionButtons onReact={reactions.send} />
                        {!expanded && (
                            <button
                                type="button"
                                onClick={() =>
                                    player.setVideoLayout('expanded')
                                }
                                aria-label="Enlarge video"
                                title="Enlarge video"
                                className="grid size-8 place-items-center rounded-full hover:bg-oz-white/10 focus-visible:ring-2 focus-visible:ring-oz-yellow focus-visible:outline-none"
                            >
                                <Maximize2 className="size-4" />
                            </button>
                        )}
                        {expanded && (
                            <button
                                type="button"
                                onClick={() => player.setVideoLayout('docked')}
                                aria-label="Shrink video"
                                className="grid size-8 place-items-center rounded-full hover:bg-oz-white/10 focus-visible:ring-2 focus-visible:ring-oz-yellow focus-visible:outline-none"
                            >
                                <Minimize2 className="size-4" />
                            </button>
                        )}
                        <button
                            type="button"
                            onClick={player.stop}
                            aria-label="Close video"
                            className="grid size-8 place-items-center rounded-full hover:bg-oz-white/10 focus-visible:ring-2 focus-visible:ring-oz-yellow focus-visible:outline-none"
                        >
                            <X className="size-4" />
                        </button>
                    </div>
                </div>
                <div className="relative aspect-video w-full bg-oz-black">
                    <VideoStage
                        source={source}
                        status={status}
                        onStatus={setStatus}
                    />
                    <ReactionOverlay floaters={reactions.floaters} />
                </div>
            </section>
        </>
    );
}

/**
 * Where a video feed stands: connecting when it (re)opens, then live once it
 * plays, offline if it doesn't within the grace period or stops.
 */
export function useFeedStatus(
    source: VideoSource | null,
    showing: boolean,
): [FeedStatus, (status: 'live' | 'offline') => void] {
    const [status, setStatus] = useState<FeedStatus>('connecting');

    // A new feed (or reopening it) starts over.
    useEffect(() => {
        setStatus(source?.type === 'embed' ? 'live' : 'connecting');
    }, [source?.src, source?.type, showing]);

    useEffect(() => {
        if (status !== 'connecting') {
            return;
        }

        const timer = window.setTimeout(
            () => setStatus('offline'),
            CONNECT_GRACE_MS,
        );

        return () => window.clearTimeout(timer);
    }, [status]);

    return [status, setStatus];
}

/** "live", "· connecting…" or "· back soon", after the station name. */
export function feedLabel(status: FeedStatus): string {
    return status === 'live'
        ? 'live'
        : status === 'connecting'
          ? '· connecting…'
          : '· back soon';
}

/**
 * The video feed with the station's intermission over it while the feed is
 * off. Fills its (positioned) parent. The feed stays mounted under the
 * intermission so it can take over as soon as it's live.
 */
export function VideoStage({
    source,
    status,
    onStatus,
}: {
    source: VideoSource;
    status: FeedStatus;
    onStatus: (status: 'live' | 'offline') => void;
}) {
    const player = usePlayer();
    const live = status === 'live';

    return (
        <>
            <div
                className={cn(
                    'absolute inset-0',
                    !live && 'pointer-events-none opacity-0',
                )}
                aria-hidden={!live || undefined}
            >
                <VideoFeed
                    key={source.src}
                    source={source}
                    live={live}
                    onStatus={onStatus}
                />
            </div>
            {!live && (
                <Intermission
                    src={player.stream.intermission_url}
                    connecting={status === 'connecting'}
                    onListen={() => player.play('audio')}
                />
            )}
        </>
    );
}

type FeedProps = {
    source: VideoSource;
    live: boolean;
    onStatus: (status: 'live' | 'offline') => void;
};

function VideoFeed({ source, live, onStatus }: FeedProps) {
    if (source.type === 'hls') {
        return <HlsVideo src={source.src} onStatus={onStatus} />;
    }

    if (source.type === 'youtube') {
        return <YouTubeFeed src={source.src} live={live} onStatus={onStatus} />;
    }

    // Other embeds can't be inspected; they're shown as they are.
    return (
        <iframe
            src={source.src}
            title="Video livestream"
            className="size-full"
            allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
            allowFullScreen
            referrerPolicy="strict-origin-when-cross-origin"
        />
    );
}

/**
 * A YouTube video or channel livestream, watched through the IFrame API so
 * we know when it is actually playing. It starts muted (autoplay always
 * allows that) and unmutes once playing. Not live yet (upcoming, offline,
 * "video unavailable"): reloaded every minute until it is.
 */
function YouTubeFeed({
    src,
    live,
    onStatus,
}: {
    src: string;
    live: boolean;
    onStatus: FeedProps['onStatus'];
}) {
    const hostRef = useRef<HTMLDivElement>(null);
    const onStatusRef = useRef(onStatus);
    const [attempt, setAttempt] = useState(0);
    const { volume, muted } = usePlayer();
    const playerRef = useRef<YouTubePlayer | null>(null);
    // The bar's volume, read when playback starts (the player starts muted).
    const soundRef = useRef({ volume, muted });

    useEffect(() => {
        onStatusRef.current = onStatus;
    }, [onStatus]);

    useEffect(() => {
        const host = hostRef.current;

        if (!host) {
            return;
        }

        // Created by hand: the YouTube API takes the iframe over (and
        // removes it on destroy), so React must not own it.
        const iframe = document.createElement('iframe');
        iframe.src = withJsApi(src);
        iframe.title = 'Video livestream';
        iframe.className = 'size-full';
        iframe.allow =
            'autoplay; encrypted-media; picture-in-picture; fullscreen';
        iframe.allowFullscreen = true;
        iframe.referrerPolicy = 'strict-origin-when-cross-origin';
        host.replaceChildren(iframe);

        let player: YouTubePlayer | null = null;
        let cancelled = false;

        loadYouTubeApi().then(
            (YT) => {
                if (cancelled) {
                    return;
                }

                player = playerRef.current = new YT.Player(iframe, {
                    events: {
                        onReady: ({ target }) => {
                            target.mute();
                            target.playVideo();
                        },
                        onStateChange: ({ target, data }) => {
                            if (data === YT_STATE.PLAYING) {
                                applyVolume(target, soundRef.current);
                                onStatusRef.current('live');
                            } else if (data === YT_STATE.ENDED) {
                                onStatusRef.current('offline');
                            }
                        },
                        onError: () => onStatusRef.current('offline'),
                    },
                });
            },
            // No API (blocked script): show the embed as it is.
            () => onStatusRef.current('live'),
        );

        return () => {
            cancelled = true;
            playerRef.current = null;
            player?.destroy();
            host.replaceChildren();
        };
    }, [src, attempt]);

    // The bar's volume and mute drive the video too.
    useEffect(() => {
        soundRef.current = { volume, muted };

        if (playerRef.current && live) {
            applyVolume(playerRef.current, { volume, muted });
        }
    }, [volume, muted, live]);

    useEffect(() => {
        if (live) {
            return;
        }

        const timer = window.setInterval(
            () => setAttempt((n) => n + 1),
            YOUTUBE_RETRY_MS,
        );

        return () => window.clearInterval(timer);
    }, [live]);

    return <div ref={hostRef} className="size-full" />;
}

function applyVolume(
    player: YouTubePlayer,
    sound: { volume: number; muted: boolean },
): void {
    player.setVolume(Math.round(sound.volume * 100));

    if (sound.muted) {
        player.mute();
    } else {
        player.unMute();
    }
}

/**
 * A <video> follows the bar's volume and mute.
 */
function useFollowVolume(ref: RefObject<HTMLMediaElement | null>) {
    const { volume, muted } = usePlayer();

    useEffect(() => {
        if (ref.current) {
            ref.current.volume = volume;
        }
    }, [ref, volume]);

    useEffect(() => {
        if (ref.current) {
            ref.current.muted = muted;
        }
    }, [ref, muted]);
}

/**
 * HLS livestream. Browsers may refuse to start video *with sound* outside a
 * tap; then it starts muted (always allowed) with a "Tap for sound" button.
 * A dropped or missing stream is retried with backoff.
 */
function HlsVideo({
    src,
    onStatus,
}: {
    src: string;
    onStatus: FeedProps['onStatus'];
}) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const [needsSound, setNeedsSound] = useState(false);
    const [attempt, setAttempt] = useState(0);
    useFollowVolume(videoRef);

    useEffect(() => {
        const video = videoRef.current;

        if (!video) {
            return;
        }

        let retry: number | undefined;
        const reconnect = () => {
            onStatus('offline');
            retry = window.setTimeout(
                () => setAttempt((n) => n + 1),
                Math.min(30_000, 2000 * 2 ** Math.min(attempt, 4)),
            );
        };

        const detach = attachStream(video, src, reconnect);

        video.play().catch((error: unknown) => {
            if (
                error instanceof DOMException &&
                error.name === 'NotAllowedError'
            ) {
                video.muted = true;
                setNeedsSound(true);
                void video.play().catch(() => undefined);
            }
        });

        return () => {
            window.clearTimeout(retry);
            detach();
        };
    }, [src, attempt, onStatus]);

    return (
        <>
            <video
                ref={videoRef}
                className="size-full"
                controls
                playsInline
                autoPlay
                onPlaying={() => onStatus('live')}
                onEnded={() => {
                    onStatus('offline');
                    setAttempt((n) => n + 1);
                }}
            />
            {needsSound && (
                <SoundButton
                    onClick={() => {
                        const video = videoRef.current;

                        if (video) {
                            video.muted = false;
                            void video.play();
                        }

                        setNeedsSound(false);
                    }}
                />
            )}
        </>
    );
}

/**
 * Fills the player while the livestream is off: the station's looping
 * intermission clip (from Station Settings), or a plain card without one.
 */
function Intermission({
    src,
    connecting,
    onListen,
}: {
    src: string | null;
    connecting: boolean;
    onListen: () => void;
}) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const [needsSound, setNeedsSound] = useState(false);
    useFollowVolume(videoRef);

    useEffect(() => {
        const video = videoRef.current;

        if (!video || connecting) {
            return;
        }

        video.play().catch((error: unknown) => {
            if (
                error instanceof DOMException &&
                error.name === 'NotAllowedError'
            ) {
                video.muted = true;
                setNeedsSound(true);
                void video.play().catch(() => undefined);
            }
        });
    }, [src, connecting]);

    return (
        <div className="absolute inset-0 grid place-items-center bg-oz-black text-oz-white">
            {src && !connecting && (
                <video
                    ref={videoRef}
                    src={src}
                    className="absolute inset-0 size-full object-cover"
                    loop
                    playsInline
                    preload="auto"
                />
            )}
            <div
                className={cn(
                    'relative flex flex-col items-center gap-3 px-6 text-center',
                    src &&
                        !connecting &&
                        'absolute inset-x-0 bottom-0 bg-gradient-to-t from-oz-black/80 to-transparent pt-10 pb-4',
                )}
            >
                <p className="text-sm font-semibold" aria-live="polite">
                    {connecting
                        ? 'Connecting to the livestream…'
                        : "We're not live on video right now. We'll switch over as soon as we are."}
                </p>
                {!connecting && (
                    <button
                        type="button"
                        onClick={onListen}
                        className="inline-flex items-center gap-2 rounded-full bg-oz-yellow px-4 py-2 text-sm font-bold text-oz-black focus-visible:ring-4 focus-visible:ring-oz-white focus-visible:outline-none"
                    >
                        <Headphones className="size-4" aria-hidden />
                        Listen to the radio
                    </button>
                )}
            </div>
            {needsSound && (
                <SoundButton
                    onClick={() => {
                        const video = videoRef.current;

                        if (video) {
                            video.muted = false;
                            void video.play();
                        }

                        setNeedsSound(false);
                    }}
                />
            )}
        </div>
    );
}

function SoundButton({ onClick }: { onClick: () => void }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="absolute top-3 left-3 rounded-full bg-oz-yellow px-4 py-2 text-sm font-bold text-oz-black shadow-lg focus-visible:ring-4 focus-visible:ring-oz-white focus-visible:outline-none"
        >
            Tap for sound
        </button>
    );
}
