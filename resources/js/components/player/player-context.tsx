import { useEchoPublic } from '@laravel/echo-react';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useRef,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import { attachStream } from '@/components/player/attach-stream';
import { apiFetch } from '@/lib/api';
import { landing } from '@/lib/landing';
import type { Broadcast, NowPlaying, Station, StreamStatus } from '@/types';

/**
 * - blocked: the browser refused to start sound without a tap (autoplay policy);
 *   one more tap on play fixes it.
 * - reconnecting: the stream dropped (network change, server hiccup) and the
 *   player is retrying with backoff.
 */
export type PlaybackStatus =
    | 'idle'
    | 'loading'
    | 'playing'
    | 'blocked'
    | 'reconnecting'
    | 'error';
export type VideoLayout = 'docked' | 'expanded';
export type StreamMode = 'audio' | 'video';

type PlayerApi = {
    station: Station;
    stream: StreamStatus;
    /** Song on air and recently played, kept live over Reverb. */
    nowPlaying: NowPlaying;
    /**
     * What the listener tuned in to. Video only while the station is
     * livestreaming; a listener who chose video falls back to audio otherwise.
     */
    mode: StreamMode;
    audioAvailable: boolean;
    videoAvailable: boolean;
    /** The listener wants the station on (audio playing or video open). */
    active: boolean;
    /** Tuned in at least once this visit (the mini player shows from then on). */
    hasStarted: boolean;
    status: PlaybackStatus;
    volume: number;
    muted: boolean;
    videoLayout: VideoLayout;
    /**
     * Frequency data of the radio for the visualizer: set once the stream
     * plays (CORS allowing). Null on Apple devices, where routing a live
     * stream through Web Audio can mute it on the lock screen.
     */
    analyser: AnalyserNode | null;
    /** Whether a real analyser can ever exist here (else: decorative bars). */
    canAnalyse: boolean;
    /**
     * The home hero is on screen and shows the video itself; the floating
     * video dock stays out of the way meanwhile (else it would play twice).
     */
    videoInHero: boolean;
    setVideoInHero: (inHero: boolean) => void;
    toggle: () => void;
    start: () => void;
    /** Tune in to (or switch to) audio or video. Call it from a tap. */
    play: (mode: StreamMode) => void;
    stop: () => void;
    setVolume: (volume: number) => void;
    toggleMute: () => void;
    setVideoLayout: (layout: VideoLayout) => void;
};

const PlayerContext = createContext<PlayerApi | null>(null);

const VOLUME_KEY = 'oz-player-volume';
/** Give up retrying (and show "tap to retry") after this many attempts. */
const MAX_RECONNECTS = 6;
/** A stalled stream that hasn't recovered by then is reconnected. */
const STALL_TIMEOUT_MS = 10_000;

function storedVolume(): number {
    try {
        const value = Number(window.localStorage.getItem(VOLUME_KEY));

        return Number.isFinite(value) && value > 0 && value <= 1 ? value : 0.8;
    } catch {
        return 0.8;
    }
}

function sameStream(a: StreamStatus, b: StreamStatus): boolean {
    return (
        a.audio_url === b.audio_url &&
        a.is_video_live === b.is_video_live &&
        a.video?.src === b.video?.src &&
        a.intermission_url === b.intermission_url
    );
}

/**
 * Web Audio can tap the stream here. Not on Apple WebKit (Safari, and every
 * iOS browser): a live stream routed through it may go silent when the
 * screen locks, and the radio must keep playing in the background.
 */
function supportsAnalyser(): boolean {
    if (typeof window === 'undefined' || !('AudioContext' in window)) {
        return false;
    }

    const ua = navigator.userAgent;
    const ios = /iP(hone|ad|od)/.test(ua);
    const safari =
        /Safari/.test(ua) && !/Chrome|Chromium|Edg|OPR|Android/.test(ua);

    return !ios && !safari;
}

const VISITOR_KEY = 'oz-visitor-id';
/** How often a playing player says "still here" (the server counts per minute). */
const AUDIENCE_PING_MS = 60_000;

