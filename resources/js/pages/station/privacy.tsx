import { usePage } from '@inertiajs/react';
import { ContentPage } from '@/components/station/content-page';
import type { StationPageProps } from '@/types';

type Props = StationPageProps & {
    measurement: {
        retentionMonths: number;
        webAnalytics: boolean;
    };
};

export default function StationPrivacy() {
    return (
        <ContentPage
            heading="Privacy policy"
            emptyText="The privacy policy will be published here."
        >
            <WhatWeMeasure />
        </ContentPage>
    );
}

/**
 * What the site itself records about visitors. Kept in code rather than the
 * CMS so it changes whenever the tracking does.
 */
function WhatWeMeasure() {
    const { station, measurement } = usePage<Props>().props;
    const email = station.contact.email;

    return (
        <section
            aria-labelledby="what-we-measure"
            className="oz-prose mt-16 max-w-[68ch] border-t border-oz-black/10 pt-10"
        >
            <h2 id="what-we-measure">What this site measures</h2>
            <p>
                To run the station's site and player and to see what our
                audience listens to, watches and reads, we record the following.
                We don't sell it, use it for advertising or share it with anyone
                other than the providers that host the site for us.
            </p>

            <h3>Listening and watching</h3>
            <p>
                While the player is playing, it tells our server once a minute.
                For each listening or viewing session we keep the station, radio
                or video, when it started and ended, how many minutes it lasted,
                and the kind of device, operating system and browser (read from
                what your browser reports about itself).
            </p>

            <h3>A random ID for your browser</h3>
            <p>
                To count unique listeners, the player keeps a random ID in your
                browser's local storage (<code>oz-visitor-id</code>). It isn't
                linked to your name. If you are signed in, your account is used
                instead. Our server stores only a one-way hash of either, never
                the ID itself. Clearing this site's data in your browser removes
                the ID.
            </p>

            <h3>How you found us</h3>
            <p>
                The name of the website that sent you here (not the full address
                of the page) and any campaign tags in the link you followed (
                <code>utm_source</code>, <code>utm_medium</code>,{' '}
                <code>utm_campaign</code>). They are kept in your browser for
                the visit and saved with your listening session.
            </p>

            <h3>Reading and reactions</h3>
            <p>
                How far down a news article readers scroll and how long it is
                open, and how many people react to a post. These are counted as
                totals per article and day, not per person.
            </p>

            <h3>The radio stream</h3>
            <p>
                Like any server, our streaming server sees the IP address and
                player of each listener while they are connected. We keep only
                listener counts and the kinds of players used, not addresses.
            </p>

            {measurement.webAnalytics && (
                <>
                    <h3>Page visits</h3>
                    <p>
                        We count page visits with Umami, a privacy-focused
                        analytics tool that we host ourselves. It does not use
                        cookies or follow you across other sites.
                    </p>
                </>
            )}

            <h3>How long we keep it</h3>
            <p>
                Listening sessions and the daily records of unique listeners are
                deleted automatically after {measurement.retentionMonths}{' '}
                months. Totals with nothing that identifies a person (such as
                listeners per minute) are kept for comparing years.
            </p>

            <h3>Your rights</h3>
            <p>
                Under Indonesia's Personal Data Protection Law (UU No. 27 Tahun
                2022) you can ask what personal data we hold about you, have it
                corrected or deleted, and withdraw your consent.
                {email ? (
                    <>
                        {' '}
                        Email us at <a href={`mailto:${email}`}>{email}</a>.
                    </>
                ) : (
                    ' Contact us using the details on our About page.'
                )}
            </p>
        </section>
    );
}
