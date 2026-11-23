import { CalendarClock, Info, MapPin, Ticket, Users } from 'lucide-react';
import { useState } from 'react';
import { OzDialog } from '@/components/station/oz-dialog';
import {
    STATION_TIMEZONE,
    formatDate,
    formatTime,
    formatWeekday,
} from '@/lib/wib';
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
    const [open, setOpen] = useState(false);

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

            <button
                type="button"
                onClick={() => setOpen(true)}
                aria-haspopup="dialog"
                className="col-start-2 inline-flex items-center gap-2 justify-self-start rounded-full border-2 border-oz-black px-5 py-2 text-sm font-bold transition-colors hover:bg-oz-black hover:text-oz-yellow focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none sm:col-start-3"
            >
                <Info className="size-4" aria-hidden />
                Details
                <span className="sr-only">: {event.title}</span>
            </button>

            <EventDialog event={event} open={open} onOpenChange={setOpen} />
        </article>
    );
}

function EventDialog({
    event,
    open,
    onOpenChange,
}: {
    event: StationEvent;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const start = new Date(event.starts_at);

    return (
        <OzDialog
            open={open}
            onOpenChange={onOpenChange}
            title={event.title}
            description={event.is_oz_event ? 'An OZ event' : undefined}
            media={
                event.poster_url ? (
                    <img
                        src={event.poster_url}
                        alt={`Poster for ${event.title}`}
                        className="absolute inset-0 size-full object-cover"
                    />
                ) : (
                    <span
                        aria-hidden
                        className="absolute bottom-4 left-6 font-oz-display leading-none font-extrabold text-oz-black"
                    >
                        <span className="block text-8xl tracking-tight">
                            {dayFormat.format(start)}
                        </span>
                        <span className="block text-3xl">
                            {monthFormat.format(start)}
                        </span>
                    </span>
                )
            }
        >
            <dl className="mt-5 space-y-3">
                <div className="flex gap-3">
                    <dt className="pt-0.5">
                        <CalendarClock className="size-5" aria-hidden />
                        <span className="sr-only">When</span>
                    </dt>
                    <dd className="font-semibold">
                        {formatWeekday(event.starts_at)},{' '}
                        {formatDate(event.starts_at)}
                        <span className="block font-normal text-oz-ink">
                            {formatTime(event.starts_at)}
                            {event.ends_at &&
                                ` – ${formatTime(event.ends_at)}`}{' '}
                            WIB
                        </span>
                    </dd>
                </div>
                {event.location && (
                    <div className="flex gap-3">
                        <dt className="pt-0.5">
                            <MapPin className="size-5" aria-hidden />
                            <span className="sr-only">Where</span>
                        </dt>
                        <dd className="font-semibold">{event.location}</dd>
                    </div>
                )}
                {event.organizer && !event.is_oz_event && (
                    <div className="flex gap-3">
                        <dt className="pt-0.5">
                            <Users className="size-5" aria-hidden />
                            <span className="sr-only">Organiser</span>
                        </dt>
                        <dd className="font-semibold">
                            Organised by {event.organizer}
                        </dd>
                    </div>
                )}
            </dl>

            {event.description && (
                <div
                    className="oz-prose mt-6 text-base"
                    dangerouslySetInnerHTML={{ __html: event.description }}
                />
            )}

            {event.ticket_url && (
                <a
                    href={event.ticket_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="mt-8 inline-flex h-12 items-center gap-2 rounded-full bg-oz-black px-6 font-bold text-oz-white transition-transform hover:scale-[1.02] focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                >
                    <Ticket className="size-5" aria-hidden />
                    Get tickets
                </a>
            )}
        </OzDialog>
    );
}
