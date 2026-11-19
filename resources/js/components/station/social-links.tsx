import { cn } from '@/lib/utils';

const LABELS: Record<string, string> = {
    youtube: 'YouTube',
    tiktok: 'TikTok',
    instagram: 'Instagram',
    x: 'X',
};

/**
 * Text links rather than glyphs: lucide no longer ships brand icons, and
 * named links are clearer for screen readers anyway.
 */
export function SocialLinks({
    social,
    className,
    tone = 'light',
}: {
    social: Partial<Record<string, string>>;
    className?: string;
    tone?: 'light' | 'dark';
}) {
    const entries = Object.entries(social).filter(
        (entry): entry is [string, string] => Boolean(entry[1]),
    );

    if (entries.length === 0) {
        return null;
    }

    return (
        <ul className={cn('flex flex-wrap gap-2', className)}>
            {entries.map(([network, url]) => (
                <li key={network}>
                    <a
                        href={url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={cn(
                            'inline-flex rounded-full border px-4 py-1.5 text-sm font-semibold transition-colors focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none',
                            tone === 'dark'
                                ? 'border-oz-white/20 text-oz-white hover:border-oz-yellow hover:bg-oz-yellow hover:text-oz-black'
                                : 'border-oz-black/15 text-oz-black hover:border-oz-black hover:bg-oz-black hover:text-oz-yellow',
                        )}
                    >
                        {LABELS[network] ?? network}
                    </a>
                </li>
            ))}
        </ul>
    );
}
