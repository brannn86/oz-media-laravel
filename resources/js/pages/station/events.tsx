import { Head, usePage } from '@inertiajs/react';
import { EventRow } from '@/components/station/event-row';
import { PageHeading } from '@/components/station/page-heading';
import type { StationEvent, StationPageProps } from '@/types';

type Props = StationPageProps & {
    upcoming: StationEvent[];
    past: StationEvent[];
};

export default function StationEvents() {
    const { station, meta, upcoming, past } = usePage<Props>().props;

    return (
        <>
            <Head title={meta.title} />
            <PageHeading
                title="Events"
                intro={`Gigs, meet-ups and community events from ${station.name} and friends.`}
            />

            <section
                aria-label="Upcoming events"
                className="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14"
            >
                {upcoming.length === 0 ? (
                    <p className="py-10 text-lg text-oz-ink">
                        Nothing is on the calendar right now. Follow our socials
                        to hear about the next one first.
                    </p>
                ) : (
                    <div className="divide-y divide-oz-black/10">
                        {upcoming.map((event) => (
                            <EventRow
                                key={event.id}
                                event={event}
                                showDescription
                            />
                        ))}
                    </div>
                )}
            </section>

            {past.length > 0 && (
                <section aria-labelledby="past-title" className="bg-oz-paper">
                    <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6">
                        <h2
                            id="past-title"
                            className="font-oz-display text-2xl font-extrabold tracking-tight"
                        >
                            Recently
                        </h2>
                        <div className="mt-4 divide-y divide-oz-black/10">
                            {past.map((event) => (
                                <EventRow key={event.id} event={event} muted />
                            ))}
                        </div>
                    </div>
                </section>
            )}
        </>
    );
}
