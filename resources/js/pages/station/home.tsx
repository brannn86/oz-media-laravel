import { Head, Link, usePage } from '@inertiajs/react';
import { AnnouncerCarousel } from '@/components/station/announcer-carousel';
import { BrandIcon } from '@/components/station/brand-icon';
import { EventRow } from '@/components/station/event-row';
import { HighlightTile } from '@/components/station/highlight-tile';
import { LeadStory, StoryItem } from '@/components/station/news-items';
import { OzubathonHero } from '@/components/ozubathon/ozubathon-hero';
import { OzubathonTicker } from '@/components/ozubathon/ozubathon-ticker';
import { OnAirHero } from '@/components/station/on-air-hero';
import { ProgramCards } from '@/components/station/program-cards';
import { RecentlyPlayed } from '@/components/station/recently-played';
import { SectionHeading } from '@/components/station/page-heading';
import { SponsorBanner } from '@/components/station/sponsor-banner';
import type { Sponsor } from '@/components/station/sponsor-banner';
import { StationDetailsProvider } from '@/components/station/station-details';
import { cn } from '@/lib/utils';
import stationRoutes from '@/routes/station';
import type {
    Announcer,
    Post,
    Program,
    SocialHighlight,
    StationEvent,
    StationPageProps,
} from '@/types';

type Props = StationPageProps & {
    news: Post[];
    events: StationEvent[];
    highlights: SocialHighlight[];
    programs: Program[];
    announcers: Announcer[];
    /** Null while the ad space is switched off. */
    sponsor: Sponsor | null;
};

const moreLink =
    'shrink-0 rounded-full px-4 py-2 text-sm font-bold text-oz-black underline decoration-oz-yellow decoration-[3px] underline-offset-4 hover:decoration-oz-black focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none';

