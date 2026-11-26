import type { CSSProperties } from 'react';

// Phrases repeated per loop, so even a short list fills a wide screen.
const MIN_PHRASES_PER_LOOP = 12;
const SECONDS_PER_PHRASE = 3;

/**
 * The Ozubathon's rolling yellow band between the hero and Recently played.
 * Screen readers get the phrases once; the scrolling copies are decorative.
 */
export function OzubathonTicker({ phrases }: { phrases: string[] }) {
    if (phrases.length === 0) {
        return null;
    }

    const repeats = Math.ceil(MIN_PHRASES_PER_LOOP / phrases.length);
    const loop = Array.from({ length: repeats }, () => phrases).flat();

    return (
        <div className="overflow-hidden border-y-2 border-oz-black bg-ozb-yellow py-2.5 text-oz-black">
            <p className="sr-only">{phrases.join(' · ')}</p>
            <div
                aria-hidden
                className="flex w-max animate-[oz-marquee_var(--oz-marquee-duration)_linear_infinite] motion-reduce:animate-none"
                style={
                    {
                        '--oz-marquee-duration': `${loop.length * SECONDS_PER_PHRASE}s`,
                    } as CSSProperties
                }
            >
                {[...loop, ...loop].map((phrase, index) => (
                    <span
                        key={index}
                        className="flex shrink-0 items-center font-ozb text-lg font-extrabold whitespace-nowrap uppercase italic sm:text-2xl"
                    >
                        <span className="px-5 sm:px-7">{phrase}</span>
                        <span className="text-xs sm:text-sm">◆</span>
                    </span>
                ))}
            </div>
        </div>
    );
}
