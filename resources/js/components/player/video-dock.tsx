import { Minimize2, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useLive } from '@/components/live/live-context';
import {
    ReactionButtons,
    ReactionOverlay,
    useReactions,
} from '@/components/live/reactions';
import { attachStream } from '@/components/player/attach-stream';
import { usePlayer } from '@/components/player/player-context';
import { cn } from '@/lib/utils';
import type { VideoSource } from '@/types';

/**
 * Floating video panel for Video Livestream mode.
 *
 * Docked: small card above the player bar. Expanded: large centred panel.
 * Only the container's classes change between the two, so the video keeps playing.
 */
export function VideoDock() {
    const player = usePlayer();
    const drawerOpen = useLive().drawerOpen;
    const reactions = useReactions(player.station.slug);
    const { stream, mode, active, videoLayout } = player;

    if (mode !== 'video' || !active || !stream.video) {
        return null;
    }

    const expanded = videoLayout === 'expanded';

    return (
        <>
            {expanded && (
                <button
                    type="button"
                    aria-label="Shrink video"
                    onClick={() => player.setVideoLayout('docked')}
                    className="fixed inset-0 z-30 bg-oz-black/70"
                />
            )}
            <section
                aria-label={`${player.station.name} video livestream`}
                className={cn(
                    'fixed z-40 overflow-hidden bg-oz-black shadow-2xl ring-1 ring-oz-white/10',
                    expanded
                        ? 'inset-x-3 top-1/2 mx-auto max-w-5xl -translate-y-[calc(50%+2.25rem)] rounded-2xl sm:inset-x-8'
                        : cn(
                              'right-3 bottom-[5.25rem] left-3 rounded-2xl sm:left-auto sm:w-[26rem]',
                              // Sit beside the drawer instead of under it.
                              drawerOpen && 'sm:right-[25rem]',
                          ),
                )}
            >
                <div className="flex items-center justify-between gap-2 px-3 py-2 text-oz-white">
                    <p className="truncate text-sm font-semibold">
                        {player.station.name} live
                    </p>
                    <div className="flex items-center gap-1">
                        <ReactionButtons onReact={reactions.send} />
                        {expanded && (
                            <button
                                type="button"
                                onClick={() => player.setVideoLayout('docked')}
                                aria-label="Shrink video"
                                className="grid size-8 place-items-center rounded-full hover:bg-oz-white/10 focus-visible:ring-2 focus-visible:ring-oz-yellow focus-visible:outline-none"
                            >
                                <Minimize2 className="size-4" />
                            </button>
                        )}
                        <button
                            type="button"
                            onClick={player.stop}
                            aria-label="Close video"
                            className="grid size-8 place-items-center rounded-full hover:bg-oz-white/10 focus-visible:ring-2 focus-visible:ring-oz-yellow focus-visible:outline-none"
                        >
                            <X className="size-4" />
                        </button>
                    </div>
                </div>
                <div className="relative aspect-video w-full bg-oz-black">
                    <VideoSurface source={stream.video} />
                    <ReactionOverlay floaters={reactions.floaters} />
                </div>
            </section>
        </>
    );
}

function VideoSurface({ source }: { source: VideoSource }) {
    if (source.type === 'hls') {
        return <HlsVideo src={source.src} />;
    }

    return (
        <iframe
            src={source.src}
            title="Video livestream"
            className="size-full"
            allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
            allowFullScreen
            referrerPolicy="strict-origin-when-cross-origin"
        />
    );
}

/**
 * HLS livestream. Browsers may refuse to start video *with sound* outside a
 * tap; then it starts muted (always allowed) with a "Tap for sound" button.
 * A dropped stream is retried with backoff.
 */
function HlsVideo({ src }: { src: string }) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const [needsSound, setNeedsSound] = useState(false);
    const [attempt, setAttempt] = useState(0);

    useEffect(() => {
        const video = videoRef.current;

        if (!video) {
            return;
        }

        let retry: number | undefined;
        const reconnect = () => {
            retry = window.setTimeout(
                () => setAttempt((n) => n + 1),
                Math.min(30_000, 2000 * 2 ** Math.min(attempt, 4)),
            );
        };

        const detach = attachStream(video, src, reconnect);

        video.play().catch((error: unknown) => {
            if (
                error instanceof DOMException &&
                error.name === 'NotAllowedError'
            ) {
                video.muted = true;
                setNeedsSound(true);
                void video.play().catch(() => undefined);
            }
        });

        return () => {
            window.clearTimeout(retry);
            detach();
        };
    }, [src, attempt]);

    return (
        <>
            <video
                ref={videoRef}
                className="size-full"
                controls
                playsInline
                autoPlay
                onEnded={() => setAttempt((n) => n + 1)}
            />
            {needsSound && (
                <button
                    type="button"
                    onClick={() => {
                        const video = videoRef.current;

                        if (video) {
                            video.muted = false;
                            void video.play();
                        }

                        setNeedsSound(false);
                    }}
                    className="absolute top-3 left-3 rounded-full bg-oz-yellow px-4 py-2 text-sm font-bold text-oz-black shadow-lg focus-visible:ring-4 focus-visible:ring-oz-white focus-visible:outline-none"
                >
                    Tap for sound
                </button>
            )}
        </>
    );
}
