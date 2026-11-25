import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, MapPin } from 'lucide-react';
import { CoverImage } from '@/components/station/cover-image';
import { STATION_TIMEZONE, formatTime } from '@/lib/wib';
import { cn } from '@/lib/utils';
import stationRoutes from '@/routes/station';
import type { StationEvent, StationPageProps } from '@/types';

const dayFormat = new Intl.DateTimeFormat('en-GB', {
    timeZone: STATION_TIMEZONE,
    day: 'numeric',
});
const monthFormat = new Intl.DateTimeFormat('en-GB', {
    timeZone: STATION_TIMEZONE,
    month: 'short',
});
const weekdayFormat = new Intl.DateTimeFormat('en-GB', {
    timeZone: STATION_TIMEZONE,
    weekday: 'short',
});

/** The tear-off date block: weekday, big day number, month. */
export function DateBlock({
    event,
    className,
}: {
    event: StationEvent;
    className?: string;
}) {
    const start = new Date(event.starts_at);

    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center rounded-2xl py-3 text-center',
                event.is_oz_event
                    ? 'bg-oz-yellow text-oz-black'
                    : 'bg-oz-paper text-oz-black',
                className,
            )}
        >
            <span className="text-xs font-semibold">
                {weekdayFormat.format(start)}
            </span>
            <span className="font-oz-display text-3xl leading-none font-extrabold sm:text-4xl">
                {dayFormat.format(start)}
            </span>
            <span className="text-sm font-semibold">
                {monthFormat.format(start)}
            </span>
        </div>
    );
}

/**
 * One event as a ticket-stub row: the date block is the anchor. The name and
 * the Details button open the event's own page.
 */
export function EventRow({
    event,
    muted = false,
    withCover = false,
}: {
    event: StationEvent;
    muted?: boolean;
    /** Show the poster beside the date (the Events page). */
    withCover?: boolean;
}) {
    const { station } = usePage<StationPageProps>().props;
    const href = stationRoutes.events.show({
        station: station.slug,
        slug: event.slug,
    });

    return (
        <article
            className={cn(
                'grid grid-cols-[4.5rem_minmax(0,1fr)] gap-5 py-6 sm:grid-cols-[5.5rem_minmax(0,1fr)_auto] sm:items-center sm:gap-8',
                withCover && 'md:grid-cols-[5.5rem_13rem_minmax(0,1fr)_auto]',
                muted && 'opacity-70',
            )}
        >
            {withCover && (
                <Link
                    href={href}
                    tabIndex={-1}
                    aria-hidden
                    className="col-span-2 block overflow-hidden rounded-2xl sm:col-span-3 md:order-2 md:col-span-1"
                >
                    <CoverImage
                        src={event.poster_url}
                        label={event.title}
                        className="aspect-video w-full"
                    />
                </Link>
            )}

            <DateBlock
                event={event}
                className={cn(withCover && 'md:order-1')}
            />

            <div className={cn('min-w-0', withCover && 'md:order-3')}>
                {event.is_oz_event && (
                    <p className="mb-1 text-sm font-bold text-oz-black">
                        An OZ event
                    </p>
                )}
                <h3 className="font-oz-display text-xl leading-tight font-extrabold tracking-tight sm:text-2xl">
                    <Link
                        href={href}
                        className="decoration-oz-yellow decoration-[3px] underline-offset-4 hover:underline focus-visible:underline focus-visible:outline-none"
                    >
                        {event.title}
                    </Link>
                </h3>
                <p className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-oz-ink">
                    <span>
                        {formatTime(event.starts_at)}
                        {event.ends_at && `–${formatTime(event.ends_at)}`} WIB
                    </span>
                    {event.location && (
                        <span className="inline-flex items-center gap-1">
                            <MapPin className="size-4" aria-hidden />
                            {event.location}
                        </span>
                    )}
                </p>
                {event.organizer && !event.is_oz_event && (
                    <p className="mt-1 text-sm text-oz-ink/70">
                        Organised by {event.organizer}
                    </p>
                )}
            </div>

            <Link
                href={href}
                className={cn(
                    'col-start-2 inline-flex items-center gap-2 justify-self-start rounded-full border-2 border-oz-black px-5 py-2 text-sm font-bold transition-colors hover:bg-oz-black hover:text-oz-yellow focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none sm:col-start-3',
                    withCover && 'md:order-4 md:col-start-4',
                )}
            >
                Details
                <ArrowRight className="size-4" aria-hidden />
                <span className="sr-only">: {event.title}</span>
            </Link>
        </article>
    );
}
