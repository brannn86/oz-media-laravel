/** See app/Http/Resources/Chat*Resource.php and AccountResource.php. */

export type ChatUser = {
    id: number;
    name: string;
    avatar_url: string | null;
    is_staff: boolean;
};

export type ChatMessage = {
    id: number;
    body: string;
    is_pinned: boolean;
    created_at: string;
    user: ChatUser;
};

export type ChatSettings = {
    channel: string;
    slow_mode_seconds: number;
    cooldown_seconds: number;
    max_length: number;
};

export type Account = ChatUser & {
    email: string;
    has_password: boolean;
    has_google: boolean;
    chat_muted_until: string | null;
    points_balance: number;
    current_streak_days: number;
};

export type SharedChatProps = {
    settings: ChatSettings;
    google_sign_in: boolean;
};
