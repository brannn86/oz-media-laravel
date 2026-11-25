import type { ReactNode } from 'react';
import { useNow } from '@/hooks/use-now';
import {
    ozubathonClock,
    ozubathonDates,
    ozubathonStart,
} from '@/lib/ozubathon';
import { cn } from '@/lib/utils';
import type { Ozubathon } from '@/types';

const ASSETS = '/images/ozubathon';

/**
 * The poster's ground: crumpled white paper, torn open across the middle
 * onto a yellow band printed in halftone. Children sit on top of it.
 */
export function OzubathonGround({
    children,
    className,
    bandClassName,
}: {
    children: ReactNode;
    className?: string;
    /** Where the torn band sits (its top and bottom). */
    bandClassName?: string;
}) {
    return (
        <div
            className={cn(
                'relative isolate overflow-hidden bg-[#f1f0ec] bg-cover bg-center',
                className,
            )}
            style={{ backgroundImage: `url(${ASSETS}/paper.webp)` }}
        >
            <div
                aria-hidden
                className={cn(
                    'ozb-band pointer-events-none absolute inset-x-0 -z-10',
                    bandClassName ?? 'top-[18%] bottom-[14%]',
                )}
                style={{
                    backgroundImage: `url(${ASSETS}/band-halftone.webp), url(${ASSETS}/paper.webp)`,
                    maskImage: `url(${ASSETS}/torn-band.svg)`,
                }}
            />
            {children}
        </div>
    );
}

/** The poster's four-point sparkle, drawn. */
export function Sparkle({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 24 24"
            aria-hidden
            className={cn('text-oz-black', className)}
        >
            <path
                d="M12 0c.9 6.6 4.5 10.6 12 12-7.5 1.4-11.1 5.4-12 12-.9-6.6-4.5-10.6-12-12C7.5 10.6 11.1 6.6 12 0Z"
                fill="currentColor"
                stroke="#fff"
                strokeWidth="1.4"
                paintOrder="stroke"
            />
        </svg>
    );
}

/**
 * The 55 Ozubathon logo on its halftone burst, with the poster's black
 * tape ("On-air · Online · On-ground") and yellow date sticker.
 */
export function OzubathonLockup({
    event,
    as: Heading = 'h1',
    size = 'large',
    className,
}: {
    event: Ozubathon;
    as?: 'h1' | 'h2';
    size?: 'large' | 'compact';
    className?: string;
}) {
    const compact = size === 'compact';

    return (
        <div className={cn('relative flex flex-col items-start', className)}>
            <div
                className={cn(
                    'relative',
                    compact
                        ? 'w-[min(78vw,20rem)] lg:w-[26rem]'
                        : 'w-[min(86vw,22rem)] lg:w-full lg:max-w-[30rem]',
                )}
            >
                <img
                    src={`${ASSETS}/burst.webp`}
                    alt=""
                    aria-hidden
                    className="ozb-slap absolute top-1/2 left-[42%] w-[86%] max-w-none -translate-x-1/2 -translate-y-1/2 -rotate-6 [--ozb-delay:0ms]"
                />
                <Sparkle className="absolute -top-1 right-[6%] size-7 sm:size-9" />
                <Sparkle className="absolute top-[14%] right-[-2%] size-4 sm:size-5" />
                <Heading className="relative">
                    <img
                        src={`${ASSETS}/logo.svg`}
                        alt="55 Ozubathon: loud and live"
                        className="ozb-slap relative w-full drop-shadow-[0_10px_14px_rgba(0,0,0,0.28)] [--ozb-delay:120ms]"
                    />
                </Heading>
            </div>

            <p
                className={cn(
                    'ozb-slap relative -mt-2 ml-[6%] -rotate-[4deg] bg-oz-black px-3 py-1.5 font-ozb-display leading-none tracking-wide text-oz-white uppercase shadow-[0_8px_16px_-6px_rgba(0,0,0,0.5)] [--ozb-delay:220ms]',
                    compact ? 'text-lg' : 'text-xl sm:text-2xl',
                )}
            >
                On-air · Online · On-ground
            </p>
            <p
                className={cn(
                    'ozb-slap relative mt-2 ml-[14%] -rotate-[3deg] border-[3px] border-oz-white bg-ozb-yellow px-3 py-1 font-ozb-display leading-none text-oz-black uppercase shadow-[0_8px_16px_-6px_rgba(0,0,0,0.45)] [--ozb-delay:300ms]',
                    compact ? 'text-2xl' : 'text-3xl sm:text-4xl',
                )}
            >
                <time dateTime={event.starts_at}>{ozubathonDates(event)}</time>
            </p>
            {!compact && (
                // The poster's outlined sticker line: what the event is.
                <p className="ozb-slap relative mt-3 ml-[4%] rotate-[1.5deg] max-lg:mr-20 bg-oz-white px-3 py-1.5 font-ozb-display text-xl leading-tight tracking-wide text-ozb-yellow uppercase shadow-[0_8px_16px_-6px_rgba(0,0,0,0.35)] [paint-order:stroke_fill] [-webkit-text-stroke:5px_var(--color-oz-black)] [--ozb-delay:360ms] sm:text-2xl">
                    {event.hours} hours live · OZ Radio’s 55th anniversary
                </p>
            )}
        </div>
    );
}