export default function StationHome() {
    const {
        station,
        broadcast,
        meta,
        news,
        events,
        highlights,
        programs,
        announcers,
        sponsor,
    } = usePage<Props>().props;
    const [lead, ...rest] = news;
    const whatsapp = station.contact.whatsapp?.replace(/\D/g, '');

    const banner = sponsor && (
        <SponsorBanner sponsor={sponsor} stationName={station.name} />
    );

    return (
        <>
            <Head title={meta.title} />

            {station.ozubathon ? (
                <OzubathonHero
                    station={station}
                    broadcast={broadcast}
                    event={station.ozubathon}
                    banner={banner}
                />
            ) : (
                <OnAirHero
                    station={station}
                    broadcast={broadcast}
                    banner={banner}
                />
            )}

            {station.ozubathon && (
                <OzubathonTicker phrases={station.ozubathon.ticker} />
            )}

            <RecentlyPlayed />

            <StationDetailsProvider programs={programs} announcers={announcers}>
                {programs.length > 0 && (
                    <section
                        aria-labelledby="programs-title"
                        className="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24"
                    >
                        <SectionHeading
                            id="programs-title"
                            title="Our shows"
                            action={
                                <Link
                                    href={stationRoutes.programs(station.slug)}
                                    className={moreLink}
                                >
                                    Full schedule
                                </Link>
                            }
                        />
                        <ProgramCards programs={programs} />
                    </section>
                )}

                {announcers.length > 0 && (
                    <section
                        aria-labelledby="announcers-title"
                        className="bg-oz-yellow py-16 sm:py-24"
                    >
                        <div className="mx-auto max-w-7xl px-4 sm:px-6">
                            <SectionHeading
                                id="announcers-title"
                                title="The voices"
                                action={
                                    <Link
                                        href={stationRoutes.announcers(
                                            station.slug,
                                        )}
                                        className={cn(
                                            moreLink,
                                            'decoration-oz-black',
                                        )}
                                    >
                                        All announcers
                                    </Link>
                                }
                            />
                        </div>
                        <AnnouncerCarousel announcers={announcers} />
                    </section>
                )}
            </StationDetailsProvider>

            {lead && (
                <section
                    aria-labelledby="news-title"
                    className="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24"
                >
                    <SectionHeading
                        id="news-title"
                        title="OZ News"
                        action={
                            <Link
                                href={stationRoutes.news.index(station.slug)}
                                className={moreLink}
                            >
                                All stories
                            </Link>
                        }
                    />
                    <LeadStory post={lead} stationSlug={station.slug} />
                    {rest.length > 0 && (
                        <div className="mt-12 grid gap-8 border-t border-oz-black/10 pt-10 md:grid-cols-2">
                            {rest.map((post) => (
                                <StoryItem
                                    key={post.id}
                                    post={post}
                                    stationSlug={station.slug}
                                />
                            ))}
                        </div>
                    )}
                </section>
            )}

            {events.length > 0 && (
                <section aria-labelledby="events-title" className="bg-oz-paper">
                    <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24">
                        <SectionHeading
                            id="events-title"
                            title="Coming up"
                            action={
                                <Link
                                    href={stationRoutes.events(station.slug)}
                                    className={moreLink}
                                >
                                    All events
                                </Link>
                            }
                        />
                        <div className="divide-y divide-oz-black/10">
                            {events.map((event) => (
                                <EventRow key={event.id} event={event} />
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {highlights.length > 0 && (
                <section
                    aria-labelledby="highlights-title"
                    className="py-16 sm:py-24"
                >
                    <div className="mx-auto max-w-7xl px-4 sm:px-6">
                        <h2
                            id="highlights-title"
                            className="mb-8 font-oz-display text-3xl leading-none font-extrabold tracking-tight sm:text-4xl"
                        >
                            Seen on our socials
                        </h2>
                    </div>
                    <div className="mx-auto max-w-7xl">
                        <div className="flex snap-x gap-5 overflow-x-auto px-4 pb-4 sm:px-6">
                            {highlights.map((highlight) => (
                                <HighlightTile
                                    key={highlight.id}
                                    highlight={highlight}
                                />
                            ))}
                        </div>
                    </div>
                </section>
            )}

            <section aria-labelledby="contact-title" className="bg-oz-butter">
                <div className="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 sm:py-20 md:grid-cols-[1.2fr_1fr] md:items-end">
                    <div>
                        <h2
                            id="contact-title"
                            className="font-oz-display text-4xl leading-[0.95] font-extrabold tracking-tight text-balance sm:text-6xl"
                        >
                            {station.contact.heading}
                        </h2>
                    </div>
                    <dl className="grid gap-5 text-base sm:grid-cols-2">
                        {whatsapp && (
                            <div>
                                <dt className="text-sm font-semibold text-oz-black/60">
                                    WhatsApp
                                </dt>
                                <dd className="mt-1 font-semibold">
                                    <a
                                        href={`https://wa.me/${whatsapp}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-2 hover:underline"
                                    >
                                        <BrandIcon brand="whatsapp" />
                                        {station.contact.whatsapp}
                                    </a>
                                </dd>
                            </div>
                        )}
                        {station.contact.email && (
                            <div>
                                <dt className="text-sm font-semibold text-oz-black/60">
                                    Email
                                </dt>
                                <dd className="mt-1 font-semibold break-words">
                                    <a
                                        href={`mailto:${station.contact.email}`}
                                        className="hover:underline"
                                    >
                                        {station.contact.email}
                                    </a>
                                </dd>
                            </div>
                        )}
                        {station.contact.address && (
                            <div className="sm:col-span-2">
                                <dt className="text-sm font-semibold text-oz-black/60">
                                    Studio
                                </dt>
                                <dd className="mt-1 font-semibold whitespace-pre-line">
                                    {station.contact.map_url ? (
                                        <a
                                            href={station.contact.map_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="underline decoration-oz-black/30 underline-offset-4 hover:decoration-oz-black"
                                        >
                                            {station.contact.address}
                                            <span className="sr-only">
                                                {' '}
                                                (open in maps)
                                            </span>
                                        </a>
                                    ) : (
                                        station.contact.address
                                    )}
                                </dd>
                            </div>
                        )}
                    </dl>
                </div>
            </section>
        </>
    );
}
