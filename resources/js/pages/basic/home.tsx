import { Head, usePage } from '@inertiajs/react';
import { LoaderCircle, Pause, Play, RotateCcw } from 'lucide-react';
import { AudioVisualizer } from '@/components/player/audio-visualizer';
import { VolumeControl } from '@/components/player/player-bar';
import { usePlayer } from '@/components/player/player-context';
import { BrandLogo } from '@/components/station/brand-logo';
import { Marquee } from '@/components/station/marquee';
import { SponsorBanner } from '@/components/station/sponsor-banner';
import type { Sponsor } from '@/components/station/sponsor-banner';
import { cn } from '@/lib/utils';
import type { StationPageProps } from '@/types';

type Props = StationPageProps & { sponsor: Sponsor | null };

/**
 * A basic station's one page: a sunset. The logo over tangerine-to-berry
 * arcs, the sun is the play button (sunbeams from the music while it
 * plays), its reflection on the lagoon, and the song on air on the water.
 */
export default function BasicStationHome() {
    const { meta, station, sponsor } = usePage<Props>().props;

    return (
        <>
            <Head title={meta.title} />
            <section
                aria-labelledby="radio-title"
                className="flex flex-1 flex-col items-center px-4"
            >
                <h1
                    id="radio-title"
                    className="bali-land relative z-30 mb-[clamp(-36px,-3svh,-12px)] w-[min(78vw,380px,45.75svh)]"
                >
                    <BrandLogo
                        brand={station.slug}
                        onDark
                        className="h-auto w-full"
                    />
                    <span className="sr-only"> live radio</span>
                </h1>
                <Sunset />
            </section>
            {sponsor && (
                <div className="bg-[var(--bali-lagoon)] px-4 pt-2 sm:px-6">
                    <SponsorBanner
                        sponsor={sponsor}
                        stationName={station.name}
                        tone="dark"
                        className="mx-auto max-w-5xl"
                    />
                </div>
            )}
        </>
    );
}

function Sunset() {
    const player = usePlayer();
    const { active, status, nowPlaying, analyser, station } = player;
    const track = nowPlaying.track;
    const playing = active && status === 'playing';
    const connecting =
        active && (status === 'loading' || status === 'reconnecting');
    const needsTap = status === 'blocked' || status === 'error';
    const available = player.audioAvailable;

    const label = needsTap
        ? status === 'error'
            ? 'Try the radio again'
            : 'Tap to start the radio'
        : active
          ? 'Pause the radio'
          : 'Play the radio';

    return (
        <div className="relative w-[min(100%-8px,460px)] flex-1 pb-10">
            <div aria-hidden className="bali-arcs" />
            <div aria-hidden className="bali-sea z-10" />

            {/* The sun sits in a dial-sized window, so it rises from the horizon. */}
            <div className="absolute top-0 left-1/2 z-20 aspect-square w-[var(--bali-dial)] -translate-x-1/2 overflow-hidden">
                {active && (
                    <AudioVisualizer
                        analyser={analyser}
                        playing={playing}
                        shape="rays"
                        className="absolute inset-0"
                    />
                )}
                <div aria-hidden className="bali-sun bali-rise" />
                {available && (
                    <button
                        type="button"
                        onClick={() =>
                            active && !needsTap
                                ? player.stop()
                                : player.play('audio')
                        }
                        aria-label={label}
                        className={cn(
                            'bali-rise absolute top-1/2 left-1/2 -mt-[calc(var(--bali-play)/2)] -ml-[calc(var(--bali-play)/2)] grid size-[var(--bali-play)] cursor-pointer place-items-center rounded-full bg-oz-black text-[var(--bali-sand)] transition-[transform,background-color,color] duration-150 hover:bg-[var(--bali-sand)] hover:text-oz-black focus-visible:ring-4 focus-visible:ring-[var(--bali-sand)] focus-visible:outline-none active:scale-95',
                            playing && 'shadow-[0_0_0_5px_rgba(0,0,0,0.22)]',
                        )}
                    >
                        {connecting ? (
                            <LoaderCircle
                                className="size-[40%] motion-safe:animate-spin"
                                aria-hidden
                            />
                        ) : status === 'error' ? (
                            <RotateCcw className="size-[40%]" aria-hidden />
                        ) : active && !needsTap ? (
                            <Pause
                                className="size-[40%] fill-current"
                                aria-hidden
                            />
                        ) : (
                            <Play
                                className="ml-[6%] size-[40%] fill-current"
                                aria-hidden
                            />
                        )}
                    </button>
                )}
            </div>

            {/* Holds the dial's place in the flow; the sun above is absolute. */}
            <div aria-hidden className="aspect-square w-[var(--bali-dial)]" />

            <div
                aria-hidden
                data-playing={playing || undefined}
                className="bali-reflection relative z-10 grid h-[var(--bali-reflection)] content-start justify-items-center gap-[7px] pt-[9px]"
            >
                <span />
                <span />
                <span />
                <span />
                <span />
            </div>

            <div
                className="bali-fade relative z-20 rounded-[22px] bg-oz-black px-6 pt-5 pb-4 shadow-[7px_7px_0_var(--bali-hibiscus)]"
                aria-live="polite"
            >
                {available ? (
                    <>
                        <p className="text-xs font-semibold tracking-[0.02em] text-[var(--bali-gold)] lowercase">
                            {track ? 'Now playing' : 'On air'}
                        </p>
                        <p className="mt-2 font-oz-display text-[22px] leading-tight font-bold max-[360px]:text-lg">
                            <Marquee>{track?.title ?? station.name}</Marquee>
                        </p>
                        <p className="mt-1 text-sm text-[var(--bali-sand)]/75">
                            <Marquee>{track?.artist ?? 'Live radio'}</Marquee>
                        </p>
                        {status === 'error' && (
                            <p className="mt-2 text-sm font-semibold text-[var(--bali-gold)]">
                                The stream isn't answering.
                            </p>
                        )}
                        <div className="mt-3 flex items-center justify-between gap-3">
                            <VolumeControl className="flex" />
                            <span
                                className={cn(
                                    'inline-flex h-7 items-center gap-1.5 rounded-full border px-2.5 text-xs font-bold tracking-[0.02em] lowercase',
                                    playing
                                        ? 'border-[var(--bali-gold)] bg-[var(--bali-gold)] text-oz-black'
                                        : 'border-[var(--bali-sand)]/25',
                                )}
                            >
                                <span
                                    aria-hidden
                                    className={cn(
                                        'size-2 rounded-full',
                                        playing
                                            ? 'bg-oz-black motion-safe:animate-pulse'
                                            : 'bg-[var(--bali-sand)]/50',
                                    )}
                                />
                                Live
                            </span>
                        </div>
                    </>
                ) : (
                    <p className="py-2 text-center font-oz-display text-[22px] leading-tight font-bold">
                        The live stream is coming soon.
                    </p>
                )}
            </div>
        </div>
    );
}
