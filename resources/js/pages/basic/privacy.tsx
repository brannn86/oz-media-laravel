import { Head, usePage } from '@inertiajs/react';
import type { StationPageProps } from '@/types';

type Props = StationPageProps & { body: string | null };

/** A basic station's privacy policy (the text from Station settings). */
export default function BasicStationPrivacy() {
    const { meta, body } = usePage<Props>().props;

    return (
        <>
            <Head title={meta.title} />
            <div className="mx-auto max-w-5xl px-4 pt-8 pb-16 sm:px-6 sm:pt-12">
                <h1 className="font-oz-display text-4xl leading-none font-extrabold tracking-tight sm:text-6xl">
                    Privacy policy
                </h1>
                {body ? (
                    // Sanitized server-side. Light card: the prose styles are for light backgrounds.
                    <div
                        className="oz-prose mt-8 max-w-[68ch] rounded-3xl bg-oz-white p-6 sm:p-10"
                        dangerouslySetInnerHTML={{ __html: body }}
                    />
                ) : (
                    <p className="mt-8 text-lg text-oz-white/70">
                        The privacy policy will be published here.
                    </p>
                )}
            </div>
        </>
    );
}
