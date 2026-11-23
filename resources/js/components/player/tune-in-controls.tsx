import {
    Headphones,
    LoaderCircle,
    Play,
    RotateCcw,
    Square,
    Video,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { usePlayer } from '@/components/player/player-context';
import type { StreamMode } from '@/components/player/player-context';
import { cn } from '@/lib/utils';

const pill =
    'inline-flex h-14 shrink-0 items-center gap-2.5 rounded-full px-5 text-base font-bold transition-[transform,background-color,color] focus-visible:ring-4 focus-visible:ring-oz-black/40 focus-visible:ring-offset-2 focus-visible:ring-offset-oz-yellow focus-visible:outline-none active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-45 disabled:active:scale-100 sm:px-6 sm:text-lg';
const primary = cn(pill, 'bg-oz-black text-oz-white hover:scale-[1.02]');
const secondary = cn(
    pill,
    'border-2 border-oz-black text-oz-black hover:bg-oz-black hover:text-oz-yellow',
);

/**
 * The hero's player controls: "Tune in", then a choice between radio and
 * video, then stop / switch. Every state is one fixed-height row, so the
 * hero never changes size while the listener uses it.
 */
export function TuneInControls() {
    const player = usePlayer();
    const [choosing, setChoosing] = useState(false);
    const { active, mode, status, audioAvailable, videoAvailable } = player;

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

    const choose = (next: StreamMode) => {
        setChoosing(false);
        player.play(next); // Inside the tap, so mobile browsers allow sound.
    };

    if (active) {
        const other: StreamMode = mode === 'audio' ? 'video' : 'audio';
        const otherAvailable =
            other === 'video' ? videoAvailable : audioAvailable;

        return (
            <div className="flex h-14 items-center gap-3">
                <ActiveButton />
                {otherAvailable && (
                    <button
                        type="button"
                        onClick={() => player.play(other)}
                        className={secondary}
                    >
                        {other === 'video' ? (
                            <Video className="size-5" aria-hidden />
                        ) : (
                            <Headphones className="size-5" aria-hidden />
                        )}
                        <span>
                            <span className="sr-only sm:not-sr-only">
                                Switch to{' '}
                            </span>
                            {other === 'video' ? 'video' : 'radio'}
                        </span>
                    </button>
                )}
                {status === 'error' && (
                    <p className="hidden text-sm font-semibold sm:block">
                        The stream isn't answering.
                    </p>
                )}
            </div>
        );
    }

    if (choosing) {
        return (
            <div
                className="flex h-14 items-center gap-2 sm:gap-3"
                role="group"
                aria-label="Choose how to tune in"
            >
                <button
                    type="button"
                    onClick={() => choose('audio')}
                    disabled={!audioAvailable}
                    autoFocus
                    className={primary}
                >
                    <Headphones className="size-5" aria-hidden />
                    Radio
                </button>
                <button
                    type="button"
                    onClick={() => choose('video')}
                    disabled={!videoAvailable}
                    title={
                        videoAvailable
                            ? undefined
                            : 'The video livestream is off right now'
                    }
                    className={primary}
                >
                    <Video className="size-5" aria-hidden />
                    Video
                    {!videoAvailable && (
                        <span className="text-xs font-semibold text-oz-white/70">
                            offline
                        </span>
                    )}
                </button>
                <button
                    type="button"
                    onClick={() => setChoosing(false)}
                    aria-label="Cancel"
                    className="grid size-14 shrink-0 place-items-center rounded-full text-oz-black hover:bg-oz-black/10 focus-visible:ring-4 focus-visible:ring-oz-black/40 focus-visible:outline-none"
                >
                    <X className="size-6" />
                </button>
            </div>
        );
    }

    return (
        <div className="flex h-14 items-center">
            <button
                type="button"
                onClick={() => setChoosing(true)}
                aria-haspopup="true"
                className={cn(primary, 'pr-7 pl-5')}
            >
                <Play className="size-6 fill-current" aria-hidden />
                Tune in
            </button>
        </div>
    );
}

/** Stop, or whatever the stream needs right now (connecting, retry, a tap). */
function ActiveButton() {
    const { mode, status, stop, play } = usePlayer();

    if (mode === 'video') {
        return (
            <button type="button" onClick={stop} className={primary}>
                <Square className="size-5 fill-current" aria-hidden />
                Stop watching
            </button>
        );
    }

    if (status === 'blocked' || status === 'error') {
        return (
            <button
                type="button"
                onClick={() => play('audio')}
                className={primary}
            >
                {status === 'error' ? (
                    <RotateCcw className="size-5" aria-hidden />
                ) : (
                    <Play className="size-5 fill-current" aria-hidden />
                )}
                {status === 'error' ? 'Try again' : 'Tap to listen'}
            </button>
        );
    }

    const connecting = status === 'loading' || status === 'reconnecting';

    return (
        <button type="button" onClick={stop} className={primary}>
            {connecting ? (
                <LoaderCircle
                    className="size-5 motion-safe:animate-spin"
                    aria-hidden
                />
            ) : (
                <Square className="size-5 fill-current" aria-hidden />
            )}
            <span aria-live="polite">
                {status === 'reconnecting'
                    ? 'Reconnecting…'
                    : status === 'loading'
                      ? 'Connecting…'
                      : 'Stop'}
            </span>
        </button>
    );
}
