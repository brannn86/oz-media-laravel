/**
 * Shapes of the public-site props (see app/Http/Resources).
 */

export type VideoSource = {
    type: 'youtube' | 'hls' | 'embed';
    src: string;
};

export type StreamStatus = {
    station: string;
    audio_url: string | null;
    video: VideoSource | null;
    is_video_live: boolean;
};

export type Station = {
    slug: string;
    name: string;
    tagline: string | null;
    contact: Partial<
        Record<'email' | 'phone' | 'address' | 'whatsapp', string>
    >;
    social: Partial<Record<'youtube' | 'tiktok' | 'instagram' | 'x', string>>;
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
};

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

export type Post = {
    id: number;
    slug: string;
    title: string;
    excerpt: string | null;
    image_url: string | null;
    published_at: string | null;
    author?: string | null;
    station?: { slug: string; name: string };
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
    meta: PageMeta;
};
