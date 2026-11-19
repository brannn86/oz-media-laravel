import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import type { ReactNode } from 'react';
import { PlayerBar } from '@/components/player/player-bar';
import { PlayerProvider } from '@/components/player/player-context';
import { VideoDock } from '@/components/player/video-dock';
import { StationFooter } from '@/components/station/station-footer';
import { StationHeader } from '@/components/station/station-header';
import type { Broadcast, StationPageProps } from '@/types';

const MIN_REFRESH_MS = 15_000;
const MAX_REFRESH_MS = 30 * 60_000;

/**
 * Persistent layout for every page under /{station}.
 *
 * Inertia keeps this component (and the <audio> element inside PlayerProvider)
 * mounted while visitors move between station pages, so the stream never cuts out.
 */
export default function StationLayout({ children }: { children: ReactNode }) {
    const { station, broadcast } = usePage<StationPageProps>().props;

    useBroadcastRefresh(broadcast);

    return (
        <PlayerProvider station={station} broadcast={broadcast}>
            <div className="flex min-h-screen flex-col bg-oz-white font-oz text-oz-black antialiased">
                <a
                    href="#main"
                    className="sr-only z-50 rounded-full bg-oz-black px-4 py-2 text-oz-white focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
                >
                    Skip to content
                </a>
                <StationHeader station={station} />
                <main id="main" className="flex-1">
                    {children}
                </main>
                <StationFooter station={station} />
                {/* Room so the fixed player never covers the footer. */}
                <div aria-hidden className="h-[4.5rem] bg-oz-black" />
            </div>
            <VideoDock />
            <PlayerBar broadcast={broadcast} />
        </PlayerProvider>
    );
}

/**
 * Re-fetches only the `broadcast` prop when the current show ends (or the next
 * starts), so "On air" stays right without reloading the page or the player.
 */
function useBroadcastRefresh(broadcast: Broadcast) {
    useEffect(() => {
        const target = broadcast.refresh_at
            ? Date.parse(broadcast.refresh_at)
            : Number.NaN;
        const delay = Number.isNaN(target)
            ? MAX_REFRESH_MS
            : Math.min(
                  Math.max(target - Date.now() + 2_000, MIN_REFRESH_MS),
                  MAX_REFRESH_MS,
              );

        const timer = window.setTimeout(
            () => router.reload({ only: ['broadcast'] }),
            delay,
        );

        return () => window.clearTimeout(timer);
    }, [broadcast.refresh_at, broadcast.at]);
}
