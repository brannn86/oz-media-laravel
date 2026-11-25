import { useEffect } from 'react';
import type { RefObject } from 'react';
import { apiFetch } from '@/lib/api';

/** Stop counting reading time after this long without scrolling, touch, mouse or keys. */
const IDLE_MS = 60_000;

/**
 * Measures how an article is read, for Analytics > News: the furthest point
 * of the article text that came into view (percent), and seconds of active
 * reading (tab visible, someone scrolling or moving in the last minute).
 * Sent once, when the reader leaves: another page, the tab hidden or closed.
 */
export function useReadingDepth(
    article: RefObject<HTMLElement | null>,
    stationSlug: string,
    postSlug: string,
) {
    useEffect(() => {
        const element = article.current;

        if (!element) {
            return;
        }

        let depth = 0;
        let seconds = 0;
        let lastActive = Date.now();
        let sent = false;

        const measure = () => {
            const box = element.getBoundingClientRect();

            if (box.height <= 0) {
                return;
            }

            const seen = Math.min(
                box.height,
                Math.max(0, window.innerHeight - box.top),
            );
            depth = Math.max(depth, Math.round((seen / box.height) * 100));
        };

        const active = () => {
            lastActive = Date.now();
            measure();
        };

        const tick = window.setInterval(() => {
            if (
                document.visibilityState === 'visible' &&
                Date.now() - lastActive < IDLE_MS
            ) {
                seconds += 1;
            }
        }, 1000);

        const send = () => {
            if (sent) {
                return;
            }

            sent = true;
            measure();

            apiFetch(`/api/v1/stations/${stationSlug}/news/${postSlug}/read`, {
                method: 'POST',
                body: { depth, seconds },
                keepalive: true,
            }).catch(() => undefined);
        };

        const onVisibility = () => {
            if (document.visibilityState === 'hidden') {
                send();
            }
        };

        const activity = ['scroll', 'pointermove', 'keydown', 'touchstart'];

        measure();
        activity.forEach((type) =>
            window.addEventListener(type, active, { passive: true }),
        );
        document.addEventListener('visibilitychange', onVisibility);
        window.addEventListener('pagehide', send);

        return () => {
            send(); // Navigating to another page inside the site.
            window.clearInterval(tick);
            activity.forEach((type) =>
                window.removeEventListener(type, active),
            );
            document.removeEventListener('visibilitychange', onVisibility);
            window.removeEventListener('pagehide', send);
        };
    }, [article, stationSlug, postSlug]);
}
