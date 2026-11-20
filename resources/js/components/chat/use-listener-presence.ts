import { echo } from '@laravel/echo-react';
import { useEffect } from 'react';

/**
 * Joins the station's listener presence channel while the player is on, so
 * Studio Mode can count live listeners. Guests count too (no account needed).
 */
export function useListenerPresence(stationSlug: string, listening: boolean) {
    useEffect(() => {
        if (!listening) {
            return;
        }

        const channel = `station.${stationSlug}.listeners`;

        try {
            echo().join(channel);
        } catch {
            // Realtime unavailable: listening works fine without being counted.
            return;
        }

        return () => {
            try {
                echo().leave(channel);
            } catch {
                // Already disconnected.
            }
        };
    }, [stationSlug, listening]);
}
