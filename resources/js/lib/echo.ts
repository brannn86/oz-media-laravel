import { configureEcho } from '@laravel/echo-react';
import type { ChannelAuthorizationCallback } from 'pusher-js';
import { apiFetch } from '@/lib/api';

type AuthData = Parameters<ChannelAuthorizationCallback>[1];

/**
 * presence-station.{slug}.listeners -> {slug}, or null for any other channel.
 * That channel counts listeners and also accepts guests, so it has its own
 * auth endpoint; everything else uses Laravel's /broadcasting/auth.
 */
function listenerStation(channelName: string): string | null {
    return (
        channelName.match(/^presence-station\.([a-z0-9-]+)\.listeners$/)?.[1] ??
        null
    );
}

export function setupEcho(): void {
    configureEcho({
        broadcaster: 'reverb',
        authorizer: (channel: { name: string }) => ({
            authorize: (
                socketId: string,
                callback: ChannelAuthorizationCallback,
            ) => {
                const slug = listenerStation(channel.name);
                const path = slug
                    ? `/api/v1/stations/${slug}/listeners/auth`
                    : '/broadcasting/auth';

                apiFetch<AuthData>(path, {
                    method: 'POST',
                    body: { socket_id: socketId, channel_name: channel.name },
                })
                    .then((result) =>
                        result.ok
                            ? callback(null, result.data)
                            : callback(
                                  new Error(`Auth failed (${result.status})`),
                                  null,
                              ),
                    )
                    .catch((error: unknown) =>
                        callback(
                            error instanceof Error
                                ? error
                                : new Error('Auth failed'),
                            null,
                        ),
                    );
            },
        }),
    });
}
