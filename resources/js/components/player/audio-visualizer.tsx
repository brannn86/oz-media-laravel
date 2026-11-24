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
 * Bins spread logarithmically from the lowest to about 7 kHz (bin width is
 * sampleRate / fftSize, ~190 Hz at fftSize 256): music lives low, and a
 * linear spread leaves the outer bars flat.
 */
const LOW_BIN = 1;
const HIGH_BIN = 36;
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
                // Treble is quieter by nature; lift it so the edges move too.
                // The curve adds contrast, so loud passages don't flatten.
                const level = (bins[bin] / 255) * (1 + fromCentre * 0.5);

                return Math.min(1, Math.max(0.05, level ** 1.6));
            }

            if (still) {
                return 0.55 - fromCentre * 0.35;
            }

            // Decorative: overlapping slow waves, tallest in the middle,
            // spaced by position (not bar index) so any bar count looks alike.
            const x = (i / bars) * 40;
            const wave =
                Math.sin(time / 380 + x * 0.55) * 0.5 +
                Math.sin(time / 610 - x * 0.31) * 0.35 +
                Math.sin(time / 230 + x * 1.7) * 0.15;

            return Math.max(
                0.08,
                (0.72 - fromCentre * 0.45) * (0.62 + wave * 0.38),
            );
        };

        const draw = (time: number) => {
            if (analyser && playingRef.current) {
                analyser.getByteFrequencyData(bins);
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
