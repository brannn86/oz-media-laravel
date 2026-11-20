import { Head, router, usePage } from '@inertiajs/react';
import { Coins, Flame } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { useLive } from '@/components/live/live-context';
import { SignInPrompt } from '@/components/live/sign-in-prompt';
import { CoverImage } from '@/components/station/cover-image';
import { PageHeading } from '@/components/station/page-heading';
import { apiFetch, errorMessage } from '@/lib/api';
import { cn } from '@/lib/utils';
import { formatDate } from '@/lib/wib';
import type {
    LeaderboardEntry,
    Redemption,
    Reward,
    StationPageProps,
} from '@/types';

type Props = StationPageProps & {
    leaderboard: { week: LeaderboardEntry[]; all: LeaderboardEntry[] };
    rewards: Reward[];
    redemptions: Redemption[];
    earning: {
        per_minute: number;
        daily_listening_cap: number;
        streak_bonus_per_day: number;
        streak_bonus_max_days: number;
        song_request_approved: number;
        poll_vote: number;
    };
};

export default function StationRewards() {
    const { station, meta, rewards, leaderboard, redemptions, earning } =
        usePage<Props>().props;
    const { account } = useLive();

    const ways = [
        {
            title: 'Listen live',
            text: `${earning.per_minute} poin for every minute the player is on, up to ${earning.daily_listening_cap} a day.`,
        },
        {
            title: 'Keep a streak',
            text: `Tune in on consecutive days for a daily bonus that grows by ${earning.streak_bonus_per_day} poin a day, up to day ${earning.streak_bonus_max_days}.`,
        },
        {
            title: 'Get a request played',
            text: `${earning.song_request_approved} poin when the studio picks your song request.`,
        },
        {
            title: 'Vote in live polls',
            text: `${earning.poll_vote} poin for every song battle or trivia vote.`,
        },
    ];

    return (
        <>
            <Head title={meta.title} />
            <PageHeading
                title="OZ Poin"
                intro={`Earn points with ${station.name} and swap them for merch, tickets and more.`}
            >
                {account && (
                    <div className="mt-8 flex flex-wrap gap-3">
                        <p className="inline-flex items-center gap-3 rounded-full bg-oz-yellow px-6 py-3">
                            <Coins className="size-6" aria-hidden />
                            <span className="font-oz-display text-3xl font-extrabold tabular-nums">
                                {account.points_balance.toLocaleString()}
                            </span>
                            <span className="font-semibold">poin</span>
                        </p>
                        {account.current_streak_days > 0 && (
                            <p className="inline-flex items-center gap-2 rounded-full bg-oz-black px-5 py-3 font-semibold text-oz-white">
                                <Flame
                                    className="size-5 text-oz-yellow"
                                    aria-hidden
                                />
                                {account.current_streak_days}-day streak
                            </p>
                        )}
                    </div>
                )}
            </PageHeading>

            <section aria-labelledby="earn-title" className="bg-oz-paper">
                <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6">
                    <h2
                        id="earn-title"
                        className="font-oz-display text-3xl font-extrabold tracking-tight"
                    >
                        How to earn
                    </h2>
                    <dl className="mt-8 grid gap-x-10 gap-y-6 sm:grid-cols-2 lg:grid-cols-4">
                        {ways.map((way) => (
                            <div key={way.title}>
                                <dt className="font-oz-display text-xl font-extrabold">
                                    {way.title}
                                </dt>
                                <dd className="mt-1 text-oz-ink">{way.text}</dd>
                            </div>
                        ))}
                    </dl>
                    {!account && (
                        <SignInPrompt
                            title="Start collecting"
                            text="Points go to your account, so log in before you press play."
                            className="mt-10 max-w-xl rounded-3xl bg-oz-white p-6"
                        />
                    )}
                </div>
            </section>

            <section
                aria-labelledby="rewards-title"
                className="mx-auto max-w-7xl px-4 py-16 sm:px-6"
            >
                <h2
                    id="rewards-title"
                    className="font-oz-display text-3xl font-extrabold tracking-tight"
                >
                    Rewards
                </h2>
                {rewards.length === 0 ? (
                    <p className="mt-6 text-lg text-oz-ink">
                        New rewards are coming soon. Keep collecting!
                    </p>
                ) : (
                    <ul className="mt-8 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        {rewards.map((reward) => (
                            <li key={reward.id}>
                                <RewardCard reward={reward} />
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <div className="mx-auto grid max-w-7xl gap-12 px-4 pb-20 sm:px-6 lg:grid-cols-[1.4fr_1fr]">
                <Leaderboard leaderboard={leaderboard} />
                {account && redemptions.length > 0 && (
                    <section aria-labelledby="mine-title">
                        <h2
                            id="mine-title"
                            className="font-oz-display text-3xl font-extrabold tracking-tight"
                        >
                            Your rewards
                        </h2>
                        <ul className="mt-6 divide-y divide-oz-black/10">
                            {redemptions.map((redemption) => (
                                <li
                                    key={redemption.id}
                                    className="flex items-center justify-between gap-4 py-3"
                                >
                                    <div>
                                        <p className="font-bold">
                                            {redemption.reward?.name}
                                        </p>
                                        <p className="text-sm text-oz-ink/70">
                                            {formatDate(redemption.created_at)},{' '}
                                            {redemption.points_spent} poin
                                        </p>
                                    </div>
                                    <span
                                        className={cn(
                                            'rounded-full px-3 py-1 text-xs font-bold',
                                            redemption.status === 'fulfilled' &&
                                                'bg-oz-yellow',
                                            redemption.status === 'pending' &&
                                                'bg-oz-butter',
                                            redemption.status === 'cancelled' &&
                                                'bg-oz-paper text-oz-ink',
                                        )}
                                    >
                                        {redemption.status_label}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}

function RewardCard({ reward }: { reward: Reward }) {
    const { station } = usePage<Props>().props;
    const { account, updatePoints } = useLive();
    const [confirming, setConfirming] = useState(false);
    const [note, setNote] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const short = account ? reward.points_cost - account.points_balance : 0;

    const redeem = async () => {
        setBusy(true);
        const result = await apiFetch<{ points_balance: number }>(
            `/api/v1/stations/${station.slug}/rewards/${reward.id}/redeem`,
            { method: 'POST', body: { note: note || null } },
        );
        setBusy(false);

        if (!result.ok) {
            setError(
                errorMessage(result.data, 'That did not work. Try again.'),
            );

            return;
        }

        updatePoints(result.data.points_balance);
        setConfirming(false);
        setNote('');
        toast.success(
            `${reward.name} is yours! We'll be in touch about collecting it.`,
        );
        router.reload({ only: ['account', 'rewards', 'redemptions'] });
    };

    return (
        <article className="flex h-full flex-col">
            <CoverImage
                src={reward.image_url}
                label={reward.name}
                className="aspect-[4/3] w-full rounded-3xl"
            />
            <div className="mt-4 flex items-start justify-between gap-4">
                <h3 className="font-oz-display text-xl leading-tight font-extrabold">
                    {reward.name}
                </h3>
                <p className="shrink-0 rounded-full bg-oz-black px-3 py-1 text-sm font-bold text-oz-yellow tabular-nums">
                    {reward.points_cost.toLocaleString()} poin
                </p>
            </div>
            {reward.description && (
                <p className="mt-2 text-oz-ink">{reward.description}</p>
            )}
            {reward.stock_left !== null && reward.in_stock && (
                <p className="mt-2 text-sm font-bold text-oz-red">
                    Only {reward.stock_left} left
                </p>
            )}

            <div className="mt-auto pt-4">
                {!reward.in_stock ? (
                    <p className="font-semibold text-oz-ink/70">Out of stock</p>
                ) : !account ? null : confirming ? (
                    <div className="space-y-2 rounded-2xl bg-oz-paper p-4">
                        <p className="font-semibold">
                            Spend {reward.points_cost.toLocaleString()} poin on{' '}
                            {reward.name}?
                        </p>
                        <label className="block text-sm">
                            <span className="text-oz-ink">
                                Anything we should know? (size, colour, …)
                            </span>
                            <input
                                value={note}
                                onChange={(e) => setNote(e.target.value)}
                                maxLength={500}
                                className="mt-1 h-10 w-full rounded-xl border-2 border-oz-black/15 bg-oz-white px-3 outline-none focus:border-oz-black"
                            />
                        </label>
                        {error && (
                            <p
                                role="alert"
                                className="text-sm font-semibold text-oz-red"
                            >
                                {error}
                            </p>
                        )}
                        <div className="flex gap-2">
                            <button
                                type="button"
                                onClick={redeem}
                                disabled={busy}
                                className="rounded-full bg-oz-black px-5 py-2 text-sm font-bold text-oz-white focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none disabled:opacity-60"
                            >
                                {busy ? 'Redeeming…' : 'Redeem'}
                            </button>
                            <button
                                type="button"
                                onClick={() => setConfirming(false)}
                                className="rounded-full px-4 py-2 text-sm font-bold hover:bg-oz-black/5"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                ) : short > 0 ? (
                    <p className="text-sm font-semibold text-oz-ink/70">
                        {short.toLocaleString()} more poin to go
                    </p>
                ) : (
                    <button
                        type="button"
                        onClick={() => {
                            setError(null);
                            setConfirming(true);
                        }}
                        className="rounded-full bg-oz-yellow px-6 py-2.5 font-bold focus-visible:ring-4 focus-visible:ring-oz-black/30 focus-visible:outline-none"
                    >
                        Redeem
                    </button>
                )}
            </div>
        </article>
    );
}

function Leaderboard({ leaderboard }: { leaderboard: Props['leaderboard'] }) {
    const [period, setPeriod] = useState<'week' | 'all'>('week');
    const { account } = useLive();
    const rows = leaderboard[period];

    return (
        <section aria-labelledby="board-title">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <h2
                    id="board-title"
                    className="font-oz-display text-3xl font-extrabold tracking-tight"
                >
                    Leaderboard
                </h2>
                <div
                    className="flex gap-1 rounded-full bg-oz-paper p-1"
                    role="group"
                    aria-label="Period"
                >
                    {(['week', 'all'] as const).map((value) => (
                        <button
                            key={value}
                            type="button"
                            aria-pressed={period === value}
                            onClick={() => setPeriod(value)}
                            className={cn(
                                'rounded-full px-4 py-1.5 text-sm font-bold focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none',
                                period === value
                                    ? 'bg-oz-black text-oz-yellow'
                                    : 'hover:bg-oz-black/5',
                            )}
                        >
                            {value === 'week' ? 'This week' : 'All time'}
                        </button>
                    ))}
                </div>
            </div>
            {rows.length === 0 ? (
                <p className="mt-6 text-oz-ink">
                    Nobody has earned points{' '}
                    {period === 'week' ? 'this week' : 'yet'}. Press play to
                    take the top spot.
                </p>
            ) : (
                <ol className="mt-6 divide-y divide-oz-black/10">
                    {rows.map((row) => (
                        <li
                            key={row.user.id}
                            className={cn(
                                'flex items-center gap-4 py-3',
                                row.user.id === account?.id &&
                                    '-mx-3 rounded-2xl bg-oz-butter px-3',
                            )}
                        >
                            <span
                                className={cn(
                                    'w-8 text-center font-oz-display text-2xl font-extrabold tabular-nums',
                                    row.rank > 3 && 'text-oz-ink/50',
                                )}
                            >
                                {row.rank}
                            </span>
                            <span className="flex-1 truncate font-semibold">
                                {row.user.id === account?.id
                                    ? `${row.user.name} (you)`
                                    : row.user.name}
                            </span>
                            <span className="font-bold tabular-nums">
                                {row.points.toLocaleString()} poin
                            </span>
                        </li>
                    ))}
                </ol>
            )}
        </section>
    );
}
