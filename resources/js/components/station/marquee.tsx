import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/** Scroll speed, px per second: slow enough to read along. */
const SPEED = 40;
/** Rest at each end before turning around, ms. */
const PAUSE = 1800;

/**
 * One line of text that never wraps. When it doesn't fit, it glides to its
 * end and back, resting at each end; otherwise (or with reduced motion) it
 * just sits there, cut with an ellipsis if it must be.
 */
export function Marquee({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    const outerRef = useRef<HTMLSpanElement>(null);
    const innerRef = useRef<HTMLSpanElement>(null);
    const [overflow, setOverflow] = useState(0);
    const [still, setStill] = useState(false);

    useEffect(() => {
        setStill(window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }, []);

    // How far the text sticks out; re-measured when it or the box resizes.
    useEffect(() => {
        const outer = outerRef.current;
        const inner = innerRef.current;

        if (!outer || !inner || still) {
            return;
        }

        const measure = () =>
            setOverflow(
                Math.max(0, Math.ceil(inner.scrollWidth - outer.clientWidth)),
            );

        const observer = new ResizeObserver(measure);
        observer.observe(outer);
        observer.observe(inner);
        measure();

        return () => observer.disconnect();
    }, [still]);

    useEffect(() => {
        const inner = innerRef.current;

        if (!inner || overflow === 0 || still) {
            return;
        }

        const move = (overflow / SPEED) * 1000;
        const total = 2 * (PAUSE + move);
        const at = (ms: number) => ms / total;
        const end = `translateX(-${overflow}px)`;

        const animation = inner.animate(
            [
                { transform: 'translateX(0)', offset: 0 },
                { transform: 'translateX(0)', offset: at(PAUSE) },
                { transform: end, offset: at(PAUSE + move) },
                { transform: end, offset: at(2 * PAUSE + move) },
                { transform: 'translateX(0)', offset: 1 },
            ],
            { duration: total, iterations: Infinity, easing: 'linear' },
        );

        return () => animation.cancel();
    }, [overflow, still]);

    const scrolling = overflow > 0 && !still;

    return (
        <span
            ref={outerRef}
            className={cn(
                'block overflow-hidden whitespace-nowrap',
                still && 'text-ellipsis',
                // Soft edges while it moves; the bleed + padding keep the
                // text where it was and its first letter clear at rest.
                scrolling &&
                    '-mx-3 [mask-image:linear-gradient(to_right,transparent,#000_0.75rem,#000_calc(100%-0.75rem),transparent)]',
                className,
            )}
        >
            {/* inline-block so it can be measured and moved; plain inline
                when still, so a long line ends in an ellipsis. */}
            <span
                ref={innerRef}
                className={cn(
                    still ? 'inline' : 'inline-block',
                    scrolling && 'px-3',
                )}
            >
                {children}
            </span>
        </span>
    );
}
