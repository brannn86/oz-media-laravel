import { cn } from '@/lib/utils';

/**
 * An image, or a brand-yellow panel with a short label when there is none,
 * so missing uploads still look intentional.
 */
export function CoverImage({
    src,
    label,
    className,
}: {
    src: string | null;
    label: string;
    className?: string;
}) {
    if (src) {
        return (
            <img
                src={src}
                alt=""
                loading="lazy"
                className={cn('object-cover', className)}
            />
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
