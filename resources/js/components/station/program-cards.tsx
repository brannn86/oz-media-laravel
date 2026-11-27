import { useEffect, useRef, useState } from 'react';
import { CoverImage } from '@/components/station/cover-image';
import { useStationDetails } from '@/components/station/station-details';
import { cn } from '@/lib/utils';
import type { Program } from '@/types';

/** Cards per page: three columns by two rows on wide screens. */
const PER_PAGE = 6;
/** How long each page stays before sliding to the next, in ms. */
const INTERVAL = 5000;

const reducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Every show's artwork, six at a time. The pages slide sideways on their own
 * and loop back to the first; hovering or focusing a card holds the current
 * page. A tap opens the show's description, schedule and hosts.
 */
export function ProgramCards({ programs }: { programs: Program[] }) {
    const { openProgram } = useStationDetails();
    const trackRef = useRef<HTMLDivElement>(null);
    const [page, setPage] = useState(0);
    const [paused, setPaused] = useState(false);

    const pages = Array.from(
        { length: Math.ceil(programs.length / PER_PAGE) },
        (_, index) => programs.slice(index * PER_PAGE, (index + 1) * PER_PAGE),
    );

    // The page nearest the track's left edge, so manual swipes count too.
    const currentPage = () => {
        const track = trackRef.current;

        if (!track) {
            return 0;
        }

        const offsets = Array.from(
            track.children as HTMLCollectionOf<HTMLElement>,
            (child) => Math.abs(child.offsetLeft - track.scrollLeft),
        );

        return offsets.indexOf(Math.min(...offsets));
    };

    const goTo = (index: number) => {
        const track = trackRef.current;
        const target = track?.children[index] as HTMLElement | undefined;

        if (track && target) {
            track.scrollTo({
                left: target.offsetLeft,
                behavior: reducedMotion() ? 'auto' : 'smooth',
            });
        }
    };

    useEffect(() => {
        if (pages.length < 2 || paused || reducedMotion()) {
            return;
        }

        const timer = window.setInterval(
            () => goTo((currentPage() + 1) % pages.length),
            INTERVAL,
        );

        return () => window.clearInterval(timer);
    }, [pages.length, paused]);

    return (
        <div
            onMouseEnter={() => setPaused(true)}
            onMouseLeave={() => setPaused(false)}
            onFocus={() => setPaused(true)}
            onBlur={() => setPaused(false)}
        >
            <div
                ref={trackRef}
                onScroll={() => setPage(currentPage())}
                className="relative flex snap-x snap-mandatory [scrollbar-width:none] gap-5 overflow-x-auto [&::-webkit-scrollbar]:hidden"
            >
                {pages.map((items, index) => (
                    <ul
                        key={index}
                        className="grid w-full shrink-0 snap-start grid-cols-2 gap-5 lg:grid-cols-3"
                    >
                        {items.map((program) => (
                            <li key={program.id}>
                                <button
                                    type="button"
                                    onClick={() => openProgram(program.id)}
                                    aria-haspopup="dialog"
                                    aria-label={program.title}
                                    className="group relative block aspect-[5/4] w-full overflow-hidden rounded-[1.75rem] bg-oz-yellow transition-transform hover:-translate-y-1 focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:ring-offset-2 focus-visible:outline-none"
                                >
                                    <CoverImage
                                        src={program.cover_image_url}
                                        label={program.title}
                                        className="absolute inset-0 size-full transition-transform duration-500 group-hover:scale-105"
                                    />
                                </button>
                            </li>
                        ))}
                    </ul>
                ))}
            </div>

            {pages.length > 1 && (
                <div className="mt-6 flex justify-center gap-2">
                    {pages.map((_, index) => (
                        <button
                            key={index}
                            type="button"
                            onClick={() => goTo(index)}
                            aria-label={`Show page ${index + 1} of ${pages.length}`}
                            aria-current={index === page || undefined}
                            className={cn(
                                'h-2.5 rounded-full transition-all focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none',
                                index === page
                                    ? 'w-8 bg-oz-black'
                                    : 'w-2.5 bg-oz-black/20 hover:bg-oz-black/40',
                            )}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
