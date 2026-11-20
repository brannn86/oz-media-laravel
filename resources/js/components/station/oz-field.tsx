import type { ReactNode } from 'react';

/** Text inputs on the station site (auth popup, profile settings). */
export const ozInputClass =
    'h-12 rounded-xl border-2 border-oz-black/15 bg-oz-white px-4 font-oz text-base text-oz-black shadow-none placeholder:text-oz-black/40 focus-visible:border-oz-black focus-visible:ring-4 focus-visible:ring-oz-yellow/60 md:text-base';

export const ozPrimaryButtonClass =
    'inline-flex h-12 items-center justify-center gap-2 rounded-full bg-oz-black px-6 font-bold text-oz-white transition-transform hover:scale-[1.01] focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none disabled:pointer-events-none disabled:opacity-60';

export const ozSecondaryButtonClass =
    'inline-flex h-12 items-center justify-center gap-2 rounded-full border-2 border-oz-black/15 px-6 font-bold text-oz-black transition-colors hover:border-oz-black focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none';

export const ozTextButtonClass =
    'rounded font-bold text-oz-black underline decoration-oz-yellow decoration-[3px] underline-offset-4 hover:decoration-oz-black focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none';

export function OzField({
    id,
    label,
    error,
    aside,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    aside?: ReactNode;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-1.5">
            <div className="flex items-baseline justify-between gap-3">
                <label htmlFor={id} className="text-sm font-bold">
                    {label}
                </label>
                {aside}
            </div>
            {children}
            {error && (
                <p
                    id={`${id}-error`}
                    className="text-sm font-semibold text-oz-red"
                >
                    {error}
                </p>
            )}
        </div>
    );
}
