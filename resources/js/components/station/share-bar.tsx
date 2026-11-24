import { Check, Link2, Share2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { BrandIcon } from '@/components/station/brand-icon';
import type { Brand } from '@/components/station/brand-icon';
import { cn } from '@/lib/utils';

const pill =
    'inline-flex h-9 items-center gap-1.5 rounded-full border border-oz-black/15 px-3.5 text-sm font-semibold transition-colors hover:border-oz-black hover:bg-oz-black hover:text-oz-yellow focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none';

/**
 * Share tools for an article: the device's own share sheet where there is
 * one, copy link, and the networks OZ listeners actually use.
 */
export function ShareBar({
    url,
    title,
    className,
}: {
    url: string;
    title: string;
    className?: string;
}) {
    const [copied, setCopied] = useState(false);
    const [canNativeShare, setCanNativeShare] = useState(false);

    useEffect(() => {
        setCanNativeShare(typeof navigator.share === 'function');
    }, []);

    useEffect(() => {
        if (!copied) {
            return;
        }

        const timer = window.setTimeout(() => setCopied(false), 2000);

        return () => window.clearTimeout(timer);
    }, [copied]);

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(url);
            setCopied(true);
        } catch {
            // Clipboard unavailable (insecure context or denied): the other
            // share options still work, so stay quiet.
        }
    };

    const encodedUrl = encodeURIComponent(url);
    const encodedText = encodeURIComponent(title);
    const networks: { brand: Brand; label: string; href: string }[] = [
        {
            brand: 'whatsapp',
            label: 'WhatsApp',
            href: `https://wa.me/?text=${encodeURIComponent(`${title} ${url}`)}`,
        },
        {
            brand: 'facebook',
            label: 'Facebook',
            href: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
        },
        {
            brand: 'x',
            label: 'X',
            href: `https://x.com/intent/post?url=${encodedUrl}&text=${encodedText}`,
        },
        {
            brand: 'telegram',
            label: 'Telegram',
            href: `https://t.me/share/url?url=${encodedUrl}&text=${encodedText}`,
        },
    ];

    return (
        <ul
            aria-label="Share this story"
            className={cn('flex flex-wrap items-center gap-2', className)}
        >
            {canNativeShare && (
                <li>
                    <button
                        type="button"
                        onClick={() =>
                            navigator.share({ title, url }).catch(() => {})
                        }
                        className={cn(
                            pill,
                            'border-oz-black bg-oz-black text-oz-white',
                        )}
                    >
                        <Share2 className="size-4" aria-hidden />
                        Share
                    </button>
                </li>
            )}
            <li>
                <button type="button" onClick={copy} className={pill}>
                    {copied ? (
                        <Check className="size-4" aria-hidden />
                    ) : (
                        <Link2 className="size-4" aria-hidden />
                    )}
                    <span aria-live="polite">
                        {copied ? 'Link copied' : 'Copy link'}
                    </span>
                </button>
            </li>
            {networks.map((network) => (
                <li key={network.label}>
                    <a
                        href={network.href}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={pill}
                    >
                        <BrandIcon brand={network.brand} />
                        {network.label}
                        <span className="sr-only"> (opens in a new tab)</span>
                    </a>
                </li>
            ))}
        </ul>
    );
}
