import { MapPin, Ticket } from 'lucide-react';
import { STATION_TIMEZONE, formatTime } from '@/lib/wib';
import { cn } from '@/lib/utils';
import type { StationEvent } from '@/types';

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

/**
 * One event as a ticket-stub row: the date block is the anchor.
 */
export function EventRow({
    event,
    showDescription = false,
    muted = false,
}: {
    event: StationEvent;
    showDescription?: boolean;
    muted?: boolean;
}) {
    const start = new Date(event.starts_at);

    return (
        <article
            className={cn(
                'grid grid-cols-[4.5rem_minmax(0,1fr)] gap-5 py-6 sm:grid-cols-[5.5rem_minmax(0,1fr)_auto] sm:items-center sm:gap-8',
                muted && 'opacity-70',
            )}
        >
            <div
                className={cn(
                    'flex flex-col items-center justify-center rounded-2xl py-3 text-center',
                    event.is_oz_event
                        ? 'bg-oz-yellow text-oz-black'
                        : 'bg-oz-paper text-oz-black',
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

            <div className="min-w-0">
                {event.is_oz_event && (
                    <p className="mb-1 text-sm font-bold text-oz-black">
                        An OZ event
                    </p>
                )}
                <h3 className="font-oz-display text-xl leading-tight font-extrabold tracking-tight sm:text-2xl">
                    {event.title}
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
                {showDescription && event.description && (
                    <div
                        className="oz-prose mt-4 max-w-2xl text-base"
                        dangerouslySetInnerHTML={{ __html: event.description }}
                    />
                )}
            </div>

            {event.ticket_url && (
                <a
                    href={event.ticket_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="col-start-2 inline-flex items-center gap-2 justify-self-start rounded-full border-2 border-oz-black px-5 py-2 text-sm font-bold transition-colors hover:bg-oz-black hover:text-oz-yellow focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none sm:col-start-3"
                >
                    <Ticket className="size-4" aria-hidden />
                    Get tickets
                </a>
            )}
        </article>
    );
}
