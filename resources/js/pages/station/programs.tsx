import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { AnnouncerAvatar } from '@/components/station/announcer-avatar';
import { PageHeading } from '@/components/station/page-heading';
import { cn } from '@/lib/utils';
import { slotRange, todayInWib } from '@/lib/wib';
import type { ScheduleSlot, StationPageProps } from '@/types';

type Props = StationPageProps & {
    days: { value: number; label: string }[];
    slots: ScheduleSlot[];
};

/** Monday first, the way listeners think about the week. */
const WEEK_ORDER = [1, 2, 3, 4, 5, 6, 0];

export default function StationPrograms() {
    const { station, broadcast, meta, days, slots } = usePage<Props>().props;
    const today = todayInWib();
    const [day, setDay] = useState(today);

    const daySlots = slots.filter((slot) => slot.day === day);
    const ordered = WEEK_ORDER.map((value) =>
        days.find((d) => d.value === value)!,
    );

    return (
        <>
            <Head title={meta.title} />
            <PageHeading
                title="Programs"
                intro={`Every show on ${station.name}, all times in WIB.`}
            >
                <div
                    className="-mx-4 mt-10 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0"
                    role="group"
                    aria-label="Choose a day"
                >
                    {ordered.map((d) => (
                        <button
                            key={d.value}
                            type="button"
                            onClick={() => setDay(d.value)}
                            aria-pressed={day === d.value}
                            className={cn(
                                'shrink-0 rounded-full px-5 py-2.5 text-sm font-bold transition-colors focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none',
                                day === d.value
                                    ? 'bg-oz-black text-oz-yellow'
                                    : 'bg-oz-paper text-oz-black hover:bg-oz-black/10',
                            )}
                        >
                            {d.value === today ? `Today` : d.label}
                        </button>
                    ))}
                </div>
            </PageHeading>

            <section
                aria-label={`${days.find((d) => d.value === day)?.label} schedule`}
                className="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14"
            >
                {daySlots.length === 0 ? (
                    <p className="py-16 text-lg text-oz-ink">
                        No shows are scheduled for this day yet.
                    </p>
                ) : (
                    <ol className="divide-y divide-oz-black/10">
                        {daySlots.map((slot) => {
                            const onAir = broadcast.current?.id === slot.id;

                            return (
                                <li
                                    key={slot.id}
                                    className={cn(
                                        'grid gap-4 py-8 sm:grid-cols-[11rem_minmax(0,1fr)_auto] sm:gap-8',
                                        onAir &&
                                            '-mx-4 rounded-3xl bg-oz-yellow px-4 sm:-mx-6 sm:px-6',
                                    )}
                                >
                                    <div>
                                        <p className="font-oz-display text-2xl font-extrabold tracking-tight tabular-nums">
                                            {slotRange(slot.start, slot.end)}
                                        </p>
                                        {onAir && (
                                            <p className="mt-2 inline-flex items-center gap-2 rounded-full bg-oz-black px-3 py-1 text-xs font-bold text-oz-white">
                                                <span className="size-2 rounded-full bg-oz-red" />
                                                On air now
                                            </p>
                                        )}
                                    </div>
                                    <div className="min-w-0">
                                        <h2 className="font-oz-display text-2xl leading-tight font-extrabold tracking-tight sm:text-3xl">
                                            {slot.program.title}
                                        </h2>
                                        {slot.program.description && (
                                            <p className="mt-2 max-w-2xl text-oz-ink">
                                                {slot.program.description}
                                            </p>
                                        )}
                                    </div>
                                    {slot.program.announcers &&
                                        slot.program.announcers.length > 0 && (
                                            <ul className="flex flex-wrap items-center gap-3 sm:justify-end">
                                                {slot.program.announcers.map(
                                                    (announcer) => (
                                                        <li
                                                            key={announcer.id}
                                                            className="flex items-center gap-2"
                                                        >
                                                            <AnnouncerAvatar
                                                                announcer={
                                                                    announcer
                                                                }
                                                                className="size-10 text-sm"
                                                            />
                                                            <span className="text-sm font-semibold">
                                                                {
                                                                    announcer.display_name
                                                                }
                                                            </span>
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        )}
                                </li>
                            );
                        })}
                    </ol>
                )}
            </section>
        </>
    );
}
