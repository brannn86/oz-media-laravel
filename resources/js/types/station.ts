/**
 * Shapes of the public-site props (see app/Http/Resources).
 */

import type { Account, SharedChatProps } from './chat';

export type VideoSource = {
    type: 'youtube' | 'hls' | 'embed';
    src: string;
};

export type StreamStatus = {
    station: string;
    audio_url: string | null;
    video: VideoSource | null;
    /** Video is offered: the station has a video stream URL. */
    is_video_live: boolean;
    /** Loops in the video player while the livestream is blank or offline. */
    intermission_url: string | null;
};

export type Station = {
    slug: string;
    name: string;
    tagline: string | null;
    contact: Partial<
        Record<'email' | 'phone' | 'address' | 'map_url' | 'whatsapp', string>
    > & {
        /** Always set: the station's own or the default. */
        heading: string;
    };
    /** In footer order: Instagram, TikTok, YouTube, Threads, X (empty ones left out). */
    social: Partial<
        Record<'instagram' | 'tiktok' | 'youtube' | 'threads' | 'x', string>
    >;
    /** The OZ Poin page is switched on (points accrue either way). */
    rewards_page: boolean;
    channel: string;
    stream: StreamStatus;
};

export type Announcer = {
    id: number;
    slug: string;
    name: string;
    display_name: string;
    bio: string | null;
    avatar_url: string | null;
    social: Partial<Record<'instagram' | 'tiktok' | 'x', string>>;
    programs?: { id: number; title: string }[];
};

export type Program = {
    id: number;
    slug: string;
    title: string;
    description: string | null;
    cover_image_url: string | null;
    announcers?: Announcer[];
    /** Weekly slots, Sunday first (only where the page loads them). */
    schedules?: ProgramSlot[];
};

export type ProgramSlot = Omit<ScheduleSlot, 'program'>;

export type ScheduleSlot = {
    id: number;
    /** 0 = Sunday … 6 = Saturday */
    day: number;
    /** HH:mm, WIB. "00:00" as an end time means midnight. */
    start: string;
    end: string;
    program: Program;
};

export type Broadcast = {
    at: string;
    refresh_at: string | null;
    current: (ScheduleSlot & { starts_at: string; ends_at: string }) | null;
    next: (ScheduleSlot & { starts_at: string }) | null;
};

/** A song as reported by the station's Icecast stream (times in WIB). */
export type Track = {
    id: number;
    title: string;
    artist: string | null;
    started_at: string;
    ended_at: string | null;
};

export type NowPlaying = {
    /** Null between songs or when the stream is off. */
    track: Track | null;
    /** Newest first. */
    recent: Track[];
    /** Listening right now according to Icecast; null when unknown. */
    listeners: number | null;
};

export type PostCategory = { value: string; label: string };

export type Post = {
    id: number;
    slug: string;
    title: string;
    category: PostCategory | null;
    excerpt: string | null;
    image_url: string | null;
    published_at: string | null;
    author?: string | null;
    station?: { slug: string; name: string };
    // Detail page only:
    updated_at?: string | null;
    image_caption?: string | null;
    image_credit?: string | null;
    source?: { name: string | null; url: string | null } | null;
    body?: string;
};

export type StationEvent = {
    id: number;
    slug: string;
    title: string;
    organizer: string | null;
    is_oz_event: boolean;
    location: string | null;
    starts_at: string;
    ends_at: string | null;
    description: string | null;
    poster_url: string | null;
    ticket_url: string | null;
};

export type SocialHighlight = {
    id: number;
    platform: 'youtube' | 'tiktok' | 'instagram';
    platform_label: string;
    url: string;
    title: string;
    orientation: 'landscape' | 'portrait';
    thumbnail_url: string | null;
};

export type PageMeta = {
    title: string;
    description: string | null;
    image: string | null;
    type: string;
    url: string;
};

export type Paginated<T> = {
    data: T[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        total: number;
    };
};

/** Props every page under /{station} receives (shared by ShareStationContext). */
export type StationPageProps = {
    station: Station;
    broadcast: Broadcast;
    now_playing: NowPlaying;
    chat: SharedChatProps;
    /** The signed-in listener, or null for guests. */
    account: Account | null;
    meta: PageMeta;
};
