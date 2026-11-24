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
 * A clip from OZ's socials, opening on the platform, in the shape it was
 * shot: long-form YouTube videos in a 16:9 box, Shorts, TikToks and Reels
 * in a 9:16 box. Widths are set so every tile in the row is the same
 * height (12rem, 18rem from `sm`).
 */
export function HighlightTile({ highlight }: { highlight: SocialHighlight }) {
    const portrait = highlight.orientation === 'portrait';

    return (
        <a
            href={highlight.url}
            target="_blank"
            rel="noopener noreferrer"
            className={cn(
                'group relative block shrink-0 snap-start focus-visible:outline-none',
                portrait
                    ? 'w-[6.75rem] sm:w-[10.125rem]'
                    : 'w-[21.333rem] sm:w-[32rem]',
            )}
        >
            <div
                className={cn(
                    'relative overflow-hidden ring-oz-black transition group-focus-visible:ring-4',
                    portrait
                        ? 'aspect-[9/16] rounded-2xl'
                        : 'aspect-video rounded-3xl',
                    PLATFORM_STYLE[highlight.platform],
                )}
            >
                {highlight.thumbnail_url ? (
                    <img
                        src={highlight.thumbnail_url}
                        alt=""
                        loading="lazy"
                        className="size-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                ) : (
                    <p
                        className={cn(
                            'absolute inset-x-3 bottom-3 font-oz-display leading-[1] font-extrabold tracking-tight sm:inset-x-5 sm:bottom-5',
                            portrait ? 'text-base sm:text-xl' : 'text-2xl',
                        )}
                    >
                        {highlight.title}
                    </p>
                )}
                <span
                    title={highlight.platform_label}
                    className="absolute top-3 left-3 grid size-8 place-items-center rounded-full bg-oz-white text-oz-black sm:top-4 sm:left-4"
                >
                    <BrandIcon brand={highlight.platform} />
                </span>
                <span className="absolute top-3 right-3 grid size-8 place-items-center rounded-full bg-oz-yellow text-oz-black sm:top-4 sm:right-4">
                    <Play className="size-3.5 translate-x-px fill-current" />
                </span>
            </div>
            {highlight.thumbnail_url && (
                <p
                    className={cn(
                        'mt-3 font-semibold group-hover:underline',
                        portrait
                            ? 'line-clamp-3 text-sm leading-snug'
                            : 'line-clamp-2',
                    )}
                >
                    {highlight.title}
                </p>
            )}
            <span className="sr-only">
                (opens on {highlight.platform_label})
            </span>
        </a>
    );
}
