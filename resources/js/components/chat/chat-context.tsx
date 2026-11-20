import { useEchoPublic } from '@laravel/echo-react';
import { createContext, useCallback, useContext, useState } from 'react';
import type { ReactNode } from 'react';
import { apiFetch, errorMessage } from '@/lib/api';
import type {
    Account,
    ChatMessage,
    ChatSettings,
    SharedChatProps,
    Station,
} from '@/types';

type LoadStatus = 'idle' | 'loading' | 'ready' | 'error';

type ChatApi = {
    open: boolean;
    setOpen: (open: boolean) => void;
    status: LoadStatus;
    messages: ChatMessage[];
    pinned: ChatMessage | null;
    settings: ChatSettings;
    me: Account | null;
    googleSignIn: boolean;
    unread: number;
    /** Epoch ms until which this listener has to wait before sending again. */
    cooldownUntil: number;
    send: (body: string) => Promise<string | null>;
};

type HistoryResponse = {
    data: ChatMessage[];
    pinned: ChatMessage | null;
    settings: ChatSettings;
    me: Account | null;
};

/** Keep the DOM light on long shows. */
const MAX_MESSAGES = 200;

const ChatContext = createContext<ChatApi | null>(null);

/**
 * Live chat state for a station. Lives in StationLayout, so the conversation
 * (and its Reverb subscription) carries on while visitors browse the site.
 * Talks to the same /api/v1 endpoints as the mobile app.
 */
export function ChatProvider({
    station,
    chat,
    children,
}: {
    station: Station;
    chat: SharedChatProps;
    children: ReactNode;
}) {
    const [open, setOpenState] = useState(false);
    const [status, setStatus] = useState<LoadStatus>('idle');
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [pinned, setPinned] = useState<ChatMessage | null>(null);
    const [settings, setSettings] = useState(chat.settings);
    const [me, setMe] = useState<Account | null>(null);
    const [unread, setUnread] = useState(0);
    const [cooldownUntil, setCooldownUntil] = useState(0);

    const base = `/api/v1/stations/${station.slug}/chat`;

    const load = useCallback(async () => {
        setStatus('loading');

        try {
            const result = await apiFetch<HistoryResponse>(base);

            if (!result.ok) {
                setStatus('error');

                return;
            }

            setMessages(result.data.data);
            setPinned(result.data.pinned);
            setSettings(result.data.settings);
            setMe(result.data.me);
            setStatus('ready');
        } catch {
            setStatus('error');
        }
    }, [base]);

    const setOpen = useCallback(
        (next: boolean) => {
            setOpenState(next);

            if (next) {
                setUnread(0);

                if (status === 'idle' || status === 'error') {
                    void load();
                }
            }
        },
        [status, load],
    );

    const append = useCallback((message: ChatMessage) => {
        setMessages((current) =>
            current.some((m) => m.id === message.id)
                ? current
                : [...current, message].slice(-MAX_MESSAGES),
        );
    }, []);

    useEchoPublic<{ message: ChatMessage }>(
        settings.channel,
        '.chat.message.sent',
        ({ message }) => {
            append(message);

            if (!open && message.user.id !== me?.id) {
                setUnread((n) => n + 1);
            }
        },
        [open, me?.id],
    );

    useEchoPublic<{ id: number }>(
        settings.channel,
        '.chat.message.deleted',
        ({ id }) => {
            setMessages((current) => current.filter((m) => m.id !== id));
            setPinned((current) => (current?.id === id ? null : current));
        },
    );

    useEchoPublic<{ message: ChatMessage }>(
        settings.channel,
        '.chat.message.pinned',
        ({ message }) => {
            setMessages((current) =>
                current.map((m) =>
                    m.id === message.id
                        ? message
                        : {
                              ...m,
                              is_pinned: message.is_pinned
                                  ? false
                                  : m.is_pinned,
                          },
                ),
            );
            setPinned((current) =>
                message.is_pinned
                    ? message
                    : current?.id === message.id
                      ? null
                      : current,
            );
        },
    );

    useEchoPublic<{ settings: ChatSettings }>(
        settings.channel,
        '.chat.settings.updated',
        (payload) => setSettings(payload.settings),
    );

    const send = useCallback(
        async (body: string): Promise<string | null> => {
            const result = await apiFetch<{
                data: ChatMessage;
                cooldown_seconds: number;
            }>(`${base}/messages`, { method: 'POST', body: { body } });

            if (result.ok) {
                append(result.data.data);
                setCooldownUntil(
                    Date.now() + result.data.cooldown_seconds * 1000,
                );

                return null;
            }

            if (result.status === 429) {
                const wait = Number(result.headers.get('Retry-After') ?? 3);
                setCooldownUntil(Date.now() + wait * 1000);
            }

            if (result.status === 401) {
                setMe(null);

                return 'Your session ended. Log in again to chat.';
            }

            return errorMessage(
                result.data,
                'Your message did not send. Try again.',
            );
        },
        [base, append],
    );

    return (
        <ChatContext.Provider
            value={{
                open,
                setOpen,
                status,
                messages,
                pinned,
                settings,
                me,
                googleSignIn: chat.google_sign_in,
                unread,
                cooldownUntil,
                send,
            }}
        >
            {children}
        </ChatContext.Provider>
    );
}

export function useChat(): ChatApi {
    const chat = useContext(ChatContext);

    if (!chat) {
        throw new Error('useChat() must be used inside <ChatProvider>.');
    }

    return chat;
}
