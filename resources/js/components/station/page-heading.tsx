import type { ReactNode } from 'react';

export function PageHeading({
    title,
    intro,
    illustration,
    children,
}: {
    title: string;
    intro?: string | null;
    illustration?: ReactNode;
    children?: ReactNode;
}) {
    return (
        <div className="border-b border-oz-black/10">
            <div
                className={
                    illustration
                        ? 'mx-auto grid max-w-7xl gap-6 px-4 pt-14 sm:px-6 sm:pt-20 md:grid-cols-[minmax(0,1fr)_auto] md:items-end md:gap-10'
                        : 'mx-auto max-w-7xl px-4 pt-14 pb-10 sm:px-6 sm:pt-20'
                }
            >
                <div className={illustration ? 'md:pb-10' : undefined}>
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
                {illustration && (
                    <div className="pointer-events-none self-end justify-self-end select-none">
                        {illustration}
                    </div>
                )}
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
