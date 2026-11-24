import { cn } from '@/lib/utils';

/**
 * An image fitted whole into its box. When the image's shape doesn't match
 * the box, the gaps are filled with a blurred, zoomed copy of itself (a
 * pillarbox or letterbox), so nothing gets cropped. With no image, a
 * brand-yellow panel with a short label, so missing uploads still look
 * intentional.
 */
export function CoverImage({
    src,
    label,
    alt = '',
    className,
}: {
    src: string | null;
    label: string;
    alt?: string;
    className?: string;
}) {
    if (src) {
        return (
            <div
                className={cn('relative overflow-hidden bg-oz-black', className)}
            >
                <img
                    src={src}
                    alt=""
                    aria-hidden
                    loading="lazy"
                    className="absolute inset-0 size-full scale-125 object-cover opacity-80 blur-2xl"
                />
                <img
                    src={src}
                    alt={alt}
                    loading="lazy"
                    className="absolute inset-0 size-full object-contain"
                />
            </div>
        );
    }

    return (
        <div
            aria-hidden
            className={cn(
                'flex items-end overflow-hidden bg-oz-yellow p-4 sm:p-5',
                className,
            )}
        >
            <span className="font-oz-display text-xl leading-tight font-extrabold tracking-tight text-oz-black/80">
                {label}
            </span>
        </div>
    );
}