/** A random id for this browser, so analytics can count unique visitors. */
function visitorId(): string | null {
    try {
        let id = window.localStorage.getItem(VISITOR_KEY);

        if (!id) {
            id =
                typeof crypto.randomUUID === 'function'
                    ? crypto.randomUUID()
                    : `${Date.now().toString(36)}-${Math.random().toString(36).slice(2)}`;
            window.localStorage.setItem(VISITOR_KEY, id);
        }

        return id;
    } catch {
        return null; // Storage blocked: the server falls back to the session.
    }
}

/**
 * Counts this player in Analytics (guests included): a ping now and once a
 * minute while the radio is actually playing or the video is open, with
 * where the visit came from (it labels the listening session).
 */
function useAudiencePing(stationSlug: string, mode: StreamMode | null) {
    useEffect(() => {
        if (!mode) {
            return;
        }

        const ping = () => {
            const id = visitorId();

            apiFetch(`/api/v1/stations/${stationSlug}/audience`, {
                method: 'POST',
                body: { mode, source: landing() },
                headers: id ? { 'X-Listener-Id': id } : undefined,
            }).catch(() => undefined);
        };

        ping();
        const timer = window.setInterval(ping, AUDIENCE_PING_MS);

        return () => window.clearInterval(timer);
    }, [stationSlug, mode]);
}

const RECENT_LIMIT = 10;

function nowPlayingKey(value: NowPlaying): string {
    return `${value.track?.id ?? '-'}:${value.recent[0]?.id ?? '-'}`;
}

/** Applies a "now-playing.updated" push: the song that ended moves to recent. */
function advance(prev: NowPlaying, track: NowPlaying['track']): NowPlaying {
    if (prev.track?.id === track?.id) {
        return prev;
    }

    const ended = prev.track
        ? [{ ...prev.track, ended_at: new Date().toISOString() }]
        : [];

    return {
        ...prev,
        track,
        recent: [...ended, ...prev.recent]
            .filter((played) => played.id !== track?.id)
            .slice(0, RECENT_LIMIT),
    };
}

/**
 * Owns the one <audio> element for the station site. Lives in StationLayout,
 * which Inertia keeps mounted between pages, so playback never restarts on navigation.
 *
 * Mobile-safe by design:
 * - play() runs synchronously inside the tap handler (iOS Safari and Chrome
 *   only allow sound from a user gesture);
 * - a refused autoplay becomes "tap to play", not an error;
 * - drops and stalls reconnect with exponential backoff, immediately when the
 *   device comes back online;
 * - lock screen / notification controls show the show, hosts and cover art.
 */
