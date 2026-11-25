import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { HeroPlayer } from '@/components/player/hero-player';
import {
    OzubathonGround,
    OzubathonLockup,
    OzubathonMeter,
} from '@/components/ozubathon/ozubathon-parts';
import { useNow } from '@/hooks/use-now';
import { ozubathonClock } from '@/lib/ozubathon';
import { formatTime } from '@/lib/wib';
import stationRoutes from '@/routes/station';
import type { Broadcast, Ozubathon, Station } from '@/types';

const ASSETS = '/images/ozubathon';

/**
 * The station home hero while the Ozubathon theme is on: the poster's
 * collage made to work. The logo lockup and the 55-hour meter on one side,
 * the live player stuck onto the torn yellow band as the poster's central
 * sticker on the other, Ozzy and his boombox leaning out from behind it.
 * The player is the usual HeroPlayer, so radio, video, reactions and the
 * mini player behave exactly as without the theme.
 */
export function OzubathonHero({
    station,
    broadcast,
    event,
    banner,
}: {
    station: Station;
    broadcast: Broadcast;
    event: Ozubathon;
    banner?: ReactNode;
}) {
    const { current, next } = broadcast;
    // During the 55 hours the station is live even between scheduled shows.
    const now = useNow(Date.parse(broadcast.at), 60_000);
    const live = ozubathonClock(event, now).phase === 'live';

    return (
        <section aria-labelledby="ozubathon-title" className="text-oz-black">
            <OzubathonGround bandClassName="top-[12%] bottom-[22%] lg:top-[16%] lg:bottom-[20%]">
                {/* The poster's props, half off the page. */}
                <img
                    src={`${ASSETS}/retro-mic.webp`}
                    alt=""
                    aria-hidden
                    className="ozb-slap pointer-events-none absolute bottom-[-4%] left-[-2.5rem] hidden w-36 rotate-[10deg] [--ozb-delay:380ms] lg:block xl:left-[2%] xl:w-44"
                />
                <img
                    src={`${ASSETS}/headphones.webp`}
                    alt=""
                    aria-hidden
                    className="ozb-slap pointer-events-none absolute top-[-2.5rem] right-[-2rem] hidden w-40 -rotate-[14deg] [--ozb-delay:460ms] lg:block xl:right-[3%]"
                />

                {/* Phones: lockup, player, meter (tune in first). Wide: lockup over
                    the meter on the left, the player spanning both on the right. */}
                <div className="relative mx-auto grid max-w-7xl grid-cols-[minmax(0,1fr)] gap-8 px-4 pt-6 pb-10 sm:px-6 sm:pt-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-x-12 lg:gap-y-8 lg:pt-10 lg:pb-14">
                    <div
                        id="ozubathon-title"
                        className="lg:col-start-1 lg:row-start-1 lg:self-end"
                    >
                        <OzubathonLockup event={event} />
                    </div>

                    <div className="relative lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:self-center">
                        <div className="relative rounded-[2rem] bg-oz-white p-3 shadow-[0_22px_45px_-18px_rgba(0,0,0,0.55)] sm:p-4">
                            {/* In front of the card, leaning on its outer lower
                                corner: past the video, so it never covers the
                                player's controls, and above the sponsor banner. */}
                            <img
                                src={`${ASSETS}/ozzy-boombox.webp`}
                                alt=""
                                aria-hidden
                                className="ozb-slap pointer-events-none absolute -top-[6.5rem] right-[-0.25rem] z-10 w-20 rotate-6 [--ozb-delay:520ms] sm:-top-32 sm:w-32 lg:top-auto lg:right-[-13%] lg:bottom-[-7%] lg:w-[17%] lg:rotate-[8deg]"
                            />
                            <HeroPlayer
                                broadcast={broadcast}
                                header={
                                    <p className="flex flex-wrap items-center gap-x-3 gap-y-1 font-ozb text-sm font-semibold sm:text-base">
                                        <span className="inline-flex items-center gap-2 rounded-full bg-oz-black px-3 py-1 text-sm font-bold text-oz-white">
                                            <span
                                                className={
                                                    current || live
                                                        ? 'size-2 rounded-full bg-oz-red'
                                                        : 'size-2 rounded-full bg-oz-white/40'
                                                }
                                            />
                                            {current
                                                ? 'On air now'
                                                : live
                                                  ? 'Ozubathon live'
                                                  : 'Off air'}
                                        </span>
                                        {current && (
                                            <span>
                                                {current.program.title}, until{' '}
                                                {formatTime(current.ends_at)}{' '}
                                                WIB
                                            </span>
                                        )}
                                    </p>
                                }
                            />
                        </div>

                        <p className="relative mt-4 ml-2 inline-flex max-w-full rotate-[-1.5deg] flex-wrap items-center gap-x-3 gap-y-1 bg-oz-black px-3 py-2 font-ozb text-sm text-oz-white shadow-[0_10px_20px_-8px_rgba(0,0,0,0.5)]">
                            <span className="font-ozb-display text-base tracking-wide text-ozb-yellow uppercase">
                                Up next
                            </span>
                            <span className="min-w-0 font-semibold">
                                {next
                                    ? `${next.program.title}, ${formatTime(next.starts_at)} WIB`
                                    : 'The schedule is being updated'}
                            </span>
                            <Link
                                href={stationRoutes.programs(station.slug)}
                                className="rounded font-semibold underline decoration-ozb-yellow decoration-2 underline-offset-4 hover:text-ozb-yellow focus-visible:ring-2 focus-visible:ring-ozb-yellow focus-visible:outline-none"
                            >
                                Full schedule
                            </Link>
                        </p>
                    </div>

                    <OzubathonMeter
                        event={event}
                        initialNow={Date.parse(broadcast.at)}
                        className="-rotate-1 lg:col-start-1 lg:row-start-2 lg:self-start"
                    />

                    {/* Under the player on wide screens; after the meter on phones. */}
                    {banner && (
                        <div className="min-w-0 lg:col-start-2 lg:row-start-3">
                            {banner}
                        </div>
                    )}
                </div>
            </OzubathonGround>
        </section>
    );
}
