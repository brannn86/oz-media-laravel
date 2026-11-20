import { LoaderCircle, Pin, SendHorizontal, Timer } from 'lucide-react';
import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { useChat } from '@/components/chat/chat-context';
import { SignInPrompt } from '@/components/live/sign-in-prompt';
import { cn } from '@/lib/utils';
import { formatTime } from '@/lib/wib';

import type { ChatMessage } from '@/types';

/**
 * The live chat tab of the player drawer (see LiveDrawer).
 */
export function ChatPanel() {
    const chat = useChat();
    const { ensureLoaded } = chat;

    useEffect(() => {
        ensureLoaded();
    }, [ensureLoaded]);

    return (
        <>
            {chat.settings.slow_mode_seconds > 0 && (
                <p className="flex items-center gap-1 border-b border-oz-black/10 px-4 py-2 text-xs font-semibold text-oz-ink/70">
                    <Timer className="size-3.5" aria-hidden />
                    Slow mode: one message every{' '}
                    {chat.settings.slow_mode_seconds}s
                </p>
            )}

            {chat.pinned && (
                <div className="flex gap-2 border-b border-oz-black/10 bg-oz-butter px-4 py-3">
                    <Pin
                        className="mt-0.5 size-4 shrink-0 fill-current"
                        aria-label="Pinned"
                    />
                    <p className="min-w-0 text-sm">
                        <span className="font-bold">
                            {chat.pinned.user.name}:{' '}
                        </span>
                        {chat.pinned.body}
                    </p>
                </div>
            )}

            <MessageList />
            <Composer />
        </>
    );
}

function MessageList() {
    const { messages, status, me } = useChat();
    const listRef = useRef<HTMLOListElement>(null);
    const stickRef = useRef(true);

    // Follow new messages unless the listener has scrolled up to read.
    useLayoutEffect(() => {
        const list = listRef.current;

        if (list && stickRef.current) {
            list.scrollTop = list.scrollHeight;
        }
    }, [messages]);

    if (status === 'loading' || status === 'idle') {
        return (
            <div className="grid flex-1 place-items-center">
                <LoaderCircle
                    className="size-6 motion-safe:animate-spin"
                    aria-label="Loading chat"
                />
            </div>
        );
    }

    if (status === 'error') {
        return (
            <p className="flex-1 p-6 text-oz-ink">
                The chat could not load. Close it and open it again to retry.
            </p>
        );
    }

    return (
        <ol
            ref={listRef}
            onScroll={(event) => {
                const list = event.currentTarget;
                stickRef.current =
                    list.scrollTop + list.clientHeight >=
                    list.scrollHeight - 48;
            }}
            role="log"
            aria-live="polite"
            className="flex-1 space-y-3 overflow-y-auto px-4 py-4"
        >
            {messages.length === 0 && (
                <li className="py-10 text-center text-oz-ink">
                    No messages yet. Say hi to the studio!
                </li>
            )}
            {messages.map((message) => (
                <MessageItem
                    key={message.id}
                    message={message}
                    mine={message.user.id === me?.id}
                />
            ))}
        </ol>
    );
}

function MessageItem({
    message,
    mine,
}: {
    message: ChatMessage;
    mine: boolean;
}) {
    return (
        <li className="flex gap-2.5">
            {message.user.avatar_url ? (
                <img
                    src={message.user.avatar_url}
                    alt=""
                    className="size-8 shrink-0 rounded-full object-cover"
                    referrerPolicy="no-referrer"
                />
            ) : (
                <span
                    aria-hidden
                    className={cn(
                        'grid size-8 shrink-0 place-items-center rounded-full text-sm font-bold',
                        message.user.is_staff
                            ? 'bg-oz-yellow text-oz-black'
                            : 'bg-oz-black text-oz-yellow',
                    )}
                >
                    {message.user.name.slice(0, 1).toUpperCase()}
                </span>
            )}
            <div className="min-w-0">
                <p className="flex flex-wrap items-center gap-x-1.5 text-sm">
                    <span className="font-bold">
                        {mine ? 'You' : message.user.name}
                    </span>
                    {message.user.is_staff && (
                        <span className="rounded bg-oz-yellow px-1.5 text-[0.7rem] font-extrabold">
                            OZ
                        </span>
                    )}
                    <time
                        dateTime={message.created_at}
                        className="text-xs text-oz-ink/60"
                    >
                        {formatTime(message.created_at)}
                    </time>
                </p>
                <p className="text-[0.95rem] leading-snug break-words">
                    {message.body}
                </p>
            </div>
        </li>
    );
}

