import { Link } from '@inertiajs/react';
import { HeroPlayer } from '@/components/player/hero-player';
import { AvatarStack } from '@/components/station/announcer-avatar';
import { useNow } from '@/hooks/use-now';
import { formatTime, formatWeekday } from '@/lib/wib';
import stationRoutes from '@/routes/station';
import type { Broadcast, Station } from '@/types';

/**
 * The station home hero: the player (radio or video, 16:9) beside the show
 * on air and what's up next, the two cards together as tall as the player.
 * Below `lg` the cards sit under it: next to a 16:9 box narrower than
 * ~700px they would be taller than the box.
 */
export function OnAirHero({
    station,
    broadcast,
}: {
    station: Station;
    broadcast: Broadcast;
}) {
    const { current } = broadcast;

    return (
        <section
            aria-labelledby="on-air-title"
            className="bg-oz-yellow text-oz-black"
        >
            <div className="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 sm:py-12 lg:grid-cols-[minmax(0,1fr)_18rem] lg:gap-6 xl:grid-cols-[minmax(0,1fr)_20rem] xl:gap-8">
                <h1 id="on-air-title" className="sr-only">
                    {station.name} live
                    {current ? `: ${current.program.title}` : ''}
                </h1>

                <HeroPlayer
                    broadcast={broadcast}
                    header={
                        <p className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm font-semibold sm:text-base">
                            <span className="inline-flex items-center gap-2 rounded-full bg-oz-black px-3 py-1 text-sm font-bold text-oz-white">
                                <span className="size-2 rounded-full bg-oz-red" />
                                {current ? 'On air now' : 'Off air'}
                            </span>
                            {current && (
                                <span>
                                    Until {formatTime(current.ends_at)} WIB
                                </span>
                            )}
                        </p>
                    }
                />

                {/* lg: level with the box (past the row above it), and as tall. */}
                <div className="grid gap-6 sm:grid-cols-2 lg:mt-[3.25rem] lg:grid-cols-1 lg:grid-rows-[minmax(0,1fr)_auto] lg:gap-4 xl:gap-6">
                    <OnAirCard station={station} broadcast={broadcast} />
                    <UpNextCard station={station} broadcast={broadcast} />
                </div>
            </div>
        </section>
    );
}

/** The show on air: its artwork with title, hosts and progress over it. */
function OnAirCard({
    station,
    broadcast,
}: {
    station: Station;
    broadcast: Broadcast;
}) {
    const now = useNow(Date.parse(broadcast.at));
    const { current } = broadcast;
    const program = current?.program;
    const hosts = program?.announcers ?? [];

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

    return (
        <article className="relative flex min-h-56 flex-col justify-end overflow-hidden rounded-3xl bg-oz-black text-oz-white lg:min-h-0">
            {program?.cover_image_url && (
                <img
                    src={program.cover_image_url}
                    alt=""
                    className="absolute inset-0 size-full object-cover"
                />
            )}
            <div className="relative bg-gradient-to-t from-oz-black via-oz-black/80 to-transparent p-5 pt-16 lg:p-4 lg:pt-10 xl:p-5 xl:pt-16">
                <h2 className="text-xs font-bold tracking-wide text-oz-yellow uppercase">
                    {current ? 'On air' : 'Off air'}
                </h2>
                <p className="mt-1 font-oz-display text-2xl leading-tight font-extrabold">
                    {program?.title ?? station.name}
                </p>
                {hosts.length > 0 && (
                    <div className="mt-3 flex items-center gap-2 lg:mt-2 xl:mt-3">
                        <AvatarStack announcers={hosts} size="sm" />
                        <p className="min-w-0 truncate text-sm font-semibold text-oz-white/85">
                            with{' '}
                            {hosts.map((host) => host.display_name).join(' & ')}
                        </p>
                    </div>
                )}
                {current && (
                    <div className="mt-4">
                        <div
                            className="h-1.5 overflow-hidden rounded-full bg-oz-white/20"
                            role="progressbar"
                            aria-label="Show progress"
                            aria-valuemin={0}
                            aria-valuemax={100}
                            aria-valuenow={Math.round(progress * 100)}
                        >
                            <div
                                className="h-full rounded-full bg-oz-yellow transition-[width] duration-1000"
                                style={{ width: `${progress * 100}%` }}
                            />
                        </div>
                        <div className="mt-1.5 flex justify-between text-xs font-semibold text-oz-white/70">
                            <span>{formatTime(current.starts_at)}</span>
                            <span>{formatTime(current.ends_at)}</span>
                        </div>
                    </div>
                )}
            </div>
        </article>
    );
}

function UpNextCard({
    station,
    broadcast,
}: {
    station: Station;
    broadcast: Broadcast;
}) {
    const { next } = broadcast;

    return (
        <aside className="rounded-3xl bg-oz-black p-5 text-oz-white lg:p-4 xl:p-5">
            <h2 className="text-sm font-semibold text-oz-yellow">Up next</h2>
            {next ? (
                <>
                    <p className="mt-2 font-oz-display text-xl leading-tight font-extrabold">
                        {next.program.title}
                    </p>
                    <p className="mt-1.5 text-sm text-oz-white/70">
                        {nextLabel(next.starts_at)}
                    </p>
                    {next.program.announcers &&
                        next.program.announcers.length > 0 && (
                            <p className="mt-0.5 text-sm text-oz-white/60">
                                with{' '}
                                {next.program.announcers
                                    .map((host) => host.display_name)
                                    .join(' & ')}
                            </p>
                        )}
                </>
            ) : (
                <p className="mt-2 text-oz-white/70">
                    The schedule is being updated.
                </p>
            )}
            <Link
                href={stationRoutes.programs(station.slug)}
                className="mt-4 inline-flex rounded-full bg-oz-yellow px-4 py-2 text-sm font-bold lg:mt-3 xl:mt-4 text-oz-black transition-colors hover:bg-oz-butter focus-visible:ring-4 focus-visible:ring-oz-yellow/50 focus-visible:outline-none"
            >
                Full schedule
            </Link>
        </aside>
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
