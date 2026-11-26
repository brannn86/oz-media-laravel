import { useEffect, useRef } from 'react';
import { cn } from '@/lib/utils';

/**
 * Band centres in Hz, from the middle of the visualizer outwards (mirrored on
 * both sides, so 68 bars). Each bar shows the true loudness of its band.
 */
const BANDS = [
    9000, 7700, 6600, 5600, 4800, 4100, 3500, 3000, 2600, 2200, 1900, 1600,
    1400, 1200, 1000, 850, 726, 620, 530, 453, 387, 331, 282, 241, 206, 176,
    150, 129, 110, 94, 80, 69, 59, 50,
];

/** The hill: 1 in the middle, easing down to 0.3 at the edges. */
function envelope(fromCentre: number): number {
    return 0.3 + 0.7 * Math.cos((Math.min(1, fromCentre) * Math.PI) / 2) ** 1.5;
}
/** Gap between bars, as a share of each bar's slot. */
const GAP = 0.28;
/** Where `rays` mode's sunbeams start and reach (share of the radius). */
const RAY_INNER = 0.64;
const RAY_REACH = 0.34;

/**
 * Live bar visualizer for the radio, drawn on a canvas.
 *
 * With an AnalyserNode it shows the real loudness of each band in BANDS. Without one (Apple
 * devices, or a stream that blocks analysis) it falls back to a gentle
 * decorative motion while playing. Paused, the bars sink to a low idle
 * line; with reduced motion they stay still.
 *
 * `rays` draws the same spectrum as sunbeams around a circle instead (OZ
 * Bali's sun), 9 kHz at the top, in the --bali-gold/--bali-sand colours.
 */
export function AudioVisualizer({
    analyser,
    playing,
    shape = 'bars',
    className,
}: {
    analyser: AnalyserNode | null;
    playing: boolean;
    shape?: 'bars' | 'rays';
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

        const rays = shape === 'rays';
        // The canvas inherits the page's variables, so the Bali ones reach it.
        const styles = getComputedStyle(canvas);
        const yellow =
            styles.getPropertyValue('--color-oz-yellow').trim() || '#f2bd51';
        const butter =
            styles.getPropertyValue('--color-oz-butter').trim() || '#fcf48e';
        const gold = styles.getPropertyValue('--bali-gold').trim() || yellow;
        const sand = styles.getPropertyValue('--bali-sand').trim() || butter;
        const still = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        const bins = new Uint8Array(analyser?.frequencyBinCount ?? 0);
        // Each band's bin range: edges halfway (geometrically) to its
        // neighbours, at least one bin wide.
        const ranges = BANDS.map((hz, k) => {
            if (!analyser) {
                return [0, 0];
            }

            const hzPerBin = analyser.context.sampleRate / analyser.fftSize;
            const above = BANDS[k - 1] ?? hz * (hz / BANDS[k + 1]);
            const below = BANDS[k + 1] ?? hz * (hz / BANDS[k - 1]);
            const centre = Math.round(hz / hzPerBin);

            return [
                Math.min(centre, Math.ceil(Math.sqrt(hz * below) / hzPerBin)),
                Math.max(centre, Math.floor(Math.sqrt(hz * above) / hzPerBin)),
            ];
        });
        const bars = BANDS.length * 2;
        const heights = new Float32Array(bars);
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
                // Mirror: the two middle bars are BANDS[0], the edges the last.
                const band =
                    i < BANDS.length ? BANDS.length - 1 - i : i - BANDS.length;
                const [from, to] = ranges[band];
                let sum = 0;

                for (let b = from; b <= to; b++) {
                    sum += bins[b];
                }

                return Math.max(0.04, sum / (to - from + 1) / 255);
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
            }

            context.clearRect(0, 0, width, height);

            if (rays) {
                const radius = Math.min(width, height) / 2;
                const inner = radius * RAY_INNER;
                const gradient = context.createRadialGradient(
                    width / 2,
                    height / 2,
                    inner,
                    width / 2,
                    height / 2,
                    radius,
                );
                gradient.addColorStop(0, gold);
                gradient.addColorStop(1, sand);
                context.strokeStyle = gradient;
                context.lineCap = 'round';
                context.lineWidth = ((2 * Math.PI * inner) / bars) * 0.42;

                for (let i = 0; i < bars; i++) {
                    heights[i] += (target(i, time) - heights[i]) * 0.25;

                    // i = 0 at the bottom, so the middle (9 kHz) is at the top.
                    const angle = (i / bars) * 2 * Math.PI + Math.PI / 2;
                    const outer =
                        inner + Math.max(3, heights[i] * radius * RAY_REACH);
                    const cos = Math.cos(angle);
                    const sin = Math.sin(angle);

                    context.beginPath();
                    context.moveTo(
                        width / 2 + cos * inner,
                        height / 2 + sin * inner,
                    );
                    context.lineTo(
                        width / 2 + cos * outer,
                        height / 2 + sin * outer,
                    );
                    context.stroke();
                }

                frame = window.requestAnimationFrame(draw);

                return;
            }

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
    }, [analyser, shape]);

    return (
        <canvas
            ref={canvasRef}
            aria-hidden
            className={cn('block size-full', className)}
        />
    );
}
