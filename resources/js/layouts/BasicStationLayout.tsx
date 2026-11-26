import { Link, usePage } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import { PlayerProvider, usePlayer } from '@/components/player/player-context';
import { BrandLogo } from '@/components/station/brand-logo';
import { SocialLinks } from '@/components/station/social-links';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import stationRoutes from '@/routes/station';
import type { StationPageProps } from '@/types';

type Props = StationPageProps & {
    sister: { slug: string; name: string } | null;
};

/** Sand pill links on the plum and lagoon; the primary one is gold with an ink offset. */
const pill =
    'inline-flex min-h-11 items-center rounded-full border-2 border-[var(--bali-sand)] px-[18px] text-sm font-bold transition-colors hover:bg-[var(--bali-sand)] hover:text-oz-black focus-visible:ring-4 focus-visible:ring-[var(--bali-gold)] focus-visible:outline-none';

/**
 * Persistent layout for a basic station site (OZ Bali): just the radio.
 * A sunset over the lagoon (the .bali styles in app.css): plum sky up top,
 * the shore as the footer. Same logo, type and player as OZ Bandung, so it's
 * still OZ. The player keeps playing between the home and privacy pages.
 */
export default function BasicStationLayout({
    children,
}: {
    children: ReactNode;
}) {
    const { station, broadcast, now_playing, sister } = usePage<Props>().props;
    const onHome = usePage().component === 'basic/home';

    return (
        <PlayerProvider
            station={station}
            broadcast={broadcast}
            nowPlaying={now_playing}
        >
            <div className="bali relative isolate flex min-h-svh flex-col overflow-x-clip font-oz antialiased">
                <a
                    href="#main"
                    className="sr-only z-50 rounded-full bg-[var(--bali-gold)] px-4 py-2 text-oz-black focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
                >
                    Skip to content
                </a>
                <header className="relative z-40 flex items-center justify-between gap-4 px-[clamp(16px,4vw,40px)] py-3">
                    <Link
                        href={home()}
                        className="inline-flex min-h-11 items-center gap-1.5 rounded-full pr-3.5 pl-2.5 text-sm font-semibold transition-colors hover:bg-[var(--bali-sand)] hover:text-oz-black focus-visible:ring-4 focus-visible:ring-[var(--bali-gold)] focus-visible:outline-none"
                    >
                        <ChevronLeft className="size-4" aria-hidden />
                        All stations
                    </Link>
                    {!onHome && (
                        <Link
                            href={stationRoutes.home(station.slug)}
                            aria-label={`${station.name} home`}
                            className="rounded-xl focus-visible:ring-4 focus-visible:ring-[var(--bali-gold)] focus-visible:outline-none"
                        >
                            <BrandLogo
                                brand={station.slug}
                                onDark
                                className="h-10 sm:h-12"
                            />
                        </Link>
                    )}
                    <Listeners />
                </header>

                <main id="main" className="flex flex-1 flex-col">
                    {children}
                </main>

                <footer className="bali-fade relative z-10 grid justify-items-center gap-[18px] bg-[var(--bali-lagoon)] px-4 pt-6 pb-8 text-center">
                    {station.tagline && (
                        <p className="max-w-[22ch] font-oz-display text-[clamp(24px,4vw,34px)] leading-[1.15] font-bold text-balance">
                            {station.tagline}
                        </p>
                    )}
                    <nav
                        aria-label="OZ stations"
                        className="flex flex-wrap justify-center gap-x-3 gap-y-2"
                    >
                        {sister && (
                            <Link
                                href={stationRoutes.home(sister.slug)}
                                className={cn(
                                    pill,
                                    'border-[var(--bali-gold)] bg-[var(--bali-gold)] text-oz-black shadow-[4px_4px_0_#000]',
                                )}
                            >
                                Listen to {sister.name}
                            </Link>
                        )}
                        <Link href={home()} className={pill}>
                            All stations
                        </Link>
                    </nav>
                    <SocialLinks
                        social={station.social}
                        tone="dark"
                        variant="icons"
                    />
                    <p className="text-[13px] text-[var(--bali-sand)]/75">
                        {station.name} is part of OZ Media ·{' '}
                        <Link
                            href={stationRoutes.privacy(station.slug)}
                            className="font-semibold text-[var(--bali-sand)] underline underline-offset-2 hover:text-[var(--bali-gold)]"
                        >
                            Privacy policy
                        </Link>
                    </p>
                </footer>
            </div>
        </PlayerProvider>
    );
}

/** "12 listening now", from Icecast; nothing while the count is unknown. */
function Listeners() {
    const { listeners } = usePlayer().nowPlaying;

    if (listeners === null) {
        return null;
    }

    return (
        <p className="text-sm font-semibold text-[var(--bali-gold)] tabular-nums">
            {listeners.toLocaleString()} listening now
        </p>
    );
}