/**
 * The 55-hour meter, the signature: a countdown to the start, then "hour
 * N of 55" with one tick per hour (the current one pulsing), then all 55
 * filled. Black tape, yellow numerals.
 */
export function OzubathonMeter({
    event,
    initialNow,
    className,
}: {
    event: Ozubathon;
    /** The server's time, so the first render matches. */
    initialNow: number;
    className?: string;
}) {
    const now = useNow(initialNow, 1_000);
    const clock = ozubathonClock(event, now);
    const { days, hours, minutes, seconds } = clock.countdown;
    const pad = (n: number) => String(n).padStart(2, '0');

    return (
        <section
            aria-label="Ozubathon clock"
            className={cn(
                'relative bg-oz-black px-4 pt-3 pb-4 text-oz-white shadow-[0_14px_30px_-12px_rgba(0,0,0,0.55)] sm:px-5',
                className,
            )}
        >
            <img
                src={`${ASSETS}/pocket-watch.webp`}
                alt=""
                aria-hidden
                className="ozb-slap pointer-events-none absolute -top-9 right-3 w-14 rotate-12 sm:-top-10 sm:-right-5 [--ozb-delay:420ms] sm:w-20"
            />

            {/* Room on the right for the pocket watch. */}
            <div className="pr-14 sm:pr-14">
                {clock.phase === 'before' && (
                    <>
                        <p className="font-ozb text-sm font-semibold text-oz-white/80">
                            Goes live {ozubathonStart(event)}, in
                        </p>
                        <p
                            role="timer"
                            aria-label={`${days} days, ${hours} hours and ${minutes} minutes to go`}
                            className="mt-1 flex items-end gap-3 font-ozb-display leading-none text-ozb-yellow tabular-nums sm:gap-4"
                        >
                            {[
                                [days, 'days'],
                                [pad(hours), 'hrs'],
                                [pad(minutes), 'min'],
                                [pad(seconds), 'sec'],
                            ].map(([value, unit]) => (
                                <span
                                    key={unit}
                                    className="flex items-end gap-1"
                                    aria-hidden
                                >
                                    <span className="text-4xl sm:text-5xl">
                                        {value}
                                    </span>
                                    <span className="pb-1 font-ozb text-xs font-semibold text-oz-white/70">
                                        {unit}
                                    </span>
                                </span>
                            ))}
                        </p>
                    </>
                )}

                {clock.phase === 'live' && (
                    <p className="flex flex-wrap items-baseline gap-x-3 font-ozb-display leading-none uppercase">
                        <span className="inline-flex items-center gap-2 text-xl text-oz-white sm:text-3xl">
                            <span className="size-3 rounded-full bg-oz-red motion-safe:animate-pulse" />
                            Live
                        </span>
                        <span className="text-3xl text-ozb-yellow tabular-nums sm:text-5xl">
                            Hour {clock.hour}
                        </span>
                        <span className="text-xl text-oz-white/70 sm:text-3xl">
                            of {event.hours}
                        </span>
                    </p>
                )}

                {clock.phase === 'after' && (
                    <p className="font-ozb-display text-2xl leading-none text-ozb-yellow uppercase sm:text-4xl">
                        {event.hours} hours, loud and live. Thank you!
                    </p>
                )}
            </div>

            <p className="sr-only">
                {clock.hoursDone} of {event.hours} hours done.
            </p>
            <ol
                aria-hidden
                className="mt-3 grid grid-cols-[repeat(55,minmax(0,1fr))] items-end gap-[2px]"
            >
                {Array.from({ length: event.hours }, (_, i) => {
                    const done = i < clock.hoursDone;
                    const current =
                        clock.phase === 'live' && i === clock.hoursDone;

                    return (
                        <li
                            key={i}
                            // Each hour is a torn strip of tape.
                            style={{
                                maskImage: `url(${ASSETS}/torn-tick.svg)`,
                                maskSize: '100% 100%',
                            }}
                            className={cn(
                                'h-4 sm:h-5',
                                // Every fifth hour stands taller, like a ruler.
                                (i + 1) % 5 === 0 && 'h-6 sm:h-7',
                                done && 'bg-ozb-yellow',
                                current &&
                                    'bg-oz-red motion-safe:animate-pulse',
                                !done && !current && 'bg-oz-white/20',
                            )}
                        />
                    );
                })}
            </ol>
            <p className="mt-1.5 flex justify-between font-ozb text-xs font-semibold text-oz-white/60 tabular-nums">
                <span>Hour 1</span>
                <span>Hour {event.hours}</span>
            </p>
        </section>
    );
}
