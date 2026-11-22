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
import type { Broadcast, NowPlaying, Station, StreamStatus } from '@/types';

export type PlaybackStatus = 'idle' | 'loading' | 'playing' | 'error';
export type VideoLayout = 'docked' | 'expanded';

type PlayerApi = {
    station: Station;
    stream: StreamStatus;
    /** Song on air and recently played, kept live over Reverb. */
    nowPlaying: NowPlaying;
    /** Video when the station is livestreaming and has a video source. */
    mode: 'audio' | 'video';
    /** The listener wants the station on (audio playing or video open). */
    active: boolean;
    status: PlaybackStatus;
    volume: number;
    muted: boolean;
    videoLayout: VideoLayout;
    toggle: () => void;
    start: () => void;
    stop: () => void;
    setVolume: (volume: number) => void;
    toggleMute: () => void;
    setVideoLayout: (layout: VideoLayout) => void;
};

const PlayerContext = createContext<PlayerApi | null>(null);

const VOLUME_KEY = 'oz-player-volume';

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
        a.video?.src === b.video?.src
    );
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
        track,
        recent: [...ended, ...prev.recent]
            .filter((played) => played.id !== track?.id)
            .slice(0, RECENT_LIMIT),
    };
}

/**
 * Owns the one <audio> element for the station site. Lives in StationLayout,
 * which Inertia keeps mounted between pages, so playback never restarts on navigation.
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

    const [active, setActive] = useState(false);
    const [attempt, setAttempt] = useState(0);
    const [status, setStatus] = useState<PlaybackStatus>('idle');
    const [volume, setVolumeState] = useState(storedVolume);
    const [muted, setMuted] = useState(false);
    const [videoLayout, setVideoLayout] = useState<VideoLayout>('docked');

    const mode = stream.is_video_live && stream.video ? 'video' : 'audio';
    const audioUrl = stream.audio_url;

    // Start/stop the audio stream. Stopping drops the connection, so pressing
    // play again always joins the broadcast live rather than from a stale buffer.
    useEffect(() => {
        const audio = audioRef.current;

        if (!audio || !active || mode !== 'audio' || !audioUrl) {
            return;
        }

        const detach = attachStream(audio, audioUrl, () => setStatus('error'));

        audio.play().catch((error: unknown) => {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return;
            }

            // Autoplay blocked or stream refused: hand control back to the listener.
            setStatus('error');
        });

        return detach;
    }, [active, mode, audioUrl, attempt]);

    useEffect(() => {
        const audio = audioRef.current;

        if (audio) {
            audio.volume = volume;
            audio.muted = muted;
        }
    }, [volume, muted]);

    // Lock screen / headset controls.
    useEffect(() => {
        if (!('mediaSession' in navigator)) {
            return;
        }

        const program = broadcast.current?.program.title;
        const track = nowPlaying.track;

        navigator.mediaSession.metadata = new MediaMetadata(
            track
                ? {
                      title: track.title,
                      artist: track.artist ?? station.name,
                      album: program ?? station.name,
                  }
                : {
                      title: program ?? station.name,
                      artist: station.name,
                      album: station.tagline ?? undefined,
                  },
        );
        navigator.mediaSession.setActionHandler('play', () => setActive(true));
        navigator.mediaSession.setActionHandler('pause', () =>
            setActive(false),
        );
    }, [
        broadcast.current?.program.title,
        nowPlaying.track,
        station.name,
        station.tagline,
    ]);

    const start = useCallback(() => {
        setStatus('loading');
        setActive(true);
        setAttempt((n) => n + 1);
    }, []);

    const stop = useCallback(() => {
        setActive(false);
        setStatus('idle');
    }, []);

    const toggle = useCallback(() => {
        if (active && status !== 'error') {
            stop();
        } else {
            start();
        }
    }, [active, status, start, stop]);

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
        active,
        status: active ? status : 'idle',
        volume,
        muted,
        videoLayout,
        toggle,
        start,
        stop,
        setVolume,
        toggleMute: () => setMuted((m) => !m),
        setVideoLayout,
    };

    return (
        <PlayerContext.Provider value={api}>
            {children}
            <audio
                ref={audioRef}
                preload="none"
                onWaiting={() => setStatus('loading')}
                onPlaying={() => setStatus('playing')}
                onError={(event) => {
                    if (event.currentTarget.getAttribute('src')) {
                        setStatus('error');
                    }
                }}
            />
        </PlayerContext.Provider>
    );
}

export function usePlayer(): PlayerApi {
    const player = useContext(PlayerContext);

    if (!player) {
        throw new Error('usePlayer() must be used inside <PlayerProvider>.');
    }

    return player;
}
