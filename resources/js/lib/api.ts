import { echo } from '@laravel/echo-react';

/**
 * Small JSON client for /api/v1 from the website.
 *
 * The website authenticates with its session cookie (Sanctum stateful API),
 * so writes must carry the XSRF token Laravel sets as a cookie.
 */

export type ApiResult<T> = {
    ok: boolean;
    status: number;
    data: T;
    headers: Headers;
};

function currentSocketId(): string | undefined {
    try {
        return echo().socketId();
    } catch {
        return undefined;
    }
}

function xsrfToken(): string | null {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : null;
}

export async function apiFetch<T = unknown>(
    path: string,
    options: { method?: string; body?: unknown; headers?: HeadersInit } = {},
): Promise<ApiResult<T>> {
    const headers = new Headers(options.headers);
    headers.set('Accept', 'application/json');
    headers.set('X-Requested-With', 'XMLHttpRequest');

    if (options.body !== undefined) {
        headers.set('Content-Type', 'application/json');
    }

    const token = xsrfToken();

    if (token) {
        headers.set('X-XSRF-TOKEN', token);
    }

    // Lets the server skip echoing a broadcast back to this tab (e.g. reactions).
    const socketId = currentSocketId();

    if (socketId) {
        headers.set('X-Socket-ID', socketId);
    }

    const response = await fetch(path, {
        method: options.method ?? 'GET',
        headers,
        credentials: 'same-origin',
        body:
            options.body === undefined
                ? undefined
                : JSON.stringify(options.body),
    });

    const data = (await response.json().catch(() => null)) as T;

    return {
        ok: response.ok,
        status: response.status,
        data,
        headers: response.headers,
    };
}

/** First validation message from a 422 response, or the plain message. */
export function errorMessage(data: unknown, fallback: string): string {
    if (data && typeof data === 'object') {
        const body = data as {
            message?: string;
            errors?: Record<string, string[]>;
        };
        const first = body.errors && Object.values(body.errors)[0]?.[0];

        return first ?? body.message ?? fallback;
    }

    return fallback;
}
