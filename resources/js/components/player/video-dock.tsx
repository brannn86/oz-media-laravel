import { Minimize2, X } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { useChat } from '@/components/chat/chat-context';
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
    const chatOpen = useChat().open;
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
                              // Sit beside the chat drawer instead of under it.
                              chatOpen && 'sm:right-[25rem]',
                          ),
                )}
            >
                <div className="flex items-center justify-between gap-2 px-3 py-2 text-oz-white">
                    <p className="truncate text-sm font-semibold">
                        {player.station.name} live
                    </p>
                    <div className="flex items-center gap-1">
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
                <div className="aspect-video w-full bg-oz-black">
                    <VideoSurface source={stream.video} />
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

function HlsVideo({ src }: { src: string }) {
    const videoRef = useRef<HTMLVideoElement>(null);

    useEffect(() => {
        const video = videoRef.current;

        if (!video) {
            return;
        }

        const detach = attachStream(video, src, () => undefined);
        void video.play().catch(() => undefined);

        return detach;
    }, [src]);

    return (
        <video
            ref={videoRef}
            className="size-full"
            controls
            playsInline
            autoPlay
        />
    );
}
