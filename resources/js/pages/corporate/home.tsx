import { Head, Link, usePage } from '@inertiajs/react';
import { Radio } from 'lucide-react';
import {
    OzubathonGround,
    OzubathonLockup,
    OzubathonMeter,
} from '@/components/ozubathon/ozubathon-parts';
import { CoverImage } from '@/components/station/cover-image';
import { BrandLogo } from '@/components/station/brand-logo';
import { formatDate } from '@/lib/wib';
import { home } from '@/routes';
import stationRoutes from '@/routes/station';
import type { Ozubathon, PageMeta, Post } from '@/types';

type Props = {
    meta: PageMeta;
    stations: { slug: string; name: string; tagline: string | null }[];
    stories: Post[];
    /** The station running the Ozubathon theme, for the spotlight. */
    ozubathon:
        | (Ozubathon & { station: { slug: string; name: string }; now: string })
        | null;
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
    const { meta, stations, stories, ozubathon } = usePage<Props>().props;

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

                {ozubathon && (
                    <section
                        aria-labelledby="ozubathon-spotlight"
                        className="mb-16 sm:mb-24"
                    >
                        <OzubathonGround bandClassName="top-[10%] bottom-[10%]">
                            <div className="mx-auto grid max-w-7xl grid-cols-[minmax(0,1fr)] items-center gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:gap-16">
                                <div id="ozubathon-spotlight">
                                    <OzubathonLockup
                                        event={ozubathon}
                                        as="h2"
                                        size="compact"
                                    />
                                </div>
                                <div className="flex flex-col items-start gap-6">
                                    <p className="max-w-md bg-oz-white px-4 py-3 font-ozb text-lg leading-snug font-medium shadow-[0_10px_20px_-10px_rgba(0,0,0,0.4)]">
                                        OZ Radio turns 55, and goes live for 55
                                        hours straight: on-air, online and
                                        on-ground.
                                    </p>
                                    <OzubathonMeter
                                        event={ozubathon}
                                        initialNow={Date.parse(ozubathon.now)}
                                        className="w-full max-w-xl -rotate-1"
                                    />
                                    <Link
                                        href={stationRoutes.home(
                                            ozubathon.station.slug,
                                        )}
                                        className="inline-flex h-14 items-center gap-3 rounded-full bg-oz-black px-7 font-ozb text-lg font-semibold text-oz-white transition-transform hover:scale-[1.02] focus-visible:ring-4 focus-visible:ring-oz-black/30 focus-visible:outline-none"
                                    >
                                        <Radio
                                            className="size-6 text-ozb-yellow"
                                            aria-hidden
                                        />
                                        Tune in on {ozubathon.station.name}
                                    </Link>
                                </div>
                            </div>
                        </OzubathonGround>
                    </section>
                )}

                <section
                    id="stations"
                    aria-labelledby="stations-title"
                    className="mx-auto max-w-7xl scroll-mt-6 px-4 sm:px-6"
                >
                    <h2 id="stations-title" className="sr-only">
                        Our stations
                    </h2>
                    <ul className="flex flex-wrap items-end gap-x-16 gap-y-10 border-y-2 border-oz-black py-12 sm:py-16">
                        {stations.map((station) => (
                            <li key={station.slug}>
                                <Link
                                    href={stationRoutes.home(station.slug)}
                                    aria-label={`${station.name}${station.tagline ? `: ${station.tagline}` : ''}`}
                                    className="group block rounded-2xl focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:ring-offset-4 focus-visible:outline-none"
                                >
                                    <BrandLogo
                                        brand={station.slug}
                                        className="h-28 transition-transform duration-300 group-hover:-translate-y-1 group-hover:rotate-[-2deg] sm:h-36"
                                    />
                                </Link>
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
