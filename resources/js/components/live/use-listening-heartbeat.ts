import { useEffect, useRef } from 'react';
import { toast } from 'sonner';
import { useLive } from '@/components/live/live-context';
import { apiFetch } from '@/lib/api';
import type { HeartbeatResponse } from '@/types';

const INTERVAL_MS = 60_000;

/**
 * Earns OZ Poin while the player is actually playing: one heartbeat per
 * minute of listening (the server ignores extras from other tabs/devices).
 * The first heartbeat comes after a full minute, so a quick tap of play earns nothing.
 */
export function useListeningHeartbeat(stationSlug: string, playing: boolean) {
    const { account, updatePoints } = useLive();
    const signedIn = account !== null;
    const capNotified = useRef(false);

    useEffect(() => {
        if (!playing || !signedIn) {
            return;
        }

        const beat = async () => {
            try {
                const result = await apiFetch<HeartbeatResponse>(
                    `/api/v1/stations/${stationSlug}/heartbeat`,
                    { method: 'POST' },
                );

                if (!result.ok || !result.data.accepted) {
                    return;
                }

                const data = result.data;
                updatePoints(data.points_balance, data.streak_days);

                if (data.streak_bonus > 0) {
                    toast.success(
                        data.streak_days > 1
                            ? `Day ${data.streak_days} listening streak! +${data.streak_bonus} OZ Poin`
                            : `+${data.streak_bonus} OZ Poin for tuning in today`,
                    );
                }

                if (data.daily_cap_reached && !capNotified.current) {
                    capNotified.current = true;
                    toast(
                        "You've earned today's listening points. Come back tomorrow for more!",
                    );
                }
            } catch {
                // Offline for a moment: the next minute will try again.
            }
        };

        const timer = window.setInterval(beat, INTERVAL_MS);

        return () => window.clearInterval(timer);
    }, [stationSlug, playing, signedIn, updatePoints]);
}
