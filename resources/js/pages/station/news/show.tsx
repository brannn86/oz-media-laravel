import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useRef } from 'react';
import { CoverImage } from '@/components/station/cover-image';
import {
    CategoryKicker,
    StoryCard,
    categoryHref,
} from '@/components/station/news-items';
import { ShareBar } from '@/components/station/share-bar';
import { useReadingDepth } from '@/hooks/use-reading-depth';
import { formatDate, formatTime } from '@/lib/wib';
import stationRoutes from '@/routes/station';
import type { Post, StationPageProps } from '@/types';

type Props = StationPageProps & {
    post: Post;
    more: Post[];
    mostRead: Post[];
};

/** "24 November 2026, 09:00 WIB" */
function stamp(iso: string) {
    return `${formatDate(iso)}, ${formatTime(iso)} WIB`;
}

const crumbLink =
    'rounded font-bold decoration-oz-yellow decoration-2 underline-offset-4 hover:underline focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none';

export default function StationNewsShow() {
    const { station, meta, post, more, mostRead } = usePage<Props>().props;
    const newsHref = stationRoutes.news.index(station.slug);
    const bodyRef = useRef<HTMLDivElement>(null);

    // How far the article is read, for Analytics > News.
    useReadingDepth(bodyRef, station.slug, post.slug);

    // "More in Sports" only when every card really is from that section.
    const moreSection =
        post.category &&
        more.every((item) => item.category?.value === post.category?.value)
            ? post.category
            : null;

    return (
        <>
            <Head title={meta.title} />

            <div className="mx-auto max-w-7xl px-4 pt-8 pb-16 sm:px-6 sm:pt-12 lg:grid lg:grid-cols-[minmax(0,1fr)_18rem] lg:gap-14 xl:gap-20">
                <article className="min-w-0">
                    <header className="max-w-3xl">
                        <nav aria-label="Breadcrumb">
                            <ol className="flex flex-wrap items-center gap-1.5 text-sm">
                                <li>
                                    <Link href={newsHref} className={crumbLink}>
                                        OZ News
                                    </Link>
                                </li>
                                {post.category && (
                                    <li className="flex items-center gap-1.5">
                                        <ChevronRight
                                            className="size-4 text-oz-ink/50"
                                            aria-hidden
                                        />
                                        <Link
                                            href={categoryHref(
                                                station.slug,
                                                post.category,
                                            )}
                                            className={crumbLink}
                                        >
                                            {post.category.label}
                                        </Link>
                                    </li>
                                )}
                            </ol>
                        </nav>

                        <h1 className="mt-5 font-oz-display text-4xl leading-[1] font-extrabold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                            {post.title}
                        </h1>
                        {post.excerpt && (
                            <p className="mt-5 text-xl leading-relaxed text-oz-ink">
                                {post.excerpt}
                            </p>
                        )}

                        <div className="mt-8 flex flex-col gap-4 border-y border-oz-black/10 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="text-sm">
                                {post.author && (
                                    <p className="font-bold">
                                        By {post.author}
                                    </p>
                                )}
                                <p className="mt-0.5 text-oz-ink/75">
                                    {post.published_at && (
                                        <>
                                            Published{' '}
                                            <time dateTime={post.published_at}>
                                                {stamp(post.published_at)}
                                            </time>
                                        </>
                                    )}
                                    {post.updated_at && (
                                        <span className="block sm:inline">
                                            <span
                                                aria-hidden
                                                className="mx-2 hidden sm:inline"
                                            >
                                                ·
                                            </span>
                                            Updated{' '}
                                            <time dateTime={post.updated_at}>
                                                {stamp(post.updated_at)}
                                            </time>
                                        </span>
                                    )}
                                </p>
                            </div>
                            <ShareBar url={meta.url} title={post.title} />
                        </div>
                    </header>

                    {post.image_url && (
                        <figure className="mt-8 max-w-4xl">
                            <CoverImage
                                src={post.image_url}
                                label={post.title}
                                alt={post.image_caption ?? ''}
                                className="aspect-video w-full rounded-2xl"
                            />
                            {(post.image_caption || post.image_credit) && (
                                <figcaption className="mt-3 text-sm leading-relaxed text-oz-ink">
                                    {post.image_caption}
                                    {post.image_credit && (
                                        <span className="ml-1.5 text-oz-ink/60">
                                            Credit: {post.image_credit}
                                        </span>
                                    )}
                                </figcaption>
                            )}
                        </figure>
                    )}

                    <div
                        ref={bodyRef}
                        className="oz-prose mt-10 max-w-[68ch]"
                        dangerouslySetInnerHTML={{ __html: post.body ?? '' }}
                    />

                    {(post.source || post.category) && (
                        <footer className="mt-12 max-w-[68ch] space-y-3 border-t border-oz-black/10 pt-6 text-sm">
                            {post.source && (
                                <p>
                                    <span className="font-bold">Source: </span>
                                    {post.source.url ? (
                                        <a
                                            href={post.source.url}
                                            target="_blank"
                                            rel="noopener noreferrer nofollow"
                                            className="underline decoration-oz-yellow decoration-2 underline-offset-4 hover:decoration-oz-black"
                                        >
                                            {post.source.name ??
                                                new URL(post.source.url)
                                                    .hostname}
                                        </a>
                                    ) : (
                                        post.source.name
                                    )}
                                </p>
                            )}
                            {post.category && (
                                <p className="flex items-center gap-2">
                                    <span className="font-bold">
                                        Filed under
                                    </span>
                                    <CategoryKicker
                                        post={post}
                                        stationSlug={station.slug}
                                    />
                                </p>
                            )}
                        </footer>
                    )}
                </article>

                {mostRead.length > 0 && (
                    <aside
                        aria-labelledby="most-read-title"
                        className="hidden lg:block"
                    >
                        <div className="sticky top-24 border-t-4 border-oz-black pt-4">
                            <h2
                                id="most-read-title"
                                className="font-oz-display text-xl font-extrabold tracking-tight"
                            >
                                Most read
                            </h2>
                            <ol className="mt-2 divide-y divide-oz-black/10">
                                {mostRead.map((item, index) => (
                                    <li
                                        key={item.id}
                                        className="flex gap-4 py-4"
                                    >
                                        <span
                                            aria-hidden
                                            className="w-7 shrink-0 font-oz-display text-3xl leading-none font-extrabold text-oz-black/25"
                                        >
                                            {index + 1}
                                        </span>
                                        <Link
                                            href={stationRoutes.news.show({
                                                station: station.slug,
                                                slug: item.slug,
                                            })}
                                            aria-current={
                                                item.id === post.id
                                                    ? 'page'
                                                    : undefined
                                            }
                                            className="text-[0.95rem] leading-snug font-bold decoration-oz-yellow decoration-2 underline-offset-4 hover:underline focus-visible:underline focus-visible:outline-none aria-[current=page]:text-oz-ink/60"
                                        >
                                            {item.title}
                                        </Link>
                                    </li>
                                ))}
                            </ol>
                        </div>
                    </aside>
                )}
            </div>

            {more.length > 0 && (
                <section aria-labelledby="more-title" className="bg-oz-paper">
                    <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6">
                        <div className="flex items-end justify-between gap-4">
                            <h2
                                id="more-title"
                                className="font-oz-display text-2xl font-extrabold tracking-tight sm:text-3xl"
                            >
                                {moreSection
                                    ? `More in ${moreSection.label}`
                                    : 'More from OZ News'}
                            </h2>
                            <Link
                                href={
                                    moreSection
                                        ? categoryHref(
                                              station.slug,
                                              moreSection,
                                          )
                                        : newsHref
                                }
                                className="shrink-0 text-sm font-bold underline decoration-oz-yellow decoration-[3px] underline-offset-4 hover:decoration-oz-black"
                            >
                                See all
                            </Link>
                        </div>
                        <div className="mt-8 grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
                            {more.map((item) => (
                                <StoryCard
                                    key={item.id}
                                    post={item}
                                    stationSlug={station.slug}
                                />
                            ))}
                        </div>
                    </div>
                </section>
            )}
        </>
    );
}
