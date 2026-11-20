import { useEchoPublic } from '@laravel/echo-react';
import { useCallback, useRef, useState } from 'react';
import { apiFetch } from '@/lib/api';
import type { ReactionType } from '@/types';

const EMOJI: Record<ReactionType, string> = {
    fire: '🔥',
    clap: '👏',
    heart: '❤️',
};

const LABEL: Record<ReactionType, string> = {
    fire: 'Fire',
    clap: 'Applause',
    heart: 'Love it',
};

type Floater = { id: number; type: ReactionType; left: number };

/** Keep the overlay light when a big audience reacts at once. */
const MAX_FLOATERS = 30;

/**
 * Floating reactions over the video livestream: everyone's (via Reverb
 * `station.{slug}.reactions`) plus your own, shown instantly.
 */
export function useReactions(stationSlug: string) {
    const [floaters, setFloaters] = useState<Floater[]>([]);
    const nextId = useRef(0);

    const float = useCallback((type: ReactionType) => {
        const id = nextId.current++;

        setFloaters((current) =>
            [...current, { id, type, left: 10 + Math.random() * 75 }].slice(
                -MAX_FLOATERS,
            ),
        );
        window.setTimeout(
            () => setFloaters((current) => current.filter((f) => f.id !== id)),
            2600,
        );
    }, []);

    useEchoPublic<{ type: ReactionType }>(
        `station.${stationSlug}.reactions`,
        '.reaction.sent',
        ({ type }) => {
            if (type in EMOJI) {
                float(type);
            }
        },
    );

    const send = useCallback(
        (type: ReactionType) => {
            float(type);
            // The server throttles bursts; a refused reaction just doesn't reach others.
            void apiFetch(`/api/v1/stations/${stationSlug}/reactions`, {
                method: 'POST',
                body: { type },
            }).catch(() => undefined);
        },
        [stationSlug, float],
    );

    return { floaters, send };
}

export function ReactionOverlay({ floaters }: { floaters: Floater[] }) {
    return (
        <div
            aria-hidden
            className="pointer-events-none absolute inset-0 overflow-hidden"
        >
            {floaters.map((floater) => (
                <span
                    key={floater.id}
                    className="oz-reaction absolute bottom-2 text-3xl"
                    style={{ left: `${floater.left}%` }}
                >
                    {EMOJI[floater.type]}
                </span>
            ))}
        </div>
    );
}

export function ReactionButtons({
    onReact,
}: {
    onReact: (type: ReactionType) => void;
}) {
    return (
        <div
            className="flex gap-1"
            role="group"
            aria-label="React to the livestream"
        >
            {(Object.keys(EMOJI) as ReactionType[]).map((type) => (
                <button
                    key={type}
                    type="button"
                    onClick={() => onReact(type)}
                    aria-label={LABEL[type]}
                    title={LABEL[type]}
                    className="grid size-9 place-items-center rounded-full text-xl transition-transform hover:scale-110 hover:bg-oz-white/10 focus-visible:ring-2 focus-visible:ring-oz-yellow focus-visible:outline-none active:scale-95"
                >
                    {EMOJI[type]}
                </button>
            ))}
        </div>
    );
}
