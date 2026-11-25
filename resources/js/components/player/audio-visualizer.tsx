import { useEffect, useRef } from 'react';
import { cn } from '@/lib/utils';

/**
 * One bar per this many px of width (within MIN_BARS..MAX_BARS): ~73 on the
 * desktop player, 32 on a phone. Mirrored, bass in the middle.
 */
const BAR_SLOT_PX = 12;
const MIN_BARS = 32;
const MAX_BARS = 96;
/**
 * Bins spread logarithmically from the lowest to about 5 kHz (bin width is
 * sampleRate / fftSize, ~190 Hz at fftSize 256): music lives low, and a
 * linear spread leaves the outer bars flat.
 */
const LOW_BIN = 1;
const HIGH_BIN = 28;
/** Below this a band counts as silence, so hiss never fills a bar. */
const NOISE_FLOOR = 48;
/** How fast a band's remembered peak fades (per frame), so loud songs don't stay loud. */
const PEAK_DECAY = 0.985;

/** The hill: 1 in the middle, easing down to 0.3 at the edges. */
function envelope(fromCentre: number): number {
    return 0.3 + 0.7 * Math.cos((Math.min(1, fromCentre) * Math.PI) / 2) ** 1.5;
}
/** Gap between bars, as a share of each bar's slot. */
const GAP = 0.28;

/**
 * Live bar visualizer for the radio, drawn on a canvas.
 *
 * With an AnalyserNode it shows the real spectrum. Without one (Apple
 * devices, or a stream that blocks analysis) it falls back to a gentle
 * decorative motion while playing. Paused, the bars sink to a low idle
 * line; with reduced motion they stay still.
 */
export function AudioVisualizer({
    analyser,
    playing,
    className,
}: {
    analyser: AnalyserNode | null;
    playing: boolean;
    className?: string;
}) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const playingRef = useRef(playing);

    useEffect(() => {
        playingRef.current = playing;
    }, [playing]);

    useEffect(() => {
        const canvas = canvasRef.current;
        const context = canvas?.getContext('2d');

        if (!canvas || !context) {
            return;
        }

        const styles = getComputedStyle(document.documentElement);
        const yellow =
            styles.getPropertyValue('--color-oz-yellow').trim() || '#f2bd51';
        const butter =
            styles.getPropertyValue('--color-oz-butter').trim() || '#fcf48e';
        const still = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        const bins = new Uint8Array(analyser?.frequencyBinCount ?? 0);
        // Each band's recent loudest, so every band (treble too) moves.
        const peaks = new Float32Array(bins.length).fill(NOISE_FLOOR);
        // ...and its running average: movement is measured against it, so the
        // bars react to beats and changes rather than sitting at a steady level.
        const means = new Float32Array(bins.length);
        const heights = new Float32Array(MAX_BARS);
        let bars = MIN_BARS;
        let frame = 0;
        let width = 0;
        let height = 0;

        const resize = () => {
            const ratio = window.devicePixelRatio || 1;
            width = canvas.clientWidth;
            height = canvas.clientHeight;
            canvas.width = Math.round(width * ratio);
            canvas.height = Math.round(height * ratio);
            context.setTransform(ratio, 0, 0, ratio, 0, 0);
            bars = Math.min(
                MAX_BARS,
                Math.max(MIN_BARS, Math.round(width / BAR_SLOT_PX)),
            );
        };

        const observer = new ResizeObserver(resize);
        observer.observe(canvas);
        resize();

        /** Target height (0–1) of bar i right now. */
        const target = (i: number, time: number): number => {
            // Mirror: distance from the centre picks the frequency bin.
            const fromCentre = Math.abs(i - (bars - 1) / 2) / (bars / 2);

            if (!playingRef.current) {
                return 0.06;
            }

            if (analyser) {
                const bin = Math.round(
                    LOW_BIN * (HIGH_BIN / LOW_BIN) ** Math.min(1, fromCentre),
                );
                // Where this band sits between most of its recent average and
                // its recent peak (0–1), shaped by the hill: the centre stays
                // tallest, every bar moves.
                const base = means[bin] * 0.6;
                const level = Math.min(
                    1,
                    Math.max(0, bins[bin] - base) /
                        Math.max(peaks[bin] - base, NOISE_FLOOR / 2),
                );

                return Math.max(
                    0.04,
                    envelope(fromCentre) * (0.2 + 0.8 * level ** 2),
                );
            }

            if (still) {
                return envelope(fromCentre) * 0.6;
            }

            // Decorative: overlapping slow waves, tallest in the middle,
            // spaced by position (not bar index) so any bar count looks alike.
            const x = (i / bars) * 40;
            const wave =
                Math.sin(time / 380 + x * 0.55) * 0.5 +
                Math.sin(time / 610 - x * 0.31) * 0.35 +
                Math.sin(time / 230 + x * 1.7) * 0.15;

            return Math.max(0.06, envelope(fromCentre) * (0.55 + wave * 0.4));
        };

        const draw = (time: number) => {
            if (analyser && playingRef.current) {
                analyser.getByteFrequencyData(bins);

                for (let b = 0; b < bins.length; b++) {
                    peaks[b] = Math.max(peaks[b] * PEAK_DECAY, bins[b]);
                    means[b] = means[b] * 0.97 + bins[b] * 0.03;
                }
            }

            context.clearRect(0, 0, width, height);

            const gradient = context.createLinearGradient(0, height, 0, 0);
            gradient.addColorStop(0, yellow);
            gradient.addColorStop(0.55, butter);
            gradient.addColorStop(1, '#fffbe0');
            context.fillStyle = gradient;

            const slot = width / bars;
            const barWidth = slot * (1 - GAP);
            const radius = Math.min(barWidth / 2, 6);

            for (let i = 0; i < bars; i++) {
                // Ease towards the target: smooth rise and fall.
                heights[i] += (target(i, time) - heights[i]) * 0.25;

                const barHeight = Math.max(2, heights[i] * height);
                const x = i * slot + (slot - barWidth) / 2;
                const y = height - barHeight;

                context.beginPath();
                context.roundRect(x, y, barWidth, barHeight, [
                    radius,
                    radius,
                    0,
                    0,
                ]);
                context.fill();
            }

            frame = window.requestAnimationFrame(draw);
        };

        frame = window.requestAnimationFrame(draw);

        return () => {
            window.cancelAnimationFrame(frame);
            observer.disconnect();
        };
    }, [analyser]);

    return (
        <canvas
            ref={canvasRef}
            aria-hidden
            className={cn('block size-full', className)}
        />
    );
}
