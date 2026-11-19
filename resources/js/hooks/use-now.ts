import { useEffect, useState } from 'react';

/**
 * Current time, re-rendering every `intervalMs` (for progress bars and "on air" marks).
 *
 * Starts from `initial` (e.g. the server's timestamp) so server-rendered and
 * hydrated markup match, then switches to the real clock after mount.
 */
export function useNow(initial: number, intervalMs = 30_000): number {
    const [now, setNow] = useState(initial);

    useEffect(() => {
        const tick = () => setNow(Date.now());
        const first = window.setTimeout(tick, 0);
        const timer = window.setInterval(tick, intervalMs);

        return () => {
            window.clearTimeout(first);
            window.clearInterval(timer);
        };
    }, [intervalMs]);

    return now;
}
