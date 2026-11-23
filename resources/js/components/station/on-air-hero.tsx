import { Link } from '@inertiajs/react';
import { Music } from 'lucide-react';
import { usePlayer } from '@/components/player/player-context';
import { TuneInControls } from '@/components/player/tune-in-controls';
import { AvatarStack } from '@/components/station/announcer-avatar';
import { useNow } from '@/hooks/use-now';
import { formatTime, formatWeekday } from '@/lib/wib';
import stationRoutes from '@/routes/station';
import type { Broadcast, Station } from '@/types';

/**
 * The station home hero: whatever is on air right now, poster-sized.
 */
export function OnAirHero({
    station,
    broadcast,
}: {
    station: Station;
    broadcast: Broadcast;
}) {
    const player = usePlayer();
    const now = useNow(Date.parse(broadcast.at));
    const { current, next } = broadcast;

    const progress = current
        ? Math.min(
              1,
              Math.max(
                  0,
                  (now - Date.parse(current.starts_at)) /
                      (Date.parse(current.ends_at) -
                          Date.parse(current.starts_at)),
              ),
          )
        : 0;

    const hosts = current?.program.announcers ?? [];

    return (
        <section
            aria-labelledby="on-air-title"
            // One screen tall (minus the header and the player bar's space),
            // whatever the controls are doing: the layout never jumps.
            className="relative flex min-h-[calc(100svh-8.5rem)] items-center overflow-hidden bg-oz-yellow text-oz-black"
        >
            <div className="mx-auto grid w-full max-w-7xl gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-16">
                <div className="min-w-0">
                    <p className="flex flex-wrap items-center gap-x-3 gap-y-1 text-base font-semibold">
                        <span className="inline-flex items-center gap-2 rounded-full bg-oz-black px-3 py-1 text-sm font-bold text-oz-white">
                            <span className="size-2 rounded-full bg-oz-red" />
                            {current ? 'On air now' : 'Off air'}
                        </span>
                        {current && (
                            <span>Until {formatTime(current.ends_at)} WIB</span>
                        )}
                    </p>

                    <h1
                        id="on-air-title"
                        className="mt-6 font-oz-display text-[clamp(3rem,9vw,7.5rem)] leading-[0.88] font-extrabold tracking-[-0.04em] text-balance"
                    >
                        {current?.program.title ?? station.name}
                    </h1>

                    {player.nowPlaying.track && (
                        <p
                            className="mt-6 inline-flex max-w-full items-center gap-3 rounded-full bg-oz-black/10 py-2 pr-5 pl-3 text-base font-semibold"
                            aria-live="polite"
                        >
                            <span className="grid size-8 shrink-0 place-items-center rounded-full bg-oz-black text-oz-yellow">
                                <Music className="size-4" aria-hidden />
                            </span>
                            <span className="min-w-0 truncate">
                                <span className="sr-only">Now playing: </span>
                                <span className="font-extrabold">
                                    {player.nowPlaying.track.title}
                                </span>
                                {player.nowPlaying.track.artist &&
                                    ` · ${player.nowPlaying.track.artist}`}
                            </span>
                        </p>
                    )}

                    {current?.program.description && (
                        <p className="mt-6 max-w-2xl text-lg leading-relaxed text-oz-black/80 sm:text-xl">
                            {current.program.description}
                        </p>
                    )}

                    <div className="mt-8">
                        <TuneInControls />
                    </div>

                    {hosts.length > 0 && (
                        <div className="mt-6 flex items-center gap-3">
                            <AvatarStack announcers={hosts} />
                            <p className="text-base leading-tight font-semibold">
                                with{' '}
                                {hosts
                                    .map((host) => host.display_name)
                                    .join(' & ')}
                            </p>
                        </div>
                    )}

                    {current && (
                        <div className="mt-10 max-w-2xl">
                            <div
                                className="h-2 overflow-hidden rounded-full bg-oz-black/15"
                                role="progressbar"
                                aria-label="Show progress"
                                aria-valuemin={0}
                                aria-valuemax={100}
                                aria-valuenow={Math.round(progress * 100)}
                            >
                                <div
                                    className="h-full rounded-full bg-oz-black transition-[width] duration-1000"
                                    style={{ width: `${progress * 100}%` }}
                                />
                            </div>
                            <div className="mt-2 flex justify-between text-sm font-semibold text-oz-black/70">
                                <span>{formatTime(current.starts_at)}</span>
                                <span>{formatTime(current.ends_at)}</span>
                            </div>
                        </div>
                    )}
                </div>

                <aside className="self-end rounded-3xl bg-oz-black p-6 text-oz-white">
                    <h2 className="text-sm font-semibold text-oz-yellow">
                        Up next
                    </h2>
                    {next ? (
                        <>
                            <p className="mt-3 font-oz-display text-2xl leading-tight font-extrabold">
                                {next.program.title}
                            </p>
                            <p className="mt-2 text-oz-white/70">
                                {nextLabel(next.starts_at)}
                            </p>
                            {next.program.announcers &&
                                next.program.announcers.length > 0 && (
                                    <p className="mt-1 text-sm text-oz-white/60">
                                        with{' '}
                                        {next.program.announcers
                                            .map((host) => host.display_name)
                                            .join(' & ')}
                                    </p>
                                )}
                        </>
                    ) : (
                        <p className="mt-3 text-oz-white/70">
                            The schedule is being updated.
                        </p>
                    )}
                    <Link
                        href={stationRoutes.programs(station.slug)}
                        className="mt-6 inline-flex rounded-full bg-oz-yellow px-5 py-2.5 text-sm font-bold text-oz-black transition-colors hover:bg-oz-butter focus-visible:ring-4 focus-visible:ring-oz-yellow/50 focus-visible:outline-none"
                    >
                        Full schedule
                    </Link>
                </aside>
            </div>
        </section>
    );
}

/** "From 10:00 WIB" today, otherwise "Saturday, 07:00 WIB". */
function nextLabel(startsAt: string): string {
    const isToday =
        formatWeekday(startsAt) === formatWeekday(new Date().toISOString()) &&
        Date.parse(startsAt) - Date.now() < 24 * 60 * 60_000;

    return isToday
        ? `From ${formatTime(startsAt)} WIB`
        : `${formatWeekday(startsAt)}, ${formatTime(startsAt)} WIB`;
}
