import { CalendarClock } from 'lucide-react';
import { AvatarStack } from '@/components/station/announcer-avatar';
import { CoverImage } from '@/components/station/cover-image';
import { useStationDetails } from '@/components/station/station-details';
import { scheduleSummary } from '@/lib/wib';
import type { Program } from '@/types';

/**
 * Every show as a card; a tap opens its description, schedule and hosts.
 */
export function ProgramCards({ programs }: { programs: Program[] }) {
    const { openProgram } = useStationDetails();

    return (
        <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {programs.map((program) => {
                const [when, ...more] = scheduleSummary(
                    program.schedules ?? [],
                );
                const hosts = program.announcers ?? [];

                return (
                    <li key={program.id}>
                        <button
                            type="button"
                            onClick={() => openProgram(program.id)}
                            aria-haspopup="dialog"
                            className="group flex h-full w-full flex-col overflow-hidden rounded-[1.75rem] bg-oz-paper text-left transition-transform hover:-translate-y-1 focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                        >
                            <span className="relative block aspect-[4/3] overflow-hidden bg-oz-yellow">
                                {program.cover_image_url ? (
                                    <CoverImage
                                        src={program.cover_image_url}
                                        label={program.title}
                                        className="absolute inset-0 size-full transition-transform duration-500 group-hover:scale-105"
                                    />
                                ) : (
                                    // No artwork yet: the show's start time, poster-style
                                    // (the title is already right below).
                                    <span
                                        aria-hidden
                                        className="absolute bottom-3 left-5 font-oz-display text-6xl leading-none font-extrabold tracking-tight text-oz-black/85 tabular-nums transition-transform duration-500 group-hover:scale-105"
                                    >
                                        {program.schedules?.[0]?.start ?? 'OZ'}
                                    </span>
                                )}
                            </span>
                            <span className="flex flex-1 flex-col p-5">
                                <span className="font-oz-display text-xl leading-tight font-extrabold tracking-tight">
                                    {program.title}
                                </span>
                                {when && (
                                    <span className="mt-2 flex items-start gap-2 text-sm font-semibold text-oz-ink">
                                        <CalendarClock
                                            className="mt-0.5 size-4 shrink-0"
                                            aria-hidden
                                        />
                                        <span>
                                            {when}
                                            {more.length > 0 &&
                                                ` +${more.length} more`}
                                        </span>
                                    </span>
                                )}
                                {hosts.length > 0 && (
                                    <span className="mt-auto flex items-center gap-3 pt-4">
                                        <AvatarStack
                                            announcers={hosts}
                                            size="sm"
                                        />
                                        <span className="min-w-0 truncate text-sm font-semibold">
                                            {hosts
                                                .map(
                                                    (host) => host.display_name,
                                                )
                                                .join(' & ')}
                                        </span>
                                    </span>
                                )}
                            </span>
                        </button>
                    </li>
                );
            })}
        </ul>
    );
}
