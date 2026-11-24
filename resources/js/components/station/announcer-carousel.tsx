import type { CSSProperties } from 'react';
import { initials } from '@/components/station/announcer-avatar';
import { useStationDetails } from '@/components/station/station-details';
import { cn } from '@/lib/utils';
import type { Announcer } from '@/types';

/** Card width plus its gap, in px (w-60 + pr-5). */
const CARD_SPACE = 260;
/** One loop must be wider than the widest screen, so the strip never runs out. */
const MIN_LOOP_WIDTH = 2600;
/** Scroll speed in px per second. */
const SPEED = 45;

/**
 * The announcers as an endlessly scrolling strip. It pauses while hovered or
 * while a card has keyboard focus, and becomes a plain scrollable row for
 * visitors who prefer reduced motion. A tap opens the announcer's details.
 */
export function AnnouncerCarousel({ announcers }: { announcers: Announcer[] }) {
    const { openAnnouncer } = useStationDetails();

    // Repeat the list until one loop is wide enough, then render that loop
    // twice: sliding by exactly half the strip lands on an identical frame.
    const repeats = Math.max(
        1,
        Math.ceil(MIN_LOOP_WIDTH / (announcers.length * CARD_SPACE)),
    );
    const loop = Array.from({ length: repeats }, () => announcers).flat();
    const duration = (loop.length * CARD_SPACE) / SPEED;

    return (
        <div className="group overflow-hidden motion-reduce:overflow-x-auto">
            <ul
                className="flex w-max animate-[oz-marquee_var(--oz-marquee-duration)_linear_infinite] group-focus-within:[animation-play-state:paused] group-hover:[animation-play-state:paused] motion-reduce:animate-none"
                style={
                    {
                        '--oz-marquee-duration': `${duration}s`,
                    } as CSSProperties
                }
            >
                {[...loop, ...loop].map((announcer, index) => {
                    // Only the first copy is reachable by keyboard and screen
                    // readers; the repeats still take the mouse (no `inert`).
                    const copy = index >= announcers.length;

                    return (
                        <li
                            key={`${announcer.id}-${index}`}
                            aria-hidden={copy || undefined}
                            className={cn(
                                'w-60 shrink-0 pr-5',
                                // Without motion, the copies would just repeat the row.
                                copy && 'motion-reduce:hidden',
                            )}
                        >
                            <button
                                type="button"
                                onClick={() => openAnnouncer(announcer.id)}
                                aria-haspopup="dialog"
                                tabIndex={copy ? -1 : undefined}
                                aria-label={announcer.display_name}
                                className="group/card block w-full text-left focus-visible:outline-none"
                            >
                                {/* Black card: the strip sits on the yellow section. */}
                                <span className="relative block aspect-[4/5] overflow-hidden rounded-[1.75rem] bg-oz-black ring-oz-white transition-transform group-hover/card:-translate-y-1 group-focus-visible/card:ring-4">
                                    {announcer.avatar_url ? (
                                        <img
                                            src={announcer.avatar_url}
                                            alt=""
                                            loading="lazy"
                                            className="size-full object-cover object-top"
                                        />
                                    ) : (
                                        <span
                                            aria-hidden
                                            className="absolute bottom-2 left-4 font-oz-display text-8xl leading-none font-extrabold tracking-tight text-oz-yellow"
                                        >
                                            {initials(announcer.display_name)}
                                        </span>
                                    )}
                                    {/* Stage name, revealed on hover or keyboard focus. */}
                                    <span
                                        aria-hidden
                                        className="absolute inset-0 flex items-end bg-gradient-to-t from-oz-black/85 via-oz-black/30 to-transparent p-5 opacity-0 transition-opacity duration-300 group-hover/card:opacity-100 group-focus-visible/card:opacity-100"
                                    >
                                        <span className="font-oz-display text-2xl leading-tight font-extrabold tracking-tight text-oz-white">
                                            {announcer.display_name}
                                        </span>
                                    </span>
                                </span>
                            </button>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}
