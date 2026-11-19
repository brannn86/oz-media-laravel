import { Head, usePage } from '@inertiajs/react';
import { PageHeading } from '@/components/station/page-heading';
import { SocialLinks } from '@/components/station/social-links';
import type { Announcer, StationPageProps } from '@/types';

type Props = StationPageProps & {
    announcers: Announcer[];
};

export default function StationAnnouncers() {
    const { station, meta, announcers } = usePage<Props>().props;

    return (
        <>
            <Head title={meta.title} />
            <PageHeading
                title="The voices"
                intro={`The people you hear on ${station.name}, morning to midnight.`}
            />

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16">
                {announcers.length === 0 ? (
                    <p className="text-lg text-oz-ink">
                        Announcer profiles are on their way.
                    </p>
                ) : (
                    <ul className="grid gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                        {announcers.map((announcer) => (
                            <li key={announcer.id}>
                                <article>
                                    <div className="relative aspect-[4/5] overflow-hidden rounded-[2rem] bg-oz-yellow">
                                        {announcer.avatar_url ? (
                                            <img
                                                src={announcer.avatar_url}
                                                alt={`Portrait of ${announcer.display_name}`}
                                                loading="lazy"
                                                className="size-full object-cover"
                                            />
                                        ) : (
                                            <span
                                                aria-hidden
                                                className="absolute bottom-3 left-5 font-oz-display text-[5.5rem] leading-[0.8] font-extrabold tracking-[-0.05em] whitespace-nowrap text-oz-black"
                                            >
                                                {announcer.display_name}
                                            </span>
                                        )}
                                    </div>
                                    <h2 className="mt-5 font-oz-display text-3xl leading-none font-extrabold tracking-tight">
                                        {announcer.display_name}
                                    </h2>
                                    {announcer.display_name !==
                                        announcer.name && (
                                        <p className="mt-1 text-oz-ink/70">
                                            {announcer.name}
                                        </p>
                                    )}
                                    {announcer.bio && (
                                        <p className="mt-3 text-oz-ink">
                                            {announcer.bio}
                                        </p>
                                    )}
                                    {announcer.programs &&
                                        announcer.programs.length > 0 && (
                                            <p className="mt-3 text-sm">
                                                <span className="font-semibold">
                                                    Hosts:{' '}
                                                </span>
                                                {announcer.programs
                                                    .map((p) => p.title)
                                                    .join(', ')}
                                            </p>
                                        )}
                                    <SocialLinks
                                        social={announcer.social}
                                        className="mt-4"
                                    />
                                </article>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </>
    );
}
