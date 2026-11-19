import { Head, Link, usePage } from '@inertiajs/react';
import { LeadStory, StoryItem } from '@/components/station/news-items';
import { PageHeading } from '@/components/station/page-heading';
import type { Paginated, Post, StationPageProps } from '@/types';

type Props = StationPageProps & {
    posts: Paginated<Post>;
};

const pagerLink =
    'rounded-full border-2 border-oz-black px-5 py-2 text-sm font-bold transition-colors hover:bg-oz-black hover:text-oz-yellow focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none';

export default function StationNewsIndex() {
    const { station, meta, posts } = usePage<Props>().props;
    const firstPage = posts.meta.current_page === 1;
    const [lead, ...rest] = firstPage ? posts.data : [null, ...posts.data];

    return (
        <>
            <Head title={meta.title} />
            <PageHeading
                title="OZ News"
                intro={`Stories, music and what's happening around ${station.name}.`}
            />

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16">
                {posts.data.length === 0 ? (
                    <p className="text-lg text-oz-ink">
                        No stories yet. Check back soon.
                    </p>
                ) : (
                    <>
                        {lead && (
                            <div className="mb-14">
                                <LeadStory
                                    post={lead}
                                    stationSlug={station.slug}
                                />
                            </div>
                        )}
                        <div className="grid gap-x-10 gap-y-8 border-t border-oz-black/10 pt-10 md:grid-cols-2">
                            {rest.map(
                                (post) =>
                                    post && (
                                        <StoryItem
                                            key={post.id}
                                            post={post}
                                            stationSlug={station.slug}
                                        />
                                    ),
                            )}
                        </div>
                    </>
                )}

                {posts.meta.last_page > 1 && (
                    <nav
                        aria-label="News pages"
                        className="mt-14 flex items-center justify-between gap-4"
                    >
                        {posts.links.prev ? (
                            <Link href={posts.links.prev} className={pagerLink}>
                                Newer stories
                            </Link>
                        ) : (
                            <span />
                        )}
                        <span className="text-sm text-oz-ink">
                            Page {posts.meta.current_page} of{' '}
                            {posts.meta.last_page}
                        </span>
                        {posts.links.next ? (
                            <Link href={posts.links.next} className={pagerLink}>
                                Older stories
                            </Link>
                        ) : (
                            <span />
                        )}
                    </nav>
                )}
            </section>
        </>
    );
}
