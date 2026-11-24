import {
    BRAND_LABELS,
    BrandIcon,
    isBrand,
} from '@/components/station/brand-icon';
import type { Brand } from '@/components/station/brand-icon';
import { cn } from '@/lib/utils';

/** The order networks always appear in. */
const ORDER: Brand[] = [
    'instagram',
    'tiktok',
    'youtube',
    'threads',
    'x',
    'facebook',
];

/**
 * Links to a station's or announcer's socials, each with the network's logo.
 * `pills` shows the name next to the logo; `icons` is logo-only round
 * buttons (the name stays for screen readers).
 */
export function SocialLinks({
    social,
    className,
    tone = 'light',
    variant = 'pills',
}: {
    social: Partial<Record<string, string>>;
    className?: string;
    tone?: 'light' | 'dark';
    variant?: 'pills' | 'icons';
}) {
    const entries = ORDER.flatMap((network) => {
        const url = social[network];

        return url ? [[network, url] as const] : [];
    });

    // Anything not in ORDER keeps its place at the end, named as stored.
    const others = Object.entries(social).filter(
        (entry): entry is [string, string] =>
            Boolean(entry[1]) && !(ORDER as string[]).includes(entry[0]),
    );

    if (entries.length + others.length === 0) {
        return null;
    }

    const icons = variant === 'icons';

    return (
        <ul className={cn('flex flex-wrap gap-2', className)}>
            {[...entries, ...others].map(([network, url]) => {
                const label = isBrand(network)
                    ? BRAND_LABELS[network]
                    : network;

                return (
                    <li key={network}>
                        <a
                            href={url}
                            target="_blank"
                            rel="noopener noreferrer"
                            title={icons ? label : undefined}
                            className={cn(
                                'inline-flex items-center rounded-full border font-semibold transition-colors focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none',
                                icons
                                    ? 'size-11 justify-center'
                                    : 'gap-2 px-4 py-1.5 text-sm',
                                tone === 'dark'
                                    ? 'border-oz-white/20 text-oz-white hover:border-oz-yellow hover:bg-oz-yellow hover:text-oz-black'
                                    : 'border-oz-black/15 text-oz-black hover:border-oz-black hover:bg-oz-black hover:text-oz-yellow',
                            )}
                        >
                            {isBrand(network) && (
                                <BrandIcon
                                    brand={network}
                                    className={icons ? 'size-5' : undefined}
                                />
                            )}
                            <span className={icons ? 'sr-only' : undefined}>
                                {label}
                            </span>
                        </a>
                    </li>
                );
            })}
        </ul>
    );
}
