import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { CSSProperties, ReactNode } from 'react';
import {
    OzubathonGround,
    Sparkle,
} from '@/components/ozubathon/ozubathon-parts';
import { BrandLogo } from '@/components/station/brand-logo';
import { useNow } from '@/hooks/use-now';
import { ozubathonClock } from '@/lib/ozubathon';
import { cn } from '@/lib/utils';
import stationRoutes from '@/routes/station';
import type { Ozubathon, PageMeta } from '@/types';

type Station = { slug: string; name: string; tagline: string | null };

type Event = Ozubathon & {
    station: { slug: string; name: string };
    now: string;
};

type Props = {
    meta: PageMeta;
    stations: Station[];
    /** The station running the Ozubathon theme, for its sticker. */
    ozubathon: Event | null;
};

/**
 * ozmedianet.com: the OZ Media logo, "Above and Beyond", and a door to each
 * station in its own look, all in the first screen; the footer below. While
 * a station runs the Ozubathon, the poster's torn band runs behind the doors
 * and the host's door carries the countdown sticker.
 */
export default function CorporateHome() {
    const { meta, stations, ozubathon } = usePage<Props>().props;

    const hero = (
        <main className="mx-auto flex min-h-svh w-full max-w-5xl flex-col items-center justify-center gap-[clamp(2rem,6svh,4rem)] px-4 py-10 sm:px-6">
            <div className="flex flex-col items-center text-center">
                <h1>
                    <BrandLogo
                        brand="oz-media"
                        className="h-[clamp(4rem,14svh,8rem)]"
                    />
                </h1>
                <p className="mt-4 font-oz-display text-[clamp(1.5rem,4vw,2.5rem)] leading-none font-extrabold tracking-tight">
                    Above and Beyond
                </p>
            </div>

            <nav aria-label="Our stations" className="w-full">
                <ul className="grid gap-4 sm:grid-cols-2 sm:gap-6">
                    {stations.map((station) => (
                        <li key={station.slug}>
                            <StationDoor
                                station={station}
                                event={
                                    ozubathon?.station.slug === station.slug
                                        ? ozubathon
                                        : null
                                }
                            />
                        </li>
                    ))}
                </ul>
            </nav>
        </main>
    );

    return (
        <div className="min-h-svh bg-oz-white font-oz text-oz-black antialiased">
            <Head title={meta.title} />

            {ozubathon ? (
                <OzubathonGround bandClassName="top-[52%] bottom-[8%] sm:top-[46%] sm:bottom-[14%]">
                    {hero}
                </OzubathonGround>
            ) : (
                hero
            )}

            <footer className="bg-oz-black text-oz-white">
                <div className="mx-auto flex max-w-5xl flex-col gap-6 px-4 py-12 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <BrandLogo brand="oz-media" onDark className="h-10" />
                    <ul className="flex flex-wrap gap-x-6 gap-y-2 text-sm font-semibold">
                        {stations.map((station) => (
                            <li key={station.slug}>
                                <Link
                                    href={stationRoutes.home(station.slug)}
                                    className="hover:text-oz-yellow focus-visible:text-oz-yellow focus-visible:outline-none"
                                >
                                    {station.name}
                                </Link>
                            </li>
                        ))}
                    </ul>
                    <p className="text-sm text-oz-white/60">
                        © {new Date().getFullYear()} OZ Media. All rights
                        reserved.
                    </p>
                </div>
            </footer>
        </div>
    );
}

/**
 * One station's door, dressed as its own site: OZ Bali's plum sunset, or
 * OZ Bandung's white and yellow. The whole card is the link.
 */