export function PlayerProvider({
    station,
    broadcast,
    nowPlaying: nowPlayingProp,
    children,
}: {
    station: Station;
    broadcast: Broadcast;
    nowPlaying: NowPlaying;
    children: ReactNode;
}) {
    const audioRef = useRef<HTMLAudioElement>(null);

    // Stream info comes from page props, and is overridden live by Reverb.
    const [streamProp, setStreamProp] = useState(station.stream);
    const [stream, setStream] = useState(station.stream);

    if (!sameStream(streamProp, station.stream)) {
        setStreamProp(station.stream);
        setStream(station.stream);
    }

    useEchoPublic<StreamStatus>(
        station.channel,
        '.stream-status.updated',
        (payload) => setStream(payload),
    );

    // Same pattern for the song on air: fresh props on navigation, Reverb in between.
    const [nowPlayingSeen, setNowPlayingSeen] = useState(() =>
        nowPlayingKey(nowPlayingProp),
    );
    const [nowPlaying, setNowPlaying] = useState(nowPlayingProp);

    if (nowPlayingKey(nowPlayingProp) !== nowPlayingSeen) {
        setNowPlayingSeen(nowPlayingKey(nowPlayingProp));
        setNowPlaying(nowPlayingProp);
    }

    useEchoPublic<Pick<NowPlaying, 'track'>>(
        station.channel,
        '.now-playing.updated',
        (payload) => setNowPlaying((prev) => advance(prev, payload.track)),
    );

    useEchoPublic<Pick<NowPlaying, 'listeners'>>(
        station.channel,
        '.listeners.updated',
        (payload) =>
            setNowPlaying((prev) => ({
                ...prev,
                listeners: payload.listeners,
            })),
    );

    const [active, setActive] = useState(false);
    const [hasStarted, setHasStarted] = useState(false);
    const [status, setStatus] = useState<PlaybackStatus>('idle');
    const [volume, setVolumeState] = useState(storedVolume);
    const [muted, setMuted] = useState(false);
    const [videoLayout, setVideoLayout] = useState<VideoLayout>('docked');
    const [videoInHero, setVideoInHero] = useState(false);
    const [preferredMode, setPreferredMode] = useState<StreamMode>('audio');

    // Visualizer: an AudioContext is woken inside the tune-in tap; the
    // analyser is attached once the stream really plays with CORS, so a
    // stream without CORS headers never ends up routed (and silenced).
    const [canAnalyse, setCanAnalyse] = useState(false);
    const [analyser, setAnalyser] = useState<AnalyserNode | null>(null);
    const audioContextRef = useRef<AudioContext | null>(null);
    const everPlayedRef = useRef(false);
    /** createMediaElementSource() works once per element. */
    const tappedRef = useRef(false);

    useEffect(() => setCanAnalyse(supportsAnalyser()), []);

    const videoAvailable = Boolean(stream.is_video_live && stream.video);
    const audioAvailable = Boolean(stream.audio_url);
    const mode: StreamMode =
        preferredMode === 'video' && videoAvailable ? 'video' : 'audio';
    const audioUrl = stream.audio_url;

    // Callbacks below run from media events and timers; refs keep them current.
    const activeRef = useRef(false);
    const modeRef = useRef(mode);
    const urlRef = useRef(audioUrl);
    const detachRef = useRef<(() => void) | null>(null);
    const retryTimer = useRef<number | null>(null);
    const stallTimer = useRef<number | null>(null);
    const attempts = useRef(0);

    useEffect(() => {
        modeRef.current = mode;
        urlRef.current = audioUrl;
    }, [mode, audioUrl]);

    const clearTimers = useCallback(() => {
        for (const timer of [retryTimer, stallTimer]) {
            if (timer.current !== null) {
                window.clearTimeout(timer.current);
                timer.current = null;
            }
        }
    }, []);

    const disconnect = useCallback(() => {
        clearTimers();
        detachRef.current?.();
        detachRef.current = null;
    }, [clearTimers]);

    // Declared before connect() because they call each other through refs.
    const scheduleReconnectRef = useRef<() => void>(() => undefined);

    /**
     * Opens the stream and starts playback. Called directly from tap handlers
     * so that audio.play() happens inside the user gesture.
     */
    const connect = useCallback(() => {
        const audio = audioRef.current;
        const url = urlRef.current;

        if (!audio || !url) {
            setStatus('error');

            return;
        }

        disconnect();
        setStatus(attempts.current > 0 ? 'reconnecting' : 'loading');

        detachRef.current = attachStream(audio, url, () =>
            scheduleReconnectRef.current(),
        );

        audio.play().catch((error: unknown) => {
            if (!(error instanceof DOMException)) {
                scheduleReconnectRef.current();

                return;
            }

            if (error.name === 'AbortError') {
                return; // Replaced by a newer connect() or a stop.
            }

            if (error.name === 'NotAllowedError') {
                // Autoplay policy: keep the intent, ask for one tap.
                disconnect();
                setStatus('blocked');

                return;
            }

            scheduleReconnectRef.current();
        });
    }, [disconnect]);

    useEffect(() => {
        scheduleReconnectRef.current = () => {
            if (!activeRef.current || modeRef.current !== 'audio') {
                return;
            }

            disconnect();
            attempts.current += 1;

            if (attempts.current > MAX_RECONNECTS) {
                setStatus('error');

                return;
            }

            setStatus('reconnecting');
            // 1s, 2s, 4s, … capped at 30s.
            const delay = Math.min(30_000, 1000 * 2 ** (attempts.current - 1));
            retryTimer.current = window.setTimeout(connect, delay);
        };
    }, [connect, disconnect]);

    const start = useCallback(() => {
        activeRef.current = true;
        attempts.current = 0;
        setActive(true);
        setHasStarted(true);

        if (modeRef.current === 'audio') {
            // Inside the tap, so the browser lets the AudioContext run.
            if (audioRef.current?.crossOrigin === 'anonymous') {
                audioContextRef.current ??= new AudioContext();
                void audioContextRef.current.resume().catch(() => undefined);
            }

            connect(); // Synchronous: still inside the tap.
        } else {
            disconnect(); // The video panel takes over.
            setStatus('idle');
        }
    }, [connect, disconnect]);

    const stop = useCallback(() => {
        activeRef.current = false;
        attempts.current = 0;
        disconnect();
        setActive(false);
        setStatus('idle');
    }, [disconnect]);

    const toggle = useCallback(() => {
        const needsTap =
            status === 'error' ||
            status === 'blocked' ||
            status === 'reconnecting';

        if (active && !needsTap) {
            stop();
        } else {
            start();
        }
    }, [active, status, start, stop]);

    // The station switched between audio and video (or changed its audio URL)
    // while the listener was tuned in: follow it.
    const followRef = useRef({ mode, audioUrl });

    const play = useCallback(
        (next: StreamMode) => {
            const target: StreamMode =
                next === 'video' && videoAvailable ? 'video' : 'audio';

            setPreferredMode(target);
            // Act on the new mode now, inside the tap; tell the follow effect
            // it is already handled so it doesn't connect a second time.
            modeRef.current = target;
            followRef.current = { ...followRef.current, mode: target };
            start();
        },
        [videoAvailable, start],
    );

    useEffect(() => {
        const previous = followRef.current;
        followRef.current = { mode, audioUrl };

        if (!activeRef.current) {
            return;
        }

        if (mode === 'video') {
            disconnect(); // The video panel takes over.
        } else if (
            previous.mode !== 'audio' ||
            previous.audioUrl !== audioUrl
        ) {
            attempts.current = 0;
            connect(); // Outside a tap, so this may end up "blocked".
        }
    }, [mode, audioUrl, connect, disconnect]);

    // Back online after a drop: retry straight away instead of waiting out the backoff.
    useEffect(() => {
        const onOnline = () => {
            if (activeRef.current && modeRef.current === 'audio') {
                attempts.current = 0;
                connect();
            }
        };

        window.addEventListener('online', onOnline);

        return () => window.removeEventListener('online', onOnline);
    }, [connect]);

    useEffect(() => disconnect, [disconnect]);

    useEffect(() => {
        const audio = audioRef.current;

        if (audio) {
            // Ignored by iOS (hardware volume only), which is why the slider is hidden on phones.
            audio.volume = volume;
            audio.muted = muted;
        }
    }, [volume, muted]);

    useAudiencePing(
        station.slug,
        !active
            ? null
            : mode === 'video'
              ? 'video'
              : status === 'playing'
                ? 'audio'
                : null,
    );

    useMediaSession({
        station,
        broadcast,
        track: nowPlaying.track,
        active,
        status,
        start,
        stop,
    });

    const setVolume = useCallback((value: number) => {
        setVolumeState(value);
        setMuted(value === 0);

        try {
            window.localStorage.setItem(VOLUME_KEY, String(value));
        } catch {
            // Private mode: volume just isn't remembered.
        }
    }, []);

    const api: PlayerApi = {
        station,
        stream,
        nowPlaying,
        mode,
        audioAvailable,
        videoAvailable,
        active,
        hasStarted,
        status: active ? status : 'idle',
        volume,
        muted,
        videoLayout,
        analyser,
        canAnalyse,
        videoInHero,
        setVideoInHero,
        toggle,
        start,
        play,
        stop,
        setVolume,
        toggleMute: () => setMuted((m) => !m),
        setVideoLayout,
    };

    const armStallWatchdog = () => {
        if (stallTimer.current !== null || !activeRef.current) {
            return;
        }

        stallTimer.current = window.setTimeout(() => {
            stallTimer.current = null;
            scheduleReconnectRef.current();
        }, STALL_TIMEOUT_MS);
    };

    const clearStallWatchdog = () => {
        if (stallTimer.current !== null) {
            window.clearTimeout(stallTimer.current);
            stallTimer.current = null;
        }
    };

    return (
        <PlayerContext.Provider value={api}>
            {children}
            <audio
                ref={audioRef}
                preload="none"
                crossOrigin={canAnalyse ? 'anonymous' : undefined}
                onWaiting={() => {
                    setStatus((current) =>
                        current === 'reconnecting' ? current : 'loading',
                    );
                    armStallWatchdog();
                }}
                onStalled={armStallWatchdog}
                onPlaying={(event) => {
                    clearStallWatchdog();
                    attempts.current = 0;
                    everPlayedRef.current = true;
                    setStatus('playing');

                    const context = audioContextRef.current;

                    // Playing with crossorigin set proves CORS: safe to tap.
                    if (
                        context &&
                        !tappedRef.current &&
                        event.currentTarget.crossOrigin === 'anonymous'
                    ) {
                        tappedRef.current = true;
                        const node = context.createAnalyser();
                        node.fftSize = 256;
                        node.minDecibels = -90;
                        node.maxDecibels = -15;
                        node.smoothingTimeConstant = 0.82;
                        context
                            .createMediaElementSource(event.currentTarget)
                            .connect(node);
                        node.connect(context.destination);
                        setAnalyser(node);
                    }
                }}
                // A live stream never "ends": it dropped.
                onEnded={() => scheduleReconnectRef.current()}
                onError={(event) => {
                    const audio = event.currentTarget;

                    if (!audio.getAttribute('src')) {
                        return;
                    }

                    // Refused before it ever played with crossorigin set: the
                    // stream sends no CORS headers (a CORS refusal surfaces as
                    // "source not supported"; a dropped connection doesn't).
                    // Play it plainly, with decorative bars instead.
                    if (
                        audio.crossOrigin === 'anonymous' &&
                        audio.error?.code ===
                            MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED &&
                        !everPlayedRef.current &&
                        !tappedRef.current
                    ) {
                        setCanAnalyse(false);
                        audio.removeAttribute('crossorigin');
                        attempts.current = 0;
                        connect();

                        return;
                    }

                    scheduleReconnectRef.current();
                }}
            />
        </PlayerContext.Provider>
    );
}

