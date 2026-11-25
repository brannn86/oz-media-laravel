import { Head, usePage } from '@inertiajs/react';
import { Headphones, LoaderCircle, Pause, Play, RotateCcw } from 'lucide-react';
import { AudioVisualizer } from '@/components/player/audio-visualizer';
import { VolumeControl } from '@/components/player/player-bar';
import { usePlayer } from '@/components/player/player-context';
import { Marquee } from '@/components/station/marquee';
import { SponsorBanner } from '@/components/station/sponsor-banner';
import type { Sponsor } from '@/components/station/sponsor-banner';
import { cn } from '@/lib/utils';
import type { StationPageProps } from '@/types';

type Props = StationPageProps & { sponsor: Sponsor | null };

/**
 * A basic station's one page: the radio, big and centred, with the song on
 * air, the bars and the sponsor under it.
 */
export default function BasicStationHome() {
    const { meta, station, sponsor } = usePage<Props>().props;

    return (
        <>
            <Head title={meta.title} />
            <div className="mx-auto max-w-5xl px-4 pt-6 pb-12 sm:px-6 sm:pt-10">
                <RadioPlayer />
                {sponsor && (
                    <SponsorBanner
                        sponsor={sponsor}
                        stationName={station.name}
                        tone="dark"
                        className="mt-10 sm:mt-14"
                    />
                )}
            </div>
        </>
    );
}

function RadioPlayer() {
    const player = usePlayer();
    const { active, status, nowPlaying, analyser, station } = player;
    const track = nowPlaying.track;
    const playing = active && status === 'playing';
    const connecting =
        active && (status === 'loading' || status === 'reconnecting');
    const needsTap = status === 'blocked' || status === 'error';

    if (!player.audioAvailable) {
        return (
            <section className="rounded-[2rem] border border-oz-white/15 px-6 py-16 text-center">
                <h1 className="font-oz-display text-3xl font-extrabold sm:text-5xl">
                    {station.name}
                </h1>
                <p className="mt-4 text-lg text-oz-white/70">
                    The live stream is coming soon.
                </p>
            </section>
        );
    }

    const label = needsTap
        ? status === 'error'
            ? 'Try the radio again'
            : 'Tap to start the radio'
        : active
          ? 'Pause the radio'
          : 'Play the radio';

    return (
        <section
            aria-labelledby="radio-title"
            className="flex flex-col items-center text-center"
        >
            <p className="inline-flex items-center gap-2 rounded-full bg-oz-white/10 px-4 py-1.5 text-sm font-bold">
                <span
                    className={cn(
                        'size-2 rounded-full',
                        playing
                            ? 'bg-oz-red motion-safe:animate-pulse'
                            : 'bg-oz-white/40',
                    )}
                />
                {station.tagline ?? 'Live radio'}
            </p>

            <h1 id="radio-title" className="sr-only">
                {station.name} live radio
            </h1>

            <div className="relative mt-10">
                {playing && (
                    <span
                        aria-hidden
                        className="absolute inset-0 rounded-full bg-oz-yellow/40 motion-safe:animate-ping"
                    />
                )}
                <button
                    type="button"
                    onClick={() =>
                        active && !needsTap
                            ? player.stop()
                            : player.play('audio')
                    }
                    aria-label={label}
                    className="relative grid size-28 place-items-center rounded-full bg-oz-yellow text-oz-black shadow-[0_0_0_10px_rgba(242,189,81,0.15)] transition-transform hover:scale-105 focus-visible:ring-4 focus-visible:ring-oz-white focus-visible:outline-none sm:size-36"
                >
                    {connecting ? (
                        <LoaderCircle
                            className="size-12 motion-safe:animate-spin"
                            aria-hidden
                        />
                    ) : status === 'error' ? (
                        <RotateCcw className="size-12" aria-hidden />
                    ) : active && !needsTap ? (
                        <Pause className="size-12 fill-current" aria-hidden />
                    ) : (
                        <Play
                            className="size-12 translate-x-1 fill-current"
                            aria-hidden
                        />
                    )}
                </button>
            </div>

            <div className="mt-10 w-full max-w-2xl" aria-live="polite">
                <p className="text-sm font-semibold text-oz-butter">
                    {track ? 'Now playing' : 'On air'}
                </p>
                <p className="mt-1 font-oz-display text-3xl leading-tight font-extrabold sm:text-5xl">
                    <Marquee>{track?.title ?? station.name}</Marquee>
                </p>
                <p className="mt-1 text-lg text-oz-white/70 sm:text-xl">
                    <Marquee>{track?.artist ?? 'Live radio'}</Marquee>
                </p>
                {status === 'error' && (
                    <p className="mt-2 text-sm font-semibold text-oz-yellow">
                        The stream isn't answering.
                    </p>
                )}
            </div>

            <div className="mt-8 h-24 w-full sm:h-32">
                <AudioVisualizer analyser={analyser} playing={playing} />
            </div>

            <div className="mt-6 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm font-semibold text-oz-white/80">
                <VolumeControl className="flex" />
                {nowPlaying.listeners !== null && (
                    <span className="inline-flex items-center gap-2">
                        <Headphones className="size-4" aria-hidden />
                        {nowPlaying.listeners.toLocaleString()}{' '}
                        {nowPlaying.listeners === 1 ? 'listener' : 'listeners'}
                    </span>
                )}
            </div>
        </section>
    );
}
