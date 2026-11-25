/**
 * The bits of YouTube's IFrame Player API the video dock uses, loaded once
 * on demand (only when someone actually watches a YouTube livestream).
 */

export type YouTubePlayer = {
    playVideo(): void;
    mute(): void;
    unMute(): void;
    /** 0–100. */
    setVolume(volume: number): void;
    destroy(): void;
};

type YouTubePlayerEvent = { target: YouTubePlayer; data: number };

type YouTubeNamespace = {
    Player: new (
        element: HTMLIFrameElement,
        options: {
            events: {
                onReady?: (event: YouTubePlayerEvent) => void;
                onStateChange?: (event: YouTubePlayerEvent) => void;
                onError?: (event: YouTubePlayerEvent) => void;
            };
        },
    ) => YouTubePlayer;
};

declare global {
    interface Window {
        YT?: YouTubeNamespace;
        onYouTubeIframeAPIReady?: () => void;
    }
}

/** YT.PlayerState values. */
export const YT_STATE = {
    ENDED: 0,
    PLAYING: 1,
} as const;

let loading: Promise<YouTubeNamespace> | null = null;

export function loadYouTubeApi(): Promise<YouTubeNamespace> {
    if (window.YT?.Player) {
        return Promise.resolve(window.YT);
    }

    loading ??= new Promise<YouTubeNamespace>((resolve, reject) => {
        const previous = window.onYouTubeIframeAPIReady;

        window.onYouTubeIframeAPIReady = () => {
            previous?.();

            if (window.YT) {
                resolve(window.YT);
            }
        };

        const script = document.createElement('script');
        script.src = 'https://www.youtube.com/iframe_api';
        script.async = true;
        script.onerror = () => {
            loading = null;
            reject(new Error('The YouTube player API could not be loaded.'));
        };
        document.head.append(script);
    });

    return loading;
}

/** The embed URL with the JS API switched on, so we can tell if it's live. */
export function withJsApi(src: string): string {
    const url = new URL(src);
    url.searchParams.set('enablejsapi', '1');
    url.searchParams.set('origin', window.location.origin);
    url.searchParams.set('playsinline', '1');

    return url.toString();
}
