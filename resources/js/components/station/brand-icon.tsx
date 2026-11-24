import {
    siFacebook,
    siInstagram,
    siTelegram,
    siThreads,
    siTiktok,
    siWhatsapp,
    siX,
    siYoutube,
} from 'simple-icons';
import type { SimpleIcon } from 'simple-icons';
import { cn } from '@/lib/utils';

const ICONS = {
    facebook: siFacebook,
    instagram: siInstagram,
    telegram: siTelegram,
    threads: siThreads,
    tiktok: siTiktok,
    whatsapp: siWhatsapp,
    x: siX,
    youtube: siYoutube,
} satisfies Record<string, SimpleIcon>;

export type Brand = keyof typeof ICONS;

export const BRAND_LABELS: Record<Brand, string> = {
    facebook: 'Facebook',
    instagram: 'Instagram',
    telegram: 'Telegram',
    threads: 'Threads',
    tiktok: 'TikTok',
    whatsapp: 'WhatsApp',
    x: 'X',
    youtube: 'YouTube',
};

export function isBrand(name: string): name is Brand {
    return name in ICONS;
}

/**
 * A social network's logo (Simple Icons, CC0), drawn in the current text
 * colour. Decorative: pair it with the network's name, visible or sr-only.
 */
export function BrandIcon({
    brand,
    className,
}: {
    brand: Brand;
    className?: string;
}) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="currentColor"
            aria-hidden
            focusable="false"
            className={cn('size-4 shrink-0', className)}
        >
            <path d={ICONS[brand].path} />
        </svg>
    );
}
