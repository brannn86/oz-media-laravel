import { Head, Link, usePage } from '@inertiajs/react';
import {
    LeadStory,
    StoryItem,
    categoryHref,
} from '@/components/station/news-items';
import { PageHeading } from '@/components/station/page-heading';
import { cn } from '@/lib/utils';
import stationRoutes from '@/routes/station';
import type { Paginated, Post, PostCategory, StationPageProps } from '@/types';

type Props = StationPageProps & {
    posts: Paginated<Post>;
    category: PostCategory | null;
    categories: PostCategory[];
};

const sectionLink =
    'inline-flex shrink-0 rounded-full px-4 py-1.5 text-sm font-bold whitespace-nowrap transition-colors focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none';

const pagerLink =
    'rounded-full border-2 border-oz-black px-5 py-2 text-sm font-bold transition-colors hover:bg-oz-black hover:text-oz-yellow focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none';

export default function StationNewsIndex() {
    const { station, meta, posts, category, categories } =
        usePage<Props>().props;
    const firstPage = posts.meta.current_page === 1;
    const [lead, ...rest] = firstPage ? posts.data : [null, ...posts.data];

    return (
        <>
            <Head title={meta.title} />
            <PageHeading
                title={category ? category.label : 'OZ News'}
                intro={
                    category
                        ? `The latest ${category.label.toLowerCase()} stories from OZ News.`
                        : `Stories, music and what's happening around ${station.name}.`
                }
            >
                <nav aria-label="News sections" className="-mx-4 mt-8 sm:-mx-6">
                    <ul className="flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] sm:px-6 [&::-webkit-scrollbar]:hidden">
                        {[null, ...categories].map((section) => {
                            const active =
                                (section?.value ?? null) ===
                                (category?.value ?? null);

                            return (
                                <li key={section?.value ?? 'all'}>
                                    <Link
                                        href={
                                            section
                                                ? categoryHref(
                                                      station.slug,
                                                      section,
                                                  )
                                                : stationRoutes.news.index(
                                                      station.slug,
                                                  )
                                        }
                                        aria-current={
                                            active ? 'page' : undefined
                                        }
                                        className={cn(
                                            sectionLink,
                                            active
                                                ? 'bg-oz-black text-oz-yellow'
                                                : 'bg-oz-paper hover:bg-oz-yellow',
                                        )}
                                    >
                                        {section?.label ?? 'All'}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </nav>
            </PageHeading>

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16">
                {posts.data.length === 0 ? (
                    <p className="text-lg text-oz-ink">
                        {category
                            ? `No ${category.label.toLowerCase()} stories yet. Check back soon.`
                            : 'No stories yet. Check back soon.'}
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
