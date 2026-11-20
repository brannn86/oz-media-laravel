import { ChevronUp, LoaderCircle, Music } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { useLive } from '@/components/live/live-context';
import { useRequests } from '@/components/live/requests-context';
import { SignInPrompt } from '@/components/live/sign-in-prompt';
import { cn } from '@/lib/utils';
import type { SongRequest } from '@/types';

/**
 * "Request a Song" tab of the player drawer: the live queue, upvotes, and
 * a form for signed-in listeners.
 */
export function RequestsPanel() {
    const requests = useRequests();
    const { account } = useLive();
    const { ensureLoaded } = requests;

    useEffect(() => {
        ensureLoaded();
    }, [ensureLoaded]);

    return (
        <div className="flex-1 overflow-y-auto">
            {account ? (
                <RequestForm />
            ) : (
                <SignInPrompt
                    title="Request a song"
                    text="Log in to request songs and upvote other listeners' picks. Approved requests earn OZ Poin."
                    className="border-b border-oz-black/10 p-4"
                />
            )}
            <Queue />
        </div>
    );
}

const fieldClass =
    'h-11 w-full rounded-xl border-2 border-oz-black/15 bg-oz-white px-3 text-[0.95rem] outline-none focus:border-oz-black';

function RequestForm() {
    const { submit } = useRequests();
    const [title, setTitle] = useState('');
    const [artist, setArtist] = useState('');
    const [shoutout, setShoutout] = useState('');
    const [sending, setSending] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [sent, setSent] = useState(false);

    const onSubmit = async (event: FormEvent) => {
        event.preventDefault();
        setSending(true);
        const problem = await submit({
            song_title: title,
            artist,
            shoutout_message: shoutout,
        });
        setSending(false);
        setError(problem);
        setSent(!problem);

        if (!problem) {
            setTitle('');
            setArtist('');
            setShoutout('');
        }
    };

    return (
        <form
            onSubmit={onSubmit}
            className="space-y-2.5 border-b border-oz-black/10 bg-oz-paper p-4"
            aria-label="Request a song"
        >
            <div className="grid grid-cols-2 gap-2">
                <label className="block">
                    <span className="sr-only">Song title</span>
                    <input
                        value={title}
                        onChange={(e) => setTitle(e.target.value)}
                        placeholder="Song title"
                        maxLength={150}
                        required
                        className={fieldClass}
                    />
                </label>
                <label className="block">
                    <span className="sr-only">Artist</span>
                    <input
                        value={artist}
                        onChange={(e) => setArtist(e.target.value)}
                        placeholder="Artist"
                        maxLength={150}
                        required
                        className={fieldClass}
                    />
                </label>
            </div>
            <label className="block">
                <span className="sr-only">Shout-out (optional)</span>
                <input
                    value={shoutout}
                    onChange={(e) => setShoutout(e.target.value)}
                    placeholder="Shout-out to someone? (optional)"
                    maxLength={280}
                    className={fieldClass}
                />
            </label>
            {error && (
                <p role="alert" className="text-sm font-semibold text-oz-red">
                    {error}
                </p>
            )}
            {sent && !error && (
                <p role="status" className="text-sm font-semibold">
                    Request sent! Get friends to upvote it.
                </p>
            )}
            <button
                type="submit"
                disabled={sending}
                className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-full bg-oz-yellow font-bold text-oz-black transition-opacity focus-visible:ring-4 focus-visible:ring-oz-black/30 focus-visible:outline-none disabled:opacity-60"
            >
                <Music className="size-4" aria-hidden />
                {sending ? 'Sending…' : 'Request this song'}
            </button>
        </form>
    );
}

function Queue() {
    const { status, queue } = useRequests();

    if (status === 'loading' || status === 'idle') {
        return (
            <div className="grid place-items-center py-10">
                <LoaderCircle
                    className="size-6 motion-safe:animate-spin"
                    aria-label="Loading requests"
                />
            </div>
        );
    }

    if (status === 'error') {
        return (
            <p className="p-6 text-oz-ink">
                The request queue could not load. Close the drawer and open it
                again to retry.
            </p>
        );
    }

    if (queue.length === 0) {
        return (
            <p className="p-6 text-center text-oz-ink">
                No requests yet. Be the first!
            </p>
        );
    }

    return (
        <ol
            className="space-y-2 p-4"
            aria-label="Request queue"
            aria-live="polite"
        >
            {queue.map((request) => (
                <QueueItem key={request.id} request={request} />
            ))}
        </ol>
    );
}

function QueueItem({ request }: { request: SongRequest }) {
    const { myUpvotes, toggleUpvote } = useRequests();
    const { account } = useLive();
    const [error, setError] = useState<string | null>(null);

    const mine = account?.id === request.user.id;
    const upvoted = myUpvotes.has(request.id);
    const comingUp = request.status === 'approved';

    return (
        <li
            className={cn(
                'flex gap-3 rounded-2xl border p-3',
                comingUp
                    ? 'border-oz-yellow bg-oz-butter'
                    : 'border-oz-black/10',
            )}
        >
            <button
                type="button"
                disabled={!account || mine}
                onClick={async () => setError(await toggleUpvote(request.id))}
                aria-pressed={upvoted}
                aria-label={
                    mine
                        ? `${request.upvotes_count} upvotes on your request`
                        : upvoted
                          ? `Remove your upvote (${request.upvotes_count})`
                          : `Upvote (${request.upvotes_count})`
                }
                title={!account ? 'Log in to upvote' : undefined}
                className={cn(
                    'flex w-11 shrink-0 flex-col items-center justify-center rounded-xl border-2 py-1 text-sm font-extrabold tabular-nums transition-colors focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none disabled:cursor-default',
                    upvoted
                        ? 'border-oz-black bg-oz-black text-oz-yellow'
                        : 'border-oz-black/15 enabled:hover:border-oz-black',
                )}
            >
                <ChevronUp className="size-4" aria-hidden />
                {request.upvotes_count}
            </button>
            <div className="min-w-0 flex-1">
                <p className="leading-snug">
                    <span className="font-bold">{request.song_title}</span>
                    <span className="text-oz-ink"> by {request.artist}</span>
                </p>
                <p className="mt-0.5 text-xs text-oz-ink/70">
                    {comingUp ? (
                        <span className="font-bold text-oz-black">
                            Coming up!{' '}
                        </span>
                    ) : null}
                    Requested by {mine ? 'you' : request.user.name}
                </p>
                {request.shoutout_message && (
                    <p className="mt-1 text-sm break-words">
                        “{request.shoutout_message}”
                    </p>
                )}
                {error && (
                    <p
                        role="alert"
                        className="mt-1 text-xs font-semibold text-oz-red"
                    >
                        {error}
                    </p>
                )}
            </div>
        </li>
    );
}
