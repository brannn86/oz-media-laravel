import { cn } from '@/lib/utils';

/** The logo files in public/images/logos, by station slug. */
const LOGOS: Record<string, { src: string; name: string }> = {
    'oz-media': { src: '/images/logos/oz-media.svg', name: 'OZ Media' },
    'oz-bandung': {
        src: '/images/logos/oz-bandung.svg',
        name: 'OZ Radio Bandung',
    },
    'oz-bali': { src: '/images/logos/oz-bali.svg', name: 'OZ Radio Bali' },
};

/**
 * An official OZ logo: the station's own (by slug), or OZ Media's for
 * anything without one. Size it with a height class; the width follows.
 * On a dark background, `onDark` adds a white outline so the logo's black
 * shadow doesn't disappear into it.
 */
export function BrandLogo({
    brand,
    onDark = false,
    className,
}: {
    brand: string;
    onDark?: boolean;
    className?: string;
}) {
    const logo = LOGOS[brand] ?? LOGOS['oz-media'];

    return (
        <img
            src={logo.src}
            alt={logo.name}
            className={cn(
                'block w-auto',
                onDark && 'oz-logo-stroke',
                className,
            )}
        />
    );
}
