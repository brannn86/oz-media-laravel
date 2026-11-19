import { Head, usePage } from '@inertiajs/react';
import type { StationPageProps } from '@/types';

type Props = StationPageProps & {
    title: string;
    body: string | null;
};

/**
 * CMS-managed text page (About, Privacy Policy). The body is sanitized server-side.
 */
export function ContentPage({
    heading,
    emptyText,
}: {
    heading: string;
    emptyText: string;
}) {
    const { meta, body } = usePage<Props>().props;

    return (
        <>
            <Head title={meta.title} />
            <div className="mx-auto max-w-4xl px-4 pt-14 pb-20 sm:px-6 sm:pt-20">
                <h1 className="font-oz-display text-5xl leading-[0.95] font-extrabold tracking-tight sm:text-7xl">
                    {heading}
                </h1>
                {body ? (
                    <div
                        className="oz-prose mt-10 max-w-[68ch]"
                        dangerouslySetInnerHTML={{ __html: body }}
                    />
                ) : (
                    <p className="mt-10 text-lg text-oz-ink">{emptyText}</p>
                )}
            </div>
        </>
    );
}
