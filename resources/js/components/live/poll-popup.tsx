import { useEchoPublic } from '@laravel/echo-react';
import { Check, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useLive } from '@/components/live/live-context';
import { SignInPrompt } from '@/components/live/sign-in-prompt';
import { apiFetch, errorMessage } from '@/lib/api';
import { cn } from '@/lib/utils';
import type { Poll, Station } from '@/types';

/** How long final results stay up after the studio closes a poll. */
const CLOSED_VISIBLE_MS = 20_000;

type CurrentPollResponse = { data: Poll | null; my_vote: number | null };

/**
 * Live poll card that pops up above the player when the studio launches a
 * poll, with results that move in real time (Reverb `station.{slug}.polls`).
 */
export function PollPopup({ station }: { station: Station }) {
    const { account, updatePoints } = useLive();
    const [poll, setPoll] = useState<Poll | null>(null);
    const [myVote, setMyVote] = useState<number | null>(null);
    const [dismissed, setDismissed] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [voting, setVoting] = useState(false);

    // Pick up a poll that is already live when the visitor arrives.
    useEffect(() => {
        let cancelled = false;

        apiFetch<CurrentPollResponse>(
            `/api/v1/stations/${station.slug}/polls/current`,
        )
            .then((result) => {
                if (!cancelled && result.ok && result.data.data) {
                    setPoll(result.data.data);
                    setMyVote(result.data.my_vote);
                }
            })
            .catch(() => undefined);

        return () => {
            cancelled = true;
        };
    }, [station.slug, account?.id]);

    useEchoPublic<{ poll: Poll }>(
        `station.${station.slug}.polls`,
        '.poll.updated',
        ({ poll: next }) => {
            // A new poll: forget the vote and error from the previous one.
            if (poll?.id !== next.id) {
                setMyVote(null);
                setError(null);
            }

            setPoll(next);
        },
        [poll?.id],
    );

    // Results of a closed poll stay up for a little while, then the card goes.
    useEffect(() => {
        if (poll?.status !== 'closed') {
            return;
        }

        const timer = window.setTimeout(
            () => setDismissed(poll.id),
            CLOSED_VISIBLE_MS,
        );

        return () => window.clearTimeout(timer);
    }, [poll?.id, poll?.status]);

    if (!poll || dismissed === poll.id) {
        return null;
    }

    const closed = poll.status === 'closed';
    const showResults = closed || myVote !== null;

    const vote = async (optionId: number) => {
        setVoting(true);
        const result = await apiFetch<{
            data: Poll;
            my_vote: number;
            points_balance: number;
        }>(`/api/v1/stations/${station.slug}/polls/${poll.id}/votes`, {
            method: 'POST',
            body: { option_id: optionId },
        });
        setVoting(false);

        if (result.ok) {
            setPoll(result.data.data);
            setMyVote(result.data.my_vote);
            updatePoints(result.data.points_balance);
            setError(null);
        } else {
            setError(
                errorMessage(
                    result.data,
                    'Your vote did not count. Try again.',
                ),
            );
        }
    };

    return (
        <section
            aria-label={`${poll.type_label}: ${poll.question}`}
            className="fixed bottom-[calc(var(--oz-player-bar)+0.75rem)] left-3 z-40 w-[min(24rem,calc(100vw-1.5rem))] rounded-3xl bg-oz-black p-5 font-oz text-oz-white shadow-2xl motion-safe:animate-in motion-safe:slide-in-from-bottom-4 sm:left-6"
        >
            <div className="flex items-start justify-between gap-3">
                <p className="inline-flex items-center gap-2 text-sm font-bold text-oz-yellow">
                    {!closed && (
                        <span className="size-2 rounded-full bg-oz-red motion-safe:animate-pulse" />
                    )}
                    {poll.type_label}
                    {closed ? ': final results' : ': live now'}
                </p>
                <button
                    type="button"
                    onClick={() => setDismissed(poll.id)}
                    aria-label="Hide poll"
                    className="-mt-1 -mr-1 grid size-8 place-items-center rounded-full text-oz-white/70 hover:bg-oz-white/10 hover:text-oz-white focus-visible:ring-2 focus-visible:ring-oz-yellow focus-visible:outline-none"
                >
                    <X className="size-4" />
                </button>
            </div>
            <h2 className="mt-2 font-oz-display text-xl leading-tight font-extrabold">
                {poll.question}
            </h2>

            <ul className="mt-4 space-y-2">
                {poll.options.map((option) => {
                    const chosen = myVote === option.id;
                    const correct = option.is_correct === true;

                    return (
                        <li key={option.id}>
                            {showResults ? (
                                <div
                                    className={cn(
                                        'relative overflow-hidden rounded-xl border-2 px-3 py-2',
                                        correct
                                            ? 'border-oz-yellow'
                                            : chosen
                                              ? 'border-oz-white/60'
                                              : 'border-oz-white/15',
                                    )}
                                >
                                    <div
                                        aria-hidden
                                        className={cn(
                                            'absolute inset-y-0 left-0 transition-[width] duration-700 ease-out',
                                            correct
                                                ? 'bg-oz-yellow/35'
                                                : 'bg-oz-white/15',
                                        )}
                                        style={{ width: `${option.percent}%` }}
                                    />
                                    <div className="relative flex justify-between gap-3 text-sm font-semibold">
                                        <span className="flex items-center gap-1.5">
                                            {(chosen || correct) && (
                                                <Check
                                                    className="size-4"
                                                    aria-label={
                                                        correct
                                                            ? 'Correct answer'
                                                            : 'Your vote'
                                                    }
                                                />
                                            )}
                                            {option.label}
                                        </span>
                                        <span className="tabular-nums">
                                            {option.percent}%
                                        </span>
                                    </div>
                                </div>
                            ) : (
                                <button
                                    type="button"
                                    disabled={voting || !account}
                                    onClick={() => vote(option.id)}
                                    className="w-full rounded-xl border-2 border-oz-white/20 px-3 py-2.5 text-left text-sm font-bold transition-colors focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none enabled:hover:border-oz-yellow enabled:hover:bg-oz-yellow enabled:hover:text-oz-black disabled:opacity-70"
                                >
                                    {option.label}
                                </button>
                            )}
                        </li>
                    );
                })}
            </ul>

            <p className="mt-3 text-xs text-oz-white/60" aria-live="polite">
                {poll.total_votes.toLocaleString()}{' '}
                {poll.total_votes === 1 ? 'vote' : 'votes'}
                {myVote !== null && !closed && ': thanks for voting!'}
            </p>
            {error && (
                <p
                    role="alert"
                    className="mt-2 text-sm font-semibold text-oz-yellow"
                >
                    {error}
                </p>
            )}
            {!account && !closed && (
                <SignInPrompt
                    title="Vote and earn OZ Poin"
                    text="Log in to vote. Results are open to everyone."
                    className="mt-4 rounded-2xl bg-oz-white p-4 text-oz-black"
                />
            )}
        </section>
    );
}
