import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { StoryItem } from '@/components/station/news-items';
import { formatDate } from '@/lib/wib';
import stationRoutes from '@/routes/station';
import type { Post, StationPageProps } from '@/types';

type Props = StationPageProps & {
    post: Post;
    more: Post[];
};

export default function StationNewsShow() {
    const { station, meta, post, more } = usePage<Props>().props;

    return (
        <>
            <Head title={meta.title} />

            <article>
                <header className="mx-auto max-w-4xl px-4 pt-10 sm:px-6 sm:pt-14">
                    <Link
                        href={stationRoutes.news.index(station.slug)}
                        className="inline-flex items-center gap-2 rounded-full text-sm font-bold hover:underline focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                    >
                        <ArrowLeft className="size-4" aria-hidden />
                        OZ News
                    </Link>
                    <h1 className="mt-6 font-oz-display text-4xl leading-[1] font-extrabold tracking-tight text-balance sm:text-6xl">
                        {post.title}
                    </h1>
                    {post.excerpt && (
                        <p className="mt-6 text-xl leading-relaxed text-oz-ink">
                            {post.excerpt}
                        </p>
                    )}
                    <p className="mt-6 flex flex-wrap gap-x-4 text-sm text-oz-ink/80">
                        {post.published_at && (
                            <time dateTime={post.published_at}>
                                {formatDate(post.published_at)}
                            </time>
                        )}
                        {post.author && <span>By {post.author}</span>}
                    </p>
                </header>

                {post.image_url && (
                    <div className="mx-auto mt-10 max-w-6xl px-4 sm:px-6">
                        <img
                            src={post.image_url}
                            alt=""
                            className="aspect-[16/9] w-full rounded-3xl object-cover"
                        />
                    </div>
                )}

                <div
                    className="oz-prose mx-auto mt-10 max-w-[68ch] px-4 pb-16 sm:px-6"
                    dangerouslySetInnerHTML={{ __html: post.body ?? '' }}
                />
            </article>

            {more.length > 0 && (
                <section aria-labelledby="more-title" className="bg-oz-paper">
                    <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6">
                        <h2
                            id="more-title"
                            className="font-oz-display text-2xl font-extrabold tracking-tight"
                        >
                            More from OZ News
                        </h2>
                        <div className="mt-8 grid gap-8 md:grid-cols-3">
                            {more.map((item) => (
                                <StoryItem
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