/**
 * Lock screen, notification shade, headphones and car controls.
 */
function useMediaSession({
    station,
    broadcast,
    track,
    active,
    status,
    start,
    stop,
}: {
    station: Station;
    broadcast: Broadcast;
    track: NowPlaying['track'];
    active: boolean;
    status: PlaybackStatus;
    start: () => void;
    stop: () => void;
}) {
    const program = broadcast.current?.program;
    const hosts = program?.announcers ?? [];
    const hostNames = hosts.map((host) => host.display_name).join(' & ');
    const cover =
        program?.cover_image_url ??
        hosts.find((h) => h.avatar_url)?.avatar_url ??
        null;

    useEffect(() => {
        if (!('mediaSession' in navigator)) {
            return;
        }

        const origin = window.location.origin;
        const artwork = [
            ...(cover
                ? [{ src: new URL(cover, origin).href, sizes: '512x512' }]
                : []),
            {
                src: `${origin}/apple-touch-icon.png`,
                sizes: '180x180',
                type: 'image/png',
            },
        ];

        // The song on air when Icecast reports one, otherwise the show.
        navigator.mediaSession.metadata = new MediaMetadata(
            track
                ? {
                      title: track.title,
                      artist: track.artist ?? station.name,
                      album: program?.title ?? station.name,
                      artwork,
                  }
                : {
                      title: program?.title ?? `${station.name} live`,
                      artist: hostNames ? `with ${hostNames}` : station.name,
                      album: station.name,
                      artwork,
                  },
        );
    }, [program?.title, hostNames, cover, station.name, track]);

    useEffect(() => {
        if (!('mediaSession' in navigator)) {
            return;
        }

        // A lock-screen tap is a user gesture, so start() may play straight away.
        navigator.mediaSession.setActionHandler('play', start);
        navigator.mediaSession.setActionHandler('pause', stop);
        navigator.mediaSession.setActionHandler('stop', stop);

        return () => {
            for (const action of ['play', 'pause', 'stop'] as const) {
                navigator.mediaSession.setActionHandler(action, null);
            }
        };
    }, [start, stop]);

    useEffect(() => {
        if ('mediaSession' in navigator) {
            navigator.mediaSession.playbackState = !active
                ? 'none'
                : status === 'playing'
                  ? 'playing'
                  : 'paused';
        }
    }, [active, status]);
}

export function usePlayer(): PlayerApi {
    const player = useContext(PlayerContext);

    if (!player) {
        throw new Error('usePlayer() must be used inside <PlayerProvider>.');
    }

    return player;
}
