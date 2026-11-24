import { Head, Link, usePage } from '@inertiajs/react';
import {
    CalendarClock,
    ChevronRight,
    MapPin,
    Ticket,
    Users,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { CoverImage } from '@/components/station/cover-image';
import { DateBlock, EventRow } from '@/components/station/event-row';
import { ShareBar } from '@/components/station/share-bar';
import { formatDate, formatTime, formatWeekday } from '@/lib/wib';
import stationRoutes from '@/routes/station';
import type { StationEvent, StationPageProps } from '@/types';

type Props = StationPageProps & {
    event: StationEvent;
    upcoming: StationEvent[];
};

function Fact({
    icon,
    label,
    children,
}: {
    icon: ReactNode;
    label: string;
    children: ReactNode;
}) {
    return (
        <div className="flex gap-3">
            <dt className="pt-0.5">
                {icon}
                <span className="sr-only">{label}</span>
            </dt>
            <dd className="font-semibold">{children}</dd>
        </div>
    );
}

export default function StationEventShow() {
    const { station, meta, event, upcoming } = usePage<Props>().props;
    const isPast =
        new Date(event.ends_at ?? event.starts_at).getTime() < Date.now();

    return (
        <>
            <Head title={meta.title} />

            <article className="mx-auto max-w-7xl px-4 pt-8 pb-16 sm:px-6 sm:pt-12">
                <nav aria-label="Breadcrumb">
                    <ol className="flex flex-wrap items-center gap-1.5 text-sm">
                        <li>
                            <Link
                                href={stationRoutes.events(station.slug)}
                                className="rounded font-bold decoration-oz-yellow decoration-2 underline-offset-4 hover:underline focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                            >
                                Events
                            </Link>
                        </li>
                        <li className="flex items-center gap-1.5 text-oz-ink/70">
                            <ChevronRight
                                className="size-4 text-oz-ink/50"
                                aria-hidden
                            />
                            {event.is_oz_event ? 'An OZ event' : 'Around town'}
                        </li>
                    </ol>
                </nav>

                <header className="mt-6 flex items-start gap-5 sm:gap-8">
                    <DateBlock
                        event={event}
                        className="w-[4.5rem] shrink-0 sm:w-[5.5rem]"
                    />
                    <div className="min-w-0">
                        <h1 className="font-oz-display text-4xl leading-[1] font-extrabold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                            {event.title}
                        </h1>
                        {isPast && (
                            <p className="mt-3 inline-flex rounded-full bg-oz-paper px-3 py-1 text-sm font-bold">
                                This event has ended
                            </p>
                        )}
                    </div>
                </header>

                <div className="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-14">
                    <div className="min-w-0">
                        {event.poster_url && (
                            <CoverImage
                                src={event.poster_url}
                                label={event.title}
                                alt={`Poster for ${event.title}`}
                                className="aspect-video w-full rounded-2xl"
                            />
                        )}
                        {event.description ? (
                            <div
                                className="oz-prose mt-8 max-w-[68ch]"
                                dangerouslySetInnerHTML={{
                                    __html: event.description,
                                }}
                            />
                        ) : (
                            !event.poster_url && (
                                <p className="text-lg text-oz-ink">
                                    More details soon. Follow our socials to
                                    hear first.
                                </p>
                            )
                        )}
                    </div>

                    <aside aria-label="Event details">
                        <div className="rounded-3xl bg-oz-paper p-6 lg:sticky lg:top-24">
                            <dl className="space-y-4">
                                <Fact
                                    icon={
                                        <CalendarClock
                                            className="size-5"
                                            aria-hidden
                                        />
                                    }
                                    label="When"
                                >
                                    {formatWeekday(event.starts_at)},{' '}
                                    {formatDate(event.starts_at)}
                                    <span className="block font-normal text-oz-ink">
                                        {formatTime(event.starts_at)}
                                        {event.ends_at &&
                                            ` – ${formatTime(event.ends_at)}`}{' '}
                                        WIB
                                    </span>
                                </Fact>
                                {event.location && (
                                    <Fact
                                        icon={
                                            <MapPin
                                                className="size-5"
                                                aria-hidden
                                            />
                                        }
                                        label="Where"
                                    >
                                        {event.location}
                                    </Fact>
                                )}
                                {event.organizer && !event.is_oz_event && (
                                    <Fact
                                        icon={
                                            <Users
                                                className="size-5"
                                                aria-hidden
                                            />
                                        }
                                        label="Organiser"
                                    >
                                        Organised by {event.organizer}
                                    </Fact>
                                )}
                            </dl>

                            {event.ticket_url && !isPast && (
                                <a
                                    href={event.ticket_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="mt-6 flex h-12 items-center justify-center gap-2 rounded-full bg-oz-black px-6 font-bold text-oz-white transition-transform hover:scale-[1.02] focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                                >
                                    <Ticket className="size-5" aria-hidden />
                                    Get tickets
                                </a>
                            )}

                            <ShareBar
                                url={meta.url}
                                title={event.title}
                                className="mt-6 border-t border-oz-black/10 pt-5"
                            />
                        </div>
                    </aside>
                </div>
            </article>

            {upcoming.length > 0 && (
                <section
                    aria-labelledby="upcoming-title"
                    className="bg-oz-paper"
                >
                    <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6">
                        <div className="flex items-end justify-between gap-4">
                            <h2
                                id="upcoming-title"
                                className="font-oz-display text-2xl font-extrabold tracking-tight sm:text-3xl"
                            >
                                Also coming up
                            </h2>
                            <Link
                                href={stationRoutes.events(station.slug)}
                                className="shrink-0 text-sm font-bold underline decoration-oz-yellow decoration-[3px] underline-offset-4 hover:decoration-oz-black"
                            >
                                All events
                            </Link>
                        </div>
                        <div className="mt-4 divide-y divide-oz-black/10">
                            {upcoming.map((item) => (
                                <EventRow key={item.id} event={item} />
                            ))}
                        </div>
                    </div>
                </section>
            )}
        </>
    );
}