function StationDoor({
    station,
    event,
}: {
    station: Station;
    event: Event | null;
}) {
    const bali = station.slug === 'oz-bali';

    return (
        <Link
            href={stationRoutes.home(station.slug)}
            aria-label={`Listen to ${station.name}${station.tagline ? `: ${station.tagline}` : ''}`}
            className={cn(
                'group relative isolate flex h-[clamp(11rem,26svh,17rem)] flex-col items-center justify-center gap-3 rounded-[1.75rem] border-2 border-oz-black p-6 shadow-[6px_6px_0_#000] transition-[translate,box-shadow] duration-200 hover:-translate-y-1 hover:shadow-[8px_10px_0_#000] focus-visible:ring-4 focus-visible:ring-oz-black/30 focus-visible:ring-offset-4 focus-visible:outline-none active:translate-y-0 active:shadow-[4px_4px_0_#000]',
                bali ? 'bali' : 'bg-oz-white',
            )}
        >
            {bali ? <SunsetBackdrop /> : <HalftoneBackdrop />}
            <BrandLogo
                brand={station.slug}
                onDark={bali}
                className="h-[clamp(4.5rem,13svh,8rem)] transition-transform duration-300 group-hover:-rotate-2"
            />
            <span
                className={cn(
                    'inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-bold',
                    bali
                        ? 'bg-[var(--bali-gold)] text-oz-black'
                        : 'bg-oz-black text-oz-white',
                )}
            >
                Listen live
                <ArrowRight
                    className="size-4 transition-transform group-hover:translate-x-0.5"
                    aria-hidden
                />
            </span>
            {event && <OzubathonSticker event={event} />}
        </Link>
    );
}

/** OZ Bali's sunset, cropped: the arcs and striped sun rising from the card's floor. */
function SunsetBackdrop() {
    return (
        <Backdrop>
            <div
                className="bali-arcs top-full!"
                style={{ '--bali-dial': '9rem' } as CSSProperties}
            />
            <div className="absolute top-full left-1/2 aspect-square w-36 -translate-1/2">
                <div className="bali-sun" />
            </div>
        </Backdrop>
    );
}

/** OZ Bandung's daylight white, with its yellow in halftone. */
function HalftoneBackdrop() {
    return (
        <Backdrop>
            <div className="absolute inset-0 bg-[radial-gradient(rgba(242,189,81,0.75)_1.6px,transparent_1.8px)] [mask-image:linear-gradient(to_top,#000,transparent_75%)] bg-[length:10px_10px]" />
        </Backdrop>
    );
}

function Backdrop({ children }: { children: ReactNode }) {
    return (
        <div
            aria-hidden
            className="pointer-events-none absolute inset-0 -z-10 overflow-hidden rounded-[inherit]"
        >
            {children}
        </div>
    );
}

/** The Ozubathon on its host's door: the countdown, then the hour on air. */
function OzubathonSticker({ event }: { event: Event }) {
    const clock = ozubathonClock(event, useNow(Date.parse(event.now), 60_000));
    const { days, hours, minutes } = clock.countdown;

    const status =
        clock.phase === 'before'
            ? `Starts in ${days > 0 ? `${days}d ` : ''}${String(hours).padStart(2, '0')}h${days > 0 ? '' : ` ${String(minutes).padStart(2, '0')}m`}`
            : clock.phase === 'live'
              ? `Live · hour ${clock.hour} of ${event.hours}`
              : `${event.hours} hours, done. Thank you!`;

    return (
        <span className="ozb-slap absolute -top-5 -right-3 flex rotate-[5deg] flex-col items-center sm:-top-7 sm:-right-5">
            <Sparkle className="absolute -top-2 -left-3 size-5" />
            <img
                src="/images/ozubathon/logo.svg"
                alt="55 Ozubathon"
                className="w-24 drop-shadow-[0_6px_8px_rgba(0,0,0,0.3)] sm:w-32"
            />
            <span className="-mt-1 border-2 border-oz-white bg-oz-black px-2 py-0.5 font-ozb-display text-sm leading-none tracking-wide whitespace-nowrap text-ozb-yellow uppercase tabular-nums shadow-[0_6px_12px_-4px_rgba(0,0,0,0.5)] sm:text-base">
                {status}
            </span>
        </span>
    );
}
