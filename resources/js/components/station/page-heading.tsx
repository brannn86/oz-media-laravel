import type { ReactNode } from 'react';

export function PageHeading({
    title,
    intro,
    children,
}: {
    title: string;
    intro?: string | null;
    children?: ReactNode;
}) {
    return (
        <div className="border-b border-oz-black/10">
            <div className="mx-auto max-w-7xl px-4 pt-14 pb-10 sm:px-6 sm:pt-20">
                <h1 className="font-oz-display text-5xl leading-[0.95] font-extrabold tracking-tight text-balance sm:text-7xl">
                    {title}
                </h1>
                {intro && (
                    <p className="mt-5 max-w-2xl text-lg text-oz-ink">
                        {intro}
                    </p>
                )}
                {children}
            </div>
        </div>
    );
}

export function SectionHeading({
    id,
    title,
    action,
}: {
    id?: string;
    title: string;
    action?: ReactNode;
}) {
    return (
        <div className="mb-8 flex items-end justify-between gap-4">
            <h2
                id={id}
                className="font-oz-display text-3xl leading-none font-extrabold tracking-tight sm:text-4xl"
            >
                {title}
            </h2>
            {action}
        </div>
    );
}
