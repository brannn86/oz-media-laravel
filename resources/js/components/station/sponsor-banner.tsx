import { Megaphone } from 'lucide-react';
import { cn } from '@/lib/utils';

/** The station's uploaded banner, or the house ad when it has none. */
export type Sponsor =
    | {
          name: string | null;
          url: string | null;
          image_url: string;
          mobile_image_url: string | null;
      }
    | { house_email: string };

/**
 * The ad space under the player: full width inside the page margins, 8:1
 * on wide screens and 4:1 on phones (the sizes the admin asks for).
 * `tone="dark"` for dark pages.
 */
export function SponsorBanner({
    sponsor,
    stationName,
    tone = 'light',
    className,
}: {
    sponsor: Sponsor;
    stationName: string;
    tone?: 'light' | 'dark';
    className?: string;
}) {
    const dark = tone === 'dark';

    return (
        <aside aria-label="Advertisement" className={className}>
            <p
                className={cn(
                    'mb-2 text-xs font-semibold tracking-wide uppercase',
                    dark ? 'text-oz-white/50' : 'text-oz-black/50',
                )}
            >
                Advertisement
            </p>
            {'image_url' in sponsor ? (
                <SponsorImage sponsor={sponsor} />
            ) : (
                <a
                    href={`mailto:${sponsor.house_email}?subject=${encodeURIComponent(`Advertising on ${stationName}`)}`}
                    className={cn(
                        'flex aspect-[4/1] w-full items-center justify-between gap-4 overflow-hidden rounded-2xl border-2 border-dashed px-5 transition-colors focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none sm:aspect-[8/1] sm:px-8',
                        dark
                            ? 'border-oz-white/25 text-oz-white hover:border-oz-yellow'
                            : 'border-oz-black/20 bg-oz-paper text-oz-black hover:border-oz-black',
                    )}
                >
                    <span className="min-w-0">
                        <span className="block font-oz-display text-lg leading-tight font-extrabold sm:text-2xl">
                            Your brand here
                        </span>
                        <span
                            className={cn(
                                'block truncate text-sm sm:text-base',
                                dark ? 'text-oz-white/70' : 'text-oz-ink',
                            )}
                        >
                            Advertise on {stationName}:{' '}
                            <span className="font-semibold">
                                {sponsor.house_email}
                            </span>
                        </span>
                    </span>
                    <Megaphone
                        className="size-8 shrink-0 text-oz-yellow sm:size-10"
                        aria-hidden
                    />
                </a>
            )}
        </aside>
    );
}

function SponsorImage({
    sponsor,
}: {
    sponsor: Extract<Sponsor, { image_url: string }>;
}) {
    const picture = (
        <picture>
            {sponsor.mobile_image_url && (
                <source
                    media="(max-width: 639px)"
                    srcSet={sponsor.mobile_image_url}
                />
            )}
            <img
                src={sponsor.image_url}
                alt={sponsor.name ?? 'Sponsor'}
                loading="lazy"
                // Locked to the sizes the admin asks for; other shapes are cropped
                // to fit. Without a phone banner, phones keep the wide shape.
                className={cn(
                    'block w-full rounded-2xl object-cover',
                    sponsor.mobile_image_url
                        ? 'aspect-[4/1] sm:aspect-[8/1]'
                        : 'aspect-[8/1]',
                )}
            />
        </picture>
    );

    return sponsor.url ? (
        <a
            href={sponsor.url}
            target="_blank"
            rel="sponsored noopener noreferrer"
            className="block rounded-2xl focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
        >
            {picture}
        </a>
    ) : (
        picture
    );
}
