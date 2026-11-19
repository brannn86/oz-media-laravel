import { cn } from '@/lib/utils';

/**
 * Text wordmark used until the official OZ logo files are added to /public.
 * Yellow + black per the brand guideline.
 */
export function OzMark({
    suffix,
    className,
    inverted = false,
}: {
    suffix?: string;
    className?: string;
    inverted?: boolean;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-2 font-oz-display',
                className,
            )}
        >
            <span
                aria-hidden
                className={cn(
                    'grid h-9 place-items-center rounded-[0.9rem] px-2.5 text-xl leading-none font-extrabold tracking-tighter',
                    inverted
                        ? 'bg-oz-yellow text-oz-black'
                        : 'bg-oz-black text-oz-yellow',
                )}
            >
                OZ
            </span>
            {suffix && (
                <span className="text-lg leading-none font-extrabold tracking-tight">
                    {suffix}
                </span>
            )}
        </span>
    );
}
