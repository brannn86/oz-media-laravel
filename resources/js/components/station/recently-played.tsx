import { usePlayer } from '@/components/player/player-context';
import { SectionHeading } from '@/components/station/page-heading';
import { formatTime } from '@/lib/wib';

/**
 * Songs the stream played before the current one, newest first.
 * Updates live as songs change; hidden until there is history.
 */
export function RecentlyPlayed({ limit = 6 }: { limit?: number }) {
    const { recent } = usePlayer().nowPlaying;

    if (recent.length === 0) {
        return null;
    }

    return (
        <section
            aria-labelledby="recently-played-title"
            className="mx-auto max-w-7xl px-4 pt-16 sm:px-6 sm:pt-24"
        >
            <SectionHeading
                id="recently-played-title"
                title="Recently played"
            />
            <ol className="grid gap-x-8 sm:grid-cols-2 lg:grid-cols-3">
                {recent.slice(0, limit).map((track) => (
                    <li
                        key={track.id}
                        className="flex items-baseline gap-4 border-t border-oz-black/10 py-4"
                    >
                        <time
                            dateTime={track.started_at}
                            className="w-12 shrink-0 text-sm font-semibold text-oz-black/60 tabular-nums"
                        >
                            {formatTime(track.started_at)}
                        </time>
                        <div className="min-w-0">
                            <p className="truncate font-oz-display text-lg leading-tight font-extrabold">
                                {track.title}
                            </p>
                            {track.artist && (
                                <p className="truncate text-sm text-oz-black/70">
                                    {track.artist}
                                </p>
                            )}
                        </div>
                    </li>
                ))}
            </ol>
        </section>
    );
}
