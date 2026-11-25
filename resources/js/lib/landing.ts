/**
 * Where this visit came from: the referring site (only another site, not
 * ours) and the UTM tags of the page the visitor landed on.
 *
 * Read once, when the app first loads (before in-app navigation changes the
 * URL), and kept for the tab's visit, so a listener who browses a few pages
 * before pressing play is still credited to the post that brought them.
 */

export type Landing = {
    referrer_host: string | null;
    utm_source: string | null;
    utm_medium: string | null;
    utm_campaign: string | null;
};

const KEY = 'oz-landing';

let current: Landing | null = null;

function read(): Landing {
    const params = new URLSearchParams(window.location.search);
    let referrerHost: string | null = null;

    try {
        if (document.referrer) {
            const host = new URL(document.referrer).hostname;
            referrerHost =
                host && host !== window.location.hostname ? host : null;
        }
    } catch {
        referrerHost = null;
    }

    return {
        referrer_host: referrerHost,
        utm_source: params.get('utm_source'),
        utm_medium: params.get('utm_medium'),
        utm_campaign: params.get('utm_campaign'),
    };
}

export function landing(): Landing {
    if (current) {
        return current;
    }

    try {
        const stored = window.sessionStorage.getItem(KEY);

        if (stored) {
            current = JSON.parse(stored) as Landing;

            return current;
        }
    } catch {
        // Storage blocked: this page load is the landing.
    }

    current = read();

    try {
        window.sessionStorage.setItem(KEY, JSON.stringify(current));
    } catch {
        // Not remembered across reloads; fine.
    }

    return current;
}

// Capture at load, while the address still shows the landing page.
if (typeof window !== 'undefined') {
    landing();
}
