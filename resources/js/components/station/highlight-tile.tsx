import { Play } from 'lucide-react';
import { BrandIcon } from '@/components/station/brand-icon';
import { cn } from '@/lib/utils';
import type { SocialHighlight } from '@/types';

const PLATFORM_STYLE: Record<SocialHighlight['platform'], string> = {
    youtube: 'bg-oz-red text-oz-white',
    tiktok: 'bg-oz-black text-oz-white',
    instagram: 'bg-oz-butter text-oz-black',
};

/**
 * A clip from OZ's socials, opening on the platform. Every tile is a 16:9
 * box: long-form videos fill it, vertical ones (Shorts, TikTok, Reels) sit
 * in a portrait frame in the middle over a blurred copy of themselves.
 */
export function HighlightTile({ highlight }: { highlight: SocialHighlight }) {
    const portrait = highlight.orientation === 'portrait';

    return (
        <a
            href={highlight.url}
            target="_blank"
            rel="noopener noreferrer"
            className="group relative block w-72 shrink-0 snap-start focus-visible:outline-none sm:w-96"
        >
            <div
                className={cn(
                    'relative aspect-video overflow-hidden rounded-3xl ring-oz-black transition group-focus-visible:ring-4',
                    PLATFORM_STYLE[highlight.platform],
                )}
            >
                {highlight.thumbnail_url ? (
                    <>
                        {portrait && (
                            <img
                                src={highlight.thumbnail_url}
                                alt=""
                                aria-hidden
                                loading="lazy"
                                className="absolute inset-0 size-full scale-125 object-cover opacity-80 blur-2xl"
                            />
                        )}
                        <div
                            className={cn(
                                'absolute inset-y-0 overflow-hidden',
                                portrait
                                    ? 'left-1/2 aspect-[9/16] -translate-x-1/2'
                                    : 'inset-x-0',
                            )}
                        >
                            <img
                                src={highlight.thumbnail_url}
                                alt=""
                                loading="lazy"
                                className="size-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                        </div>
                    </>
                ) : (
                    <p className="absolute inset-x-5 bottom-5 font-oz-display text-2xl leading-[1] font-extrabold tracking-tight">
                        {highlight.title}
                    </p>
                )}
                <span className="absolute top-4 left-4 inline-flex items-center gap-1.5 rounded-full bg-oz-white px-3 py-1 text-xs font-bold text-oz-black">
                    <BrandIcon
                        brand={highlight.platform}
                        className="size-3.5"
                    />
                    {highlight.platform_label}
                </span>
                <span className="absolute top-4 right-4 grid size-9 place-items-center rounded-full bg-oz-yellow text-oz-black">
                    <Play className="size-4 translate-x-px fill-current" />
                </span>
            </div>
            {highlight.thumbnail_url && (
                <p className="mt-3 line-clamp-2 font-semibold group-hover:underline">
                    {highlight.title}
                </p>
            )}
            <span className="sr-only">
                (opens on {highlight.platform_label})
            </span>
        </a>
    );
}
