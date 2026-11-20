/** Phase 4: song requests, polls, reactions and OZ Poin (see app/Http/Resources). */

import type { ChatUser } from './chat';

export type SongRequestStatus = 'pending' | 'approved' | 'played' | 'rejected';

export type SongRequest = {
    id: number;
    song_title: string;
    artist: string;
    shoutout_message: string | null;
    upvotes_count: number;
    status: SongRequestStatus;
    status_label: string;
    created_at: string;
    user: ChatUser;
};

export type PollOption = {
    id: number;
    label: string;
    votes: number;
    percent: number;
    /** Only revealed for trivia once the poll has closed. */
    is_correct: boolean | null;
};

export type Poll = {
    id: number;
    question: string;
    type: 'general' | 'song_battle' | 'trivia';
    type_label: string;
    status: 'live' | 'closed';
    opened_at: string | null;
    closed_at: string | null;
    total_votes: number;
    options: PollOption[];
};

export type ReactionType = 'fire' | 'clap' | 'heart';

export type Reward = {
    id: number;
    name: string;
    description: string | null;
    image_url: string | null;
    points_cost: number;
    stock_left: number | null;
    in_stock: boolean;
};

export type Redemption = {
    id: number;
    reward?: { id: number; name: string };
    points_spent: number;
    status: 'pending' | 'fulfilled' | 'cancelled';
    status_label: string;
    created_at: string;
};

export type LeaderboardEntry = {
    rank: number;
    user: { id: number; name: string; avatar_url: string | null };
    points: number;
};

export type HeartbeatResponse = {
    accepted: boolean;
    awarded: number;
    listening_points: number;
    streak_bonus: number;
    streak_days: number;
    points_balance: number;
    daily_cap_reached: boolean;
    next_in_seconds: number;
};
