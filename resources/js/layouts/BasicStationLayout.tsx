import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { PlayerProvider } from '@/components/player/player-context';
import { BrandLogo } from '@/components/station/brand-logo';
import { SocialLinks } from '@/components/station/social-links';
import { home } from '@/routes';
import stationRoutes from '@/routes/station';
import type { StationPageProps } from '@/types';

/**
 * Persistent layout for a basic station site (OZ Bali): just the radio.
 * Night navy with a warm glow rising from the bottom, where OZ Bandung is
 * daylight white; same logo style, yellow and type, so it's still OZ.
 * The player keeps playing between the home and privacy pages.
 */
export default function BasicStationLayout({
    children,
}: {
    children: ReactNode;
}) {
    const { station, broadcast, now_playing } =
        usePage<StationPageProps>().props;

    return (
        <PlayerProvider
            station={station}
            broadcast={broadcast}
            nowPlaying={now_playing}
        >
            <div className="relative isolate flex min-h-screen flex-col overflow-hidden bg-oz-navy font-oz text-oz-white antialiased">
                {/* Sunset: a warm glow from below the fold. */}
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-x-0 bottom-0 -z-10 h-[70vh] bg-[radial-gradient(ellipse_at_50%_100%,rgba(242,189,81,0.35),rgba(242,189,81,0.08)_45%,transparent_70%)]"
                />
                <a
                    href="#main"
                    className="sr-only z-50 rounded-full bg-oz-yellow px-4 py-2 text-oz-black focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
                >
                    Skip to content
                </a>
                <header className="mx-auto flex w-full max-w-5xl items-center justify-between px-4 py-5 sm:px-6">
                    <Link
                        href={stationRoutes.home(station.slug)}
                        aria-label={`${station.name} home`}
                        className="rounded-xl focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                    >
                        <BrandLogo
                            brand={station.slug}
                            className="h-12 sm:h-14"
                        />
                    </Link>
                    <SocialLinks
                        social={station.social}
                        tone="dark"
                        variant="icons"
                    />
                </header>

                <main id="main" className="flex-1">
                    {children}
                </main>

                <footer className="mx-auto flex w-full max-w-5xl flex-col gap-2 px-4 py-8 text-sm text-oz-white/60 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p>
                        {station.name} is part of{' '}
                        <Link
                            href={home()}
                            className="font-semibold text-oz-white hover:text-oz-yellow"
                        >
                            OZ Media
                        </Link>
                        .
                    </p>
                    <Link
                        href={stationRoutes.privacy(station.slug)}
                        className="font-semibold text-oz-white hover:text-oz-yellow"
                    >
                        Privacy policy
                    </Link>
                </footer>
            </div>
        </PlayerProvider>
    );
}
