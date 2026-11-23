import { createContext, useContext, useState } from 'react';
import type { ReactNode } from 'react';
import { AnnouncerAvatar } from '@/components/station/announcer-avatar';
import { CoverImage } from '@/components/station/cover-image';
import { DialogSection, OzDialog } from '@/components/station/oz-dialog';
import { SocialLinks } from '@/components/station/social-links';
import { scheduleSummary } from '@/lib/wib';
import type { Announcer, Program } from '@/types';

type Selected = { type: 'program' | 'announcer'; id: number } | null;

type DetailsApi = {
    openProgram: (id: number) => void;
    openAnnouncer: (id: number) => void;
};

const DetailsContext = createContext<DetailsApi | null>(null);

/**
 * One popup for the station home's program and announcer cards. A host in a
 * program popup opens that announcer, and a show in an announcer popup opens
 * that program, so visitors can hop between them.
 */
export function StationDetailsProvider({
    programs,
    announcers,
    children,
}: {
    programs: Program[];
    announcers: Announcer[];
    children: ReactNode;
}) {
    const [selected, setSelected] = useState<Selected>(null);
    // Keep showing the last item while the close animation runs.
    const [shown, setShown] = useState<Selected>(null);

    const open = (next: NonNullable<Selected>) => {
        setSelected(next);
        setShown(next);
    };

    const api: DetailsApi = {
        openProgram: (id) => open({ type: 'program', id }),
        openAnnouncer: (id) => open({ type: 'announcer', id }),
    };

    const program =
        shown?.type === 'program'
            ? programs.find((p) => p.id === shown.id)
            : undefined;
    const announcer =
        shown?.type === 'announcer'
            ? announcers.find((a) => a.id === shown.id)
            : undefined;

    const onOpenChange = (isOpen: boolean) => {
        if (!isOpen) {
            setSelected(null);
        }
    };

    return (
        <DetailsContext.Provider value={api}>
            {children}
            {program && (
                <ProgramDialog
                    program={program}
                    open={selected !== null}
                    onOpenChange={onOpenChange}
                    onHost={(id) => {
                        // Hosts who are hidden from the site have no popup.
                        if (announcers.some((a) => a.id === id)) {
                            api.openAnnouncer(id);
                        }
                    }}
                />
            )}
            {announcer && (
                <AnnouncerDialog
                    announcer={announcer}
                    programs={programs}
                    open={selected !== null}
                    onOpenChange={onOpenChange}
                    onProgram={api.openProgram}
                />
            )}
        </DetailsContext.Provider>
    );
}

export function useStationDetails(): DetailsApi {
    const details = useContext(DetailsContext);

    if (!details) {
        throw new Error(
            'useStationDetails() must be used inside <StationDetailsProvider>.',
        );
    }

    return details;
}

function ProgramDialog({
    program,
    open,
    onOpenChange,
    onHost,
}: {
    program: Program;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onHost: (id: number) => void;
}) {
    const schedule = scheduleSummary(program.schedules ?? []);
    const hosts = program.announcers ?? [];

    return (
        <OzDialog
            open={open}
            onOpenChange={onOpenChange}
            title={program.title}
            media={
                program.cover_image_url ? (
                    <CoverImage
                        src={program.cover_image_url}
                        label={program.title}
                        className="absolute inset-0 size-full"
                    />
                ) : (
                    <span
                        aria-hidden
                        className="absolute bottom-4 left-6 font-oz-display text-7xl leading-none font-extrabold tracking-tight text-oz-black/85 tabular-nums"
                    >
                        {program.schedules?.[0]?.start ?? 'OZ'}
                    </span>
                )
            }
        >
            {program.description && (
                <p className="mt-5 text-lg leading-relaxed text-oz-ink">
                    {program.description}
                </p>
            )}

            <DialogSection title="Schedule (WIB)">
                {schedule.length > 0 ? (
                    <ul className="space-y-1 font-semibold">
                        {schedule.map((line) => (
                            <li key={line}>{line}</li>
                        ))}
                    </ul>
                ) : (
                    <p className="text-oz-ink">
                        Not on the schedule right now.
                    </p>
                )}
            </DialogSection>

            {hosts.length > 0 && (
                <DialogSection title={hosts.length > 1 ? 'Hosts' : 'Host'}>
                    <ul className="flex flex-wrap gap-2">
                        {hosts.map((host) => (
                            <li key={host.id}>
                                <button
                                    type="button"
                                    onClick={() => onHost(host.id)}
                                    className="flex items-center gap-3 rounded-full bg-oz-paper py-1.5 pr-4 pl-1.5 font-semibold transition-colors hover:bg-oz-yellow focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                                >
                                    <AnnouncerAvatar
                                        announcer={host}
                                        className="size-10 text-sm"
                                    />
                                    {host.display_name}
                                </button>
                            </li>
                        ))}
                    </ul>
                </DialogSection>
            )}
        </OzDialog>
    );
}

function AnnouncerDialog({
    announcer,
    programs,
    open,
    onOpenChange,
    onProgram,
}: {
    announcer: Announcer;
    programs: Program[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onProgram: (id: number) => void;
}) {
    const shows = (announcer.programs ?? [])
        .map((show) => programs.find((p) => p.id === show.id))
        .filter((show): show is Program => show !== undefined);

    return (
        <OzDialog
            open={open}
            onOpenChange={onOpenChange}
            title={announcer.display_name}
            description={
                announcer.display_name !== announcer.name
                    ? announcer.name
                    : undefined
            }
            media={
                announcer.avatar_url ? (
                    <img
                        src={announcer.avatar_url}
                        alt={`Portrait of ${announcer.display_name}`}
                        className="absolute inset-0 size-full object-cover object-top"
                    />
                ) : (
                    <span
                        aria-hidden
                        className="absolute bottom-4 left-5 font-oz-display text-6xl leading-[0.85] font-extrabold tracking-tight text-oz-black"
                    >
                        {announcer.display_name}
                    </span>
                )
            }
        >
            {announcer.bio && (
                <p className="mt-5 text-lg leading-relaxed text-oz-ink">
                    {announcer.bio}
                </p>
            )}

            {shows.length > 0 && (
                <DialogSection title="On air in">
                    <ul className="space-y-2">
                        {shows.map((show) => (
                            <li key={show.id}>
                                <button
                                    type="button"
                                    onClick={() => onProgram(show.id)}
                                    className="w-full rounded-2xl bg-oz-paper px-4 py-3 text-left transition-colors hover:bg-oz-yellow focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                                >
                                    <span className="block font-oz-display text-lg leading-tight font-extrabold">
                                        {show.title}
                                    </span>
                                    {scheduleSummary(show.schedules ?? []).map(
                                        (line) => (
                                            <span
                                                key={line}
                                                className="block text-sm text-oz-ink"
                                            >
                                                {line} WIB
                                            </span>
                                        ),
                                    )}
                                </button>
                            </li>
                        ))}
                    </ul>
                </DialogSection>
            )}

            {Object.keys(announcer.social).length > 0 && (
                <DialogSection title="Follow">
                    <SocialLinks social={announcer.social} />
                </DialogSection>
            )}
        </OzDialog>
    );
}
