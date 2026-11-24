import {
    Headphones,
    LoaderCircle,
    Pause,
    Play,
    RotateCcw,
    Square,
    Video,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import {
    ReactionButtons,
    ReactionOverlay,
    useReactions,
} from '@/components/live/reactions';
import { AudioVisualizer } from '@/components/player/audio-visualizer';
import { usePlayer } from '@/components/player/player-context';
import type { StreamMode } from '@/components/player/player-context';
import {
    VideoStage,
    feedLabel,
    useFeedStatus,
} from '@/components/player/video-dock';
import { cn } from '@/lib/utils';
import type { Broadcast } from '@/types';

/**
 * The home hero's player: one 16:9 box that is, in turn,
 * - a big play button, which morphs into a Radio / Video choice;
 * - the radio: play/pause and the song at the top, a live bar visualizer,
 *   and how many are listening (from Icecast) underneath;
 * - the video livestream (with the intermission while it's off).
 *
 * The persistent player (PlayerProvider) does the actual playing, so the
 * radio keeps going when the listener moves to another page.
 *
 * `header` (e.g. "On air now") sits in the row above the box, next to the
 * switch / stop controls, which stay outside the box so nothing covers the
 * YouTube player's own buttons.
 */
export function HeroPlayer({
    broadcast,
    header,
}: {
    broadcast: Broadcast;
    header: ReactNode;
}) {
    const player = usePlayer();
    const reactions = useReactions(player.station.slug);
    const { active, mode, hasStarted, setVideoInHero } = player;
    const [choosing, setChoosing] = useState(false);

    const watching = active && mode === 'video';
    const listening = hasStarted && mode === 'audio';

    // While the hero is on the page it shows the video itself, not the dock.
    useEffect(() => {
        setVideoInHero(true);

        return () => setVideoInHero(false);
    }, [setVideoInHero]);

    useEffect(() => {
        if (!choosing) {
            return;
        }

        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setChoosing(false);
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [choosing]);

    return (
        <div>
            <div className="mb-4 flex min-h-9 flex-wrap items-center justify-between gap-3">
                {header}
                <HeroPlayerControls onReact={reactions.send} />
            </div>
            <div className="relative aspect-video w-full overflow-hidden rounded-3xl bg-oz-black text-oz-white shadow-xl ring-1 ring-oz-black/10">
                {watching ? (
                    <HeroVideo floaters={reactions.floaters} />
                ) : listening ? (
                    <HeroRadio broadcast={broadcast} />
                ) : (
                    <TuneIn
                        choosing={choosing}
                        onChoosing={setChoosing}
                        onChoose={(next) => {
                            setChoosing(false);
                            player.play(next); // Inside the tap: sound allowed.
                        }}
                    />
                )}
            </div>
        </div>
    );
}

/**
 * Play, morphing into the two ways to tune in. Both layers are always
 * rendered so the change animates; the hidden one is inert.
 */
function TuneIn({
    choosing,
    onChoosing,
    onChoose,
}: {
    choosing: boolean;
    onChoosing: (choosing: boolean) => void;
    onChoose: (mode: StreamMode) => void;
}) {
    const { audioAvailable, videoAvailable } = usePlayer();
    const option =
        'flex flex-col items-center gap-2 rounded-3xl p-3 text-base font-bold transition-[transform,opacity] duration-300 ease-out focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-40 sm:gap-3 sm:text-xl';
    const disc =
        'grid size-16 place-items-center rounded-full bg-oz-yellow text-oz-black transition-transform group-hover:scale-105 sm:size-24 lg:size-28';

    return (
        <div className="absolute inset-0 grid place-items-center">
            <button
                type="button"
                onClick={() => onChoosing(true)}
                aria-haspopup="true"
                inert={choosing}
                className={cn(
                    'group col-start-1 row-start-1 flex flex-col items-center gap-3 rounded-3xl p-3 transition-[transform,opacity] duration-300 ease-out focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none sm:gap-5',
                    choosing && 'pointer-events-none scale-75 opacity-0',
                )}
            >
                <Play
                    className="size-16 translate-x-1 fill-oz-yellow text-oz-yellow transition-transform group-hover:scale-110 sm:size-28 lg:size-32"
                    aria-hidden
                />
                <span className="text-lg font-bold sm:text-2xl">Tune in</span>
            </button>

            <div
                role="group"
                aria-label="Choose how to tune in"
                inert={!choosing}
                className={cn(
                    'col-start-1 row-start-1 flex items-center gap-4 sm:gap-12',
                    !choosing && 'pointer-events-none',
                )}
            >
                <button
                    type="button"
                    onClick={() => onChoose('audio')}
                    disabled={!audioAvailable}
                    className={cn(
                        'group',
                        option,
                        choosing
                            ? 'translate-x-0 scale-100 opacity-100'
                            : 'translate-x-1/2 scale-50 opacity-0',
                    )}
                >
                    <span className={disc}>
                        <Headphones className="size-7 sm:size-11" aria-hidden />
                    </span>
                    Radio
                </button>
                <button
                    type="button"
                    onClick={() => onChoose('video')}
                    disabled={!videoAvailable}
                    title={
                        videoAvailable
                            ? undefined
                            : 'There is no video livestream right now'
                    }
                    className={cn(
                        'group',
                        option,
                        choosing
                            ? 'translate-x-0 scale-100 opacity-100'
                            : '-translate-x-1/2 scale-50 opacity-0',
                    )}
                >
                    <span className={disc}>
                        <Video className="size-7 sm:size-11" aria-hidden />
                    </span>
                    <span>
                        Video
                        {!videoAvailable && (
                            <span className="block text-xs font-semibold text-oz-white/60">
                                offline
                            </span>
                        )}
                    </span>
                </button>
            </div>

            {choosing && (
                <button
                    type="button"
                    onClick={() => onChoosing(false)}
                    aria-label="Cancel"
                    className="absolute top-3 right-3 grid size-10 place-items-center rounded-full text-oz-white/80 hover:bg-oz-white/10 hover:text-oz-white focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none sm:top-5 sm:right-5"
                >
                    <X className="size-5" />
                </button>
            )}
        </div>
    );
}

/** The radio: play/pause with what's on at the top, bars, listener count. */
function HeroRadio({ broadcast }: { broadcast: Broadcast }) {
    const player = usePlayer();
    const { active, status, nowPlaying, analyser } = player;
    const track = nowPlaying.track;
    const program = broadcast.current?.program;
    const hosts = program?.announcers
        ?.map((host) => host.display_name)
        .join(' & ');
    const playing = active && status === 'playing';
    const connecting =
        active && (status === 'loading' || status === 'reconnecting');
    const needsTap = status === 'blocked' || status === 'error';

    const label = needsTap
        ? status === 'error'
            ? 'Try the radio again'
            : 'Tap to start the radio'
        : active
          ? 'Pause the radio'
          : 'Play the radio';

    return (
        <div className="absolute inset-0 flex flex-col">
            <div className="flex items-center gap-3 p-3 sm:gap-4 sm:p-6">
                <button
                    type="button"
                    onClick={() =>
                        active && !needsTap
                            ? player.stop()
                            : player.play('audio')
                    }
                    aria-label={label}
                    className="grid size-11 shrink-0 place-items-center rounded-full bg-oz-white text-oz-black transition-transform hover:scale-105 focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none sm:size-16"
                >
                    {connecting ? (
                        <LoaderCircle
                            className="size-5 motion-safe:animate-spin sm:size-7"
                            aria-hidden
                        />
                    ) : status === 'error' ? (
                        <RotateCcw className="size-5 sm:size-7" aria-hidden />
                    ) : active && !needsTap ? (
                        <Pause
                            className="size-5 fill-current sm:size-7"
                            aria-hidden
                        />
                    ) : (
                        <Play
                            className="size-5 translate-x-0.5 fill-current sm:size-7"
                            aria-hidden
                        />
                    )}
                </button>
                <div className="min-w-0" aria-live="polite">
                    <p className="truncate font-oz-display text-lg leading-tight font-extrabold sm:text-3xl">
                        {track?.title ?? program?.title ?? player.station.name}
                    </p>
                    <p className="truncate text-sm text-oz-white/75 sm:text-lg">
                        {track
                            ? (track.artist ?? player.station.name)
                            : hosts
                              ? `with ${hosts}`
                              : 'Live radio'}
                    </p>
                    {status === 'error' && (
                        <p className="text-xs font-semibold text-oz-yellow sm:text-sm">
                            The stream isn't answering.
                        </p>
                    )}
                </div>
            </div>

            <div className="min-h-0 flex-1 px-3 sm:px-6">
                <AudioVisualizer analyser={analyser} playing={playing} />
            </div>

            <p className="flex h-9 items-center gap-2 px-3 text-xs font-semibold text-oz-white/80 sm:h-12 sm:px-6 sm:text-sm">
                {nowPlaying.listeners !== null && (
                    <>
                        <Headphones className="size-4" aria-hidden />
                        {nowPlaying.listeners.toLocaleString('en-GB')}{' '}
                        {nowPlaying.listeners === 1 ? 'listener' : 'listeners'}{' '}
                        right now
                    </>
                )}
            </p>
        </div>
    );
}

/** The video livestream, filling the box; reactions float over it. */
function HeroVideo({
    floaters,
}: {
    floaters: ReturnType<typeof useReactions>['floaters'];
}) {
    const player = usePlayer();
    const source = player.stream.video;
    const [status, setStatus] = useFeedStatus(source, true);

    if (!source) {
        return null;
    }

    return (
        <section
            aria-label={`${player.station.name} video livestream, ${feedLabel(status).replace('· ', '')}`}
            className="absolute inset-0"
        >
            <VideoStage source={source} status={status} onStatus={setStatus} />
            <ReactionOverlay floaters={floaters} />
        </section>
    );
}

/** Switch radio/video and stop, plus reactions while watching. */
function HeroPlayerControls({
    onReact,
}: {
    onReact: ReturnType<typeof useReactions>['send'];
}) {
    const player = usePlayer();
    const { active, mode, audioAvailable, videoAvailable } = player;

    if (!active) {
        return null;
    }

    const other: StreamMode = mode === 'audio' ? 'video' : 'audio';
    const canSwitch = other === 'video' ? videoAvailable : audioAvailable;
    const pill =
        'inline-flex h-9 items-center gap-2 rounded-full px-4 text-sm font-bold transition-colors focus-visible:ring-4 focus-visible:ring-oz-black/30 focus-visible:outline-none';

    return (
        <div className="flex items-center gap-2">
            {mode === 'video' && (
                <div className="rounded-full bg-oz-black px-1">
                    <ReactionButtons onReact={onReact} />
                </div>
            )}
            {canSwitch && (
                <button
                    type="button"
                    onClick={() => player.play(other)}
                    className={cn(
                        pill,
                        'border-2 border-oz-black hover:bg-oz-black hover:text-oz-yellow',
                    )}
                >
                    {other === 'video' ? (
                        <Video className="size-4" aria-hidden />
                    ) : (
                        <Headphones className="size-4" aria-hidden />
                    )}
                    <span>
                        <span className="sr-only sm:not-sr-only">
                            Switch to{' '}
                        </span>
                        {other === 'video' ? 'video' : 'radio'}
                    </span>
                </button>
            )}
            {mode === 'video' && (
                <button
                    type="button"
                    onClick={player.stop}
                    className={cn(
                        pill,
                        'bg-oz-black text-oz-white hover:bg-oz-ink',
                    )}
                >
                    <Square className="size-3.5 fill-current" aria-hidden />
                    Stop
                </button>
            )}
        </div>
    );
}