function Composer() {
    const chat = useChat();

    const [body, setBody] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [sending, setSending] = useState(false);
    const remaining = useCountdown(chat.cooldownUntil);

    if (chat.status !== 'ready') {
        return null;
    }

    if (!chat.me) {
        return (
            <SignInPrompt
                title="Join the conversation"
                text="Log in to send messages. Reading is open to everyone."
                className="border-t border-oz-black/10 p-4"
            />
        );
    }

    const mutedUntil = chat.me.chat_muted_until;

    if (mutedUntil && Date.parse(mutedUntil) > Date.now()) {
        return (
            <p className="border-t border-oz-black/10 p-4 text-sm text-oz-ink">
                You are muted in chat until {formatTime(mutedUntil)} WIB.
            </p>
        );
    }

    const submit = async (event: FormEvent) => {
        event.preventDefault();

        const text = body.trim();

        if (!text || sending || remaining > 0) {
            return;
        }

        setSending(true);
        const problem = await chat.send(text);
        setSending(false);
        setError(problem);

        if (!problem) {
            setBody('');
        }
    };

    return (
        <form
            onSubmit={submit}
            className="border-t border-oz-black/10 p-3"
            aria-label="Send a message"
        >
            {error && (
                <p
                    role="alert"
                    className="mb-2 text-sm font-semibold text-oz-red"
                >
                    {error}
                </p>
            )}
            <div className="flex items-end gap-2">
                <label htmlFor="chat-body" className="sr-only">
                    Message
                </label>
                <input
                    id="chat-body"
                    value={body}
                    onChange={(event) => setBody(event.target.value)}
                    maxLength={chat.settings.max_length}
                    placeholder={`Chat as ${chat.me.name}`}
                    autoComplete="off"
                    className="h-11 min-w-0 flex-1 rounded-full border-2 border-oz-black/15 bg-oz-white px-4 text-[0.95rem] outline-none focus:border-oz-black"
                />
                <button
                    type="submit"
                    disabled={sending || remaining > 0 || !body.trim()}
                    className="grid h-11 min-w-11 place-items-center rounded-full bg-oz-yellow px-3 font-bold text-oz-black transition-opacity focus-visible:ring-4 focus-visible:ring-oz-black/30 focus-visible:outline-none disabled:opacity-50"
                    aria-label={
                        remaining > 0 ? `Wait ${remaining} seconds` : 'Send'
                    }
                >
                    {remaining > 0 ? (
                        <span className="text-sm tabular-nums">
                            {remaining}s
                        </span>
                    ) : (
                        <SendHorizontal className="size-5" />
                    )}
                </button>
            </div>
        </form>
    );
}

/** Whole seconds left until `until` (epoch ms), ticking every 250 ms. */
function useCountdown(until: number): number {
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        const tick = () => {
            setNow(Date.now());

            if (Date.now() >= until) {
                window.clearInterval(timer);
            }
        };
        // Sync immediately so a fresh cooldown never shows a stale number.
        const first = window.setTimeout(tick, 0);
        const timer = window.setInterval(tick, 250);

        return () => {
            window.clearTimeout(first);
            window.clearInterval(timer);
        };
    }, [until]);

    return Math.max(0, Math.ceil((until - now) / 1000));
}
