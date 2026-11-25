import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowUpRight, Radio } from 'lucide-react';
import { CoverImage } from '@/components/station/cover-image';
import { BrandLogo } from '@/components/station/brand-logo';
import { formatDate } from '@/lib/wib';
import { home } from '@/routes';
import stationRoutes from '@/routes/station';
import type { PageMeta, Post } from '@/types';

type Props = {
    meta: PageMeta;
    stations: { slug: string; name: string; tagline: string | null }[];
    partners: {
        name: string;
        /** A key of BrandLogo's logos. */
        logo?: string;
        tagline: string;
        url: string | null;
    }[];
    stories: Post[];
};

const platforms = [
    {
        name: 'Radio',
        text: 'Live shows every day, hosted by announcers who know their city.',
    },
    {
        name: 'Video',
        text: 'Studio sessions and livestreams, on our site and on YouTube.',
    },
    {
        name: 'Social',
        text: 'Short clips, behind the scenes and conversations on TikTok and Instagram.',
    },
    {
        name: 'Live events',
        text: 'Concerts, campus tours and community meet-ups, on and off air.',
    },
];

export default function CorporateHome() {
    const { meta, stations, partners, stories } = usePage<Props>().props;

    return (
        <div className="min-h-screen bg-oz-white font-oz text-oz-black antialiased">
            <Head title={meta.title} />

            <header className="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6">
                <Link
                    href={home()}
                    aria-label="OZ Media home"
                    className="rounded-xl focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                >
                    <BrandLogo brand="oz-media" className="h-10" />
                </Link>
                <nav aria-label="Main">
                    <ul className="flex items-center gap-1 text-sm font-semibold">
                        <li>
                            <a
                                href="#stations"
                                className="rounded-full px-4 py-2 hover:bg-oz-black/5"
                            >
                                Stations
                            </a>
                        </li>
                        <li className="hidden sm:block">
                            <a
                                href="#stories"
                                className="rounded-full px-4 py-2 hover:bg-oz-black/5"
                            >
                                Stories
                            </a>
                        </li>
                    </ul>
                </nav>
            </header>

            <main>
                <section className="mx-auto max-w-7xl px-4 pt-12 pb-20 sm:px-6 sm:pt-20 sm:pb-28">
                    <h1 className="max-w-5xl font-oz-display text-[clamp(3rem,8.5vw,7.5rem)] leading-[0.9] font-extrabold tracking-[-0.045em] text-balance">
                        A home for voices, ideas, talent and community.
                    </h1>
                    <div className="mt-10 grid gap-8 md:grid-cols-[minmax(0,36rem)_1fr] md:items-end">
                        <p className="text-xl leading-relaxed text-oz-ink">
                            OZ has been on air for 55 years. What started as a
                            radio station is now a network of radio, video,
                            social and live events, made with the communities we
                            broadcast to.
                        </p>
                        {stations[0] && (
                            <Link
                                href={stationRoutes.home(stations[0].slug)}
                                className="inline-flex h-14 items-center gap-3 justify-self-start rounded-full bg-oz-yellow px-7 text-lg font-bold transition-transform hover:scale-[1.02] focus-visible:ring-4 focus-visible:ring-oz-black/30 focus-visible:outline-none md:justify-self-end"
                            >
                                <Radio className="size-6" aria-hidden />
                                Listen to {stations[0].name}
                            </Link>
                        )}
                    </div>
                </section>

                <section
                    id="stations"
                    aria-labelledby="stations-title"
                    className="mx-auto max-w-7xl scroll-mt-6 px-4 sm:px-6"
                >
                    <h2 id="stations-title" className="sr-only">
                        Our stations
                    </h2>
                    <ul className="grid gap-4 md:grid-cols-2">
                        {stations.map((station) => (
                            <li key={station.slug}>
                                <Link
                                    href={stationRoutes.home(station.slug)}
                                    className="group flex min-h-[22rem] flex-col justify-between rounded-[2rem] bg-oz-yellow p-8 transition-transform hover:-translate-y-1 focus-visible:ring-4 focus-visible:ring-oz-black focus-visible:outline-none sm:min-h-[26rem] sm:p-10"
                                >
                                    <span className="inline-flex items-center gap-2 self-start rounded-full bg-oz-black px-3 py-1 text-sm font-bold text-oz-white">
                                        <span className="size-2 rounded-full bg-oz-red" />
                                        Live now
                                    </span>
                                    <span>
                                        <span className="block font-oz-display text-6xl leading-[0.9] font-extrabold tracking-[-0.04em] sm:text-7xl">
                                            {station.name}
                                        </span>
                                        {station.tagline && (
                                            <span className="mt-4 block text-lg font-semibold text-oz-black/75">
                                                {station.tagline}
                                            </span>
                                        )}
                                        <span className="mt-8 inline-flex items-center gap-2 text-lg font-bold underline decoration-[3px] underline-offset-4">
                                            Open the station
                                        </span>
                                    </span>
                                </Link>
                            </li>
                        ))}
                        {partners.map((partner) => (
                            <li key={partner.name}>
                                <PartnerCard partner={partner} />
                            </li>
                        ))}
                    </ul>
                </section>

                <section
                    aria-labelledby="platforms-title"
                    className="mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28"
                >
                    <h2
                        id="platforms-title"
                        className="max-w-3xl font-oz-display text-4xl leading-[0.95] font-extrabold tracking-tight sm:text-5xl"
                    >
                        Still radio at heart. Everywhere our listeners are.
                    </h2>
                    <dl className="mt-12 grid gap-x-10 gap-y-8 border-t-4 border-oz-black pt-8 sm:grid-cols-2 lg:grid-cols-4">
                        {platforms.map((platform) => (
                            <div key={platform.name}>
                                <dt className="font-oz-display text-2xl font-extrabold">
                                    {platform.name}
                                </dt>
                                <dd className="mt-2 text-oz-ink">
                                    {platform.text}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </section>

                {stories.length > 0 && (
                    <section
                        id="stories"
                        aria-labelledby="stories-title"
                        className="scroll-mt-6 bg-oz-paper"
                    >
                        <div className="mx-auto max-w-7xl px-4 py-20 sm:px-6">
                            <h2
                                id="stories-title"
                                className="font-oz-display text-4xl leading-none font-extrabold tracking-tight"
                            >
                                From the network
                            </h2>
                            <ul className="mt-10 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                                {stories.map((story) => (
                                    <li key={story.id}>
                                        <StoryCard story={story} />
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </section>
                )}
            </main>

            <footer className="bg-oz-black text-oz-white">
                <div className="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-12 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <BrandLogo brand="oz-media" onDark className="h-10" />
                    <p className="text-sm text-oz-white/60">
                        © {new Date().getFullYear()} OZ Media. All rights
                        reserved.
                    </p>
                </div>
            </footer>
        </div>
    );
}

function PartnerCard({ partner }: { partner: Props['partners'][number] }) {
    const body = (
        <>
            <span className="self-start rounded-full bg-oz-white/10 px-3 py-1 text-sm font-semibold">
                {partner.url ? 'Partner site' : 'Site coming soon'}
            </span>
            <span>
                {partner.logo ? (
                    <BrandLogo
                        brand={partner.logo}
                        onDark
                        className="h-28 sm:h-36"
                    />
                ) : (
                    <span className="block font-oz-display text-6xl leading-[0.9] font-extrabold tracking-[-0.04em] sm:text-7xl">
                        {partner.name}
                    </span>
                )}
                <span className="mt-4 block text-lg text-oz-white/70">
                    {partner.tagline}
                </span>
                {partner.url && (
                    <span className="mt-8 inline-flex items-center gap-2 text-lg font-bold text-oz-yellow underline decoration-[3px] underline-offset-4">
                        Visit {partner.name}
                        <ArrowUpRight className="size-5" aria-hidden />
                    </span>
                )}
            </span>
        </>
    );

    const className =
        'flex min-h-[22rem] flex-col justify-between rounded-[2rem] bg-oz-black p-8 text-oz-white sm:min-h-[26rem] sm:p-10';

    return partner.url ? (
        <a
            href={partner.url}
            target="_blank"
            rel="noopener noreferrer"
            className={`${className} transition-transform hover:-translate-y-1 focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none`}
        >
            {body}
        </a>
    ) : (
        <div className={className}>{body}</div>
    );
}

function StoryCard({ story }: { story: Post }) {
    const stationSlug = story.station?.slug;

    if (!stationSlug) {
        return null;
    }

    const href = stationRoutes.news.show({
        station: stationSlug,
        slug: story.slug,
    });

    return (
        <article className="group">
            <Link
                href={href}
                tabIndex={-1}
                aria-hidden
                className="block overflow-hidden rounded-3xl"
            >
                <CoverImage
                    src={story.image_url}
                    label={story.station?.name ?? 'OZ News'}
                    className="aspect-video w-full transition-transform duration-500 group-hover:scale-[1.02]"
                />
            </Link>
            <p className="mt-4 text-sm font-semibold text-oz-ink/70">
                {story.station?.name}
                {story.published_at && (
                    <>
                        ,{' '}
                        <time dateTime={story.published_at}>
                            {formatDate(story.published_at)}
                        </time>
                    </>
                )}
            </p>
            <h3 className="mt-2 font-oz-display text-xl leading-snug font-extrabold tracking-tight">
                <Link
                    href={href}
                    className="decoration-oz-yellow decoration-[3px] underline-offset-4 hover:underline focus-visible:underline focus-visible:outline-none"
                >
                    {story.title}
                </Link>
            </h3>
        </article>
    );
}
