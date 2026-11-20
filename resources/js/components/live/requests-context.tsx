import { router } from '@inertiajs/react';
import { useEchoPublic } from '@laravel/echo-react';
import { toast } from 'sonner';
import { useLive } from '@/components/live/live-context';
import { createContext, useCallback, useContext, useState } from 'react';
import type { ReactNode } from 'react';
import { apiFetch, errorMessage } from '@/lib/api';
import type { SongRequest, Station } from '@/types';

type LoadStatus = 'idle' | 'loading' | 'ready' | 'error';

export type RequestInput = {
    song_title: string;
    artist: string;
    shoutout_message: string;
};

type RequestsApi = {
    status: LoadStatus;
    queue: SongRequest[];
    myUpvotes: Set<number>;
    ensureLoaded: () => void;
    /** Resolves with an error message, or null on success. */
    submit: (input: RequestInput) => Promise<string | null>;
    toggleUpvote: (id: number) => Promise<string | null>;
};

type QueueResponse = {
    data: SongRequest[];
    my_upvotes: number[];
};

const RequestsContext = createContext<RequestsApi | null>(null);

const isOpen = (request: SongRequest) =>
    request.status === 'pending' || request.status === 'approved';

/** Same order as the server: coming up first, then most upvoted, then oldest. */
function sortQueue(queue: SongRequest[]): SongRequest[] {
    return [...queue].sort(
        (a, b) =>
            Number(b.status === 'approved') - Number(a.status === 'approved') ||
            b.upvotes_count - a.upvotes_count ||
            a.id - b.id,
    );
}

/**
 * The live song request queue for a station, kept current over Reverb
 * (`station.{slug}.requests`).
 */
export function RequestsProvider({
    station,
    children,
}: {
    station: Station;
    children: ReactNode;
}) {
    const [status, setStatus] = useState<LoadStatus>('idle');
    const [queue, setQueue] = useState<SongRequest[]>([]);
    const [myUpvotes, setMyUpvotes] = useState<Set<number>>(new Set());
    const { account } = useLive();
    const [accountId, setAccountId] = useState(account?.id ?? null);

    const base = `/api/v1/stations/${station.slug}/requests`;

    // Your upvotes belong to the session: reload after logging in or out.
    if (accountId !== (account?.id ?? null)) {
        setAccountId(account?.id ?? null);
        setMyUpvotes(new Set());
        setStatus('idle');
    }

    const upsert = useCallback((request: SongRequest) => {
        setQueue((current) => {
            const others = current.filter((r) => r.id !== request.id);

            return sortQueue(isOpen(request) ? [...others, request] : others);
        });
    }, []);

    const ensureLoaded = useCallback(() => {
        if (status !== 'idle' && status !== 'error') {
            return;
        }

        setStatus('loading');

        apiFetch<QueueResponse>(base)
            .then((result) => {
                if (!result.ok) {
                    setStatus('error');

                    return;
                }

                setQueue(result.data.data);
                setMyUpvotes(new Set(result.data.my_upvotes));
                setStatus('ready');
            })
            .catch(() => setStatus('error'));
    }, [base, status]);

    useEchoPublic<{ request: SongRequest }>(
        `station.${station.slug}.requests`,
        '.song-request.updated',
        ({ request }) => {
            upsert(request);

            // Your request was approved: that just paid you OZ Poin.
            if (
                request.status === 'approved' &&
                request.user.id === account?.id
            ) {
                toast.success(
                    `"${request.song_title}" is coming up! Points added.`,
                );
                router.reload({ only: ['account'] });
            }
        },
        [account?.id],
    );

    const submit = useCallback(
        async (input: RequestInput): Promise<string | null> => {
            const result = await apiFetch<{ data: SongRequest }>(base, {
                method: 'POST',
                body: input,
            });

            if (result.ok) {
                upsert(result.data.data);

                return null;
            }

            return errorMessage(
                result.data,
                'Your request did not go through. Try again.',
            );
        },
        [base, upsert],
    );

    const toggleUpvote = useCallback(
        async (id: number): Promise<string | null> => {
            const result = await apiFetch<{
                data: SongRequest;
                upvoted: boolean;
            }>(`${base}/${id}/upvote`, { method: 'POST' });

            if (!result.ok) {
                return errorMessage(
                    result.data,
                    'That did not work. Try again.',
                );
            }

            upsert(result.data.data);
            setMyUpvotes((current) => {
                const next = new Set(current);

                if (result.data.upvoted) {
                    next.add(id);
                } else {
                    next.delete(id);
                }

                return next;
            });

            return null;
        },
        [base, upsert],
    );

    return (
        <RequestsContext.Provider
            value={{
                status,
                queue,
                myUpvotes,
                ensureLoaded,
                submit,
                toggleUpvote,
            }}
        >
            {children}
        </RequestsContext.Provider>
    );
}

export function useRequests(): RequestsApi {
    const requests = useContext(RequestsContext);

    if (!requests) {
        throw new Error(
            'useRequests() must be used inside <RequestsProvider>.',
        );
    }

    return requests;
}
