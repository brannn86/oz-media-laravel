import { Link } from '@inertiajs/react';
import { CoverImage } from '@/components/station/cover-image';
import { formatDate } from '@/lib/wib';
import stationRoutes from '@/routes/station';
import type { Post } from '@/types';

function postHref(stationSlug: string, post: Post) {
    return stationRoutes.news.show({
        station: post.station?.slug ?? stationSlug,
        slug: post.slug,
    });
}

/** The big story: image left, headline right on wide screens. */
export function LeadStory({
    post,
    stationSlug,
}: {
    post: Post;
    stationSlug: string;
}) {
    return (
        <article className="group grid gap-6 md:grid-cols-[1.25fr_1fr] md:items-center md:gap-10">
            <Link
                href={postHref(stationSlug, post)}
                tabIndex={-1}
                aria-hidden
                className="block overflow-hidden rounded-3xl"
            >
                <CoverImage
                    src={post.image_url}
                    label="OZ News"
                    className="aspect-[16/10] w-full transition-transform duration-500 group-hover:scale-[1.02]"
                />
            </Link>
            <div>
                {post.published_at && (
                    <p className="text-sm font-semibold text-oz-ink/70">
                        <time dateTime={post.published_at}>
                            {formatDate(post.published_at)}
                        </time>
                    </p>
                )}
                <h3 className="mt-3 font-oz-display text-3xl leading-[1.05] font-extrabold tracking-tight text-balance sm:text-4xl">
                    <Link
                        href={postHref(stationSlug, post)}
                        className="decoration-oz-yellow decoration-4 underline-offset-4 hover:underline focus-visible:underline focus-visible:outline-none"
                    >
                        {post.title}
                    </Link>
                </h3>
                {post.excerpt && (
                    <p className="mt-4 text-lg leading-relaxed text-oz-ink">
                        {post.excerpt}
                    </p>
                )}
            </div>
        </article>
    );
}

/** A compact story: thumbnail and headline. */
export function StoryItem({
    post,
    stationSlug,
}: {
    post: Post;
    stationSlug: string;
}) {
    return (
        <article className="group flex gap-4">
            <Link
                href={postHref(stationSlug, post)}
                tabIndex={-1}
                aria-hidden
                className="block w-28 shrink-0 overflow-hidden rounded-2xl sm:w-36"
            >
                <CoverImage
                    src={post.image_url}
                    label="OZ News"
                    className="aspect-square w-full"
                />
            </Link>
            <div className="min-w-0 py-1">
                <h3 className="font-oz-display text-lg leading-snug font-extrabold tracking-tight">
                    <Link
                        href={postHref(stationSlug, post)}
                        className="decoration-oz-yellow decoration-[3px] underline-offset-4 hover:underline focus-visible:underline focus-visible:outline-none"
                    >
                        {post.title}
                    </Link>
                </h3>
                {post.published_at && (
                    <p className="mt-2 text-sm text-oz-ink/70">
                        <time dateTime={post.published_at}>
                            {formatDate(post.published_at)}
                        </time>
                    </p>
                )}
            </div>
        </article>
    );
}
