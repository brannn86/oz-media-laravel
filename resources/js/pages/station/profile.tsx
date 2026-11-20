import { Form, Head, Link, usePage } from '@inertiajs/react';
import { ArrowUpRight, Coins, Flame, ShieldCheck } from 'lucide-react';
import type { ReactNode } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { useLive } from '@/components/live/live-context';
import PasswordInput from '@/components/password-input';
import { ListenerAvatar } from '@/components/station/account-menu';
import {
    OzField,
    ozInputClass,
    ozPrimaryButtonClass,
    ozSecondaryButtonClass,
    ozTextButtonClass,
} from '@/components/station/oz-field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { formatDate } from '@/lib/wib';
import { edit as editSecurity } from '@/routes/security';
import stationRoutes from '@/routes/station';
import { update as updatePassword } from '@/routes/user-password';
import { send as sendVerification } from '@/routes/verification';
import type { StationPageProps } from '@/types';

type Props = StationPageProps & {
    profile: {
        member_since: string | null;
        email_verified: boolean;
        two_factor_enabled: boolean;
    };
    passwordRules: string;
    status?: string;
};

export default function StationProfile() {
    const { station, meta, profile, passwordRules, status } =
        usePage<Props>().props;
    const { account } = useLive();

    // The route requires a session; this only guards the moment after logout.
    if (!account) {
        return <Head title={meta.title} />;
    }

    return (
        <>
            <Head title={meta.title} />

            <div className="border-b border-oz-black/10">
                <div className="mx-auto flex max-w-7xl flex-col gap-8 px-4 pt-14 pb-10 sm:px-6 sm:pt-20 md:flex-row md:items-end md:justify-between">
                    <div className="flex items-center gap-5 sm:gap-7">
                        <ListenerAvatar
                            account={account}
                            className="size-20 text-2xl sm:size-28 sm:text-4xl"
                        />
                        <div className="min-w-0">
                            <p className="text-sm font-bold text-oz-ink">
                                Your profile
                            </p>
                            <h1 className="mt-1 font-oz-display text-4xl leading-[0.95] font-extrabold tracking-tight break-words sm:text-6xl">
                                {account.name}
                            </h1>
                            <p className="mt-2 text-oz-ink">
                                {account.email}
                                {profile.member_since && (
                                    <>
                                        {' · '}Member since{' '}
                                        {formatDate(profile.member_since)}
                                    </>
                                )}
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <Link
                            href={stationRoutes.rewards(station.slug)}
                            className="inline-flex items-center gap-3 rounded-full bg-oz-yellow px-6 py-3 transition-transform hover:scale-[1.02] focus-visible:ring-4 focus-visible:ring-oz-black/30 focus-visible:outline-none"
                        >
                            <Coins className="size-6" aria-hidden />
                            <span className="font-oz-display text-3xl font-extrabold tabular-nums">
                                {account.points_balance.toLocaleString()}
                            </span>
                            <span className="font-semibold">poin</span>
                            <ArrowUpRight className="size-5" aria-hidden />
                        </Link>
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
                </div>
            </div>

            <section aria-labelledby="settings-title" className="bg-oz-paper">
                <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6">
                    <h2
                        id="settings-title"
                        className="font-oz-display text-3xl font-extrabold tracking-tight"
                    >
                        Settings
                    </h2>

                    <div className="mt-8 grid items-start gap-6 lg:grid-cols-2">
                        <SettingsCard
                            title="Profile details"
                            text="Your name shows next to your chat messages and song requests."
                        >
                            <Form
                                {...ProfileController.update.form()}
                                options={{ preserveScroll: true }}
                                className="grid gap-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <OzField
                                            id="profile-name"
                                            label="Name"
                                            error={errors.name}
                                        >
                                            <Input
                                                id="profile-name"
                                                name="name"
                                                defaultValue={account.name}
                                                required
                                                autoComplete="name"
                                                className={ozInputClass}
                                            />
                                        </OzField>
                                        <OzField
                                            id="profile-email"
                                            label="Email address"
                                            error={errors.email}
                                        >
                                            <Input
                                                id="profile-email"
                                                type="email"
                                                name="email"
                                                defaultValue={account.email}
                                                required
                                                autoComplete="username"
                                                className={ozInputClass}
                                            />
                                        </OzField>
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className={cn(
                                                ozPrimaryButtonClass,
                                                'justify-self-start',
                                            )}
                                            data-test="update-profile-button"
                                        >
                                            {processing && <Spinner />}
                                            Save changes
                                        </button>
                                    </>
                                )}
                            </Form>

                            {!profile.email_verified && (
                                <div className="mt-6 rounded-2xl bg-oz-butter px-4 py-3 text-sm">
                                    <p className="font-semibold">
                                        Your email address isn't verified yet.
                                    </p>
                                    {status === 'verification-link-sent' ? (
                                        <p className="mt-1">
                                            A new verification link is on its
                                            way to your inbox.
                                        </p>
                                    ) : (
                                        <Link
                                            href={sendVerification()}
                                            as="button"
                                            preserveScroll
                                            className={cn(
                                                ozTextButtonClass,
                                                'mt-1',
                                            )}
                                        >
                                            Resend the verification email
                                        </Link>
                                    )}
                                </div>
                            )}
                        </SettingsCard>

                        <div className="grid gap-6">
                            <SettingsCard
                                title="Password"
                                text={
                                    account.has_password &&
                                    profile.email_verified
                                        ? "A long password you don't use anywhere else is the strongest."
                                        : undefined
                                }
                            >
                                <PasswordSettings
                                    hasPassword={account.has_password}
                                    emailVerified={profile.email_verified}
                                    passwordRules={passwordRules}
                                />
                            </SettingsCard>

                            {account.has_password && profile.email_verified && (
                                <SettingsCard
                                    title="Security"
                                    text="Add two-factor authentication or a passkey for a safer, faster log in."
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-4">
                                        <p className="inline-flex items-center gap-2 font-semibold">
                                            <ShieldCheck
                                                className={cn(
                                                    'size-5',
                                                    profile.two_factor_enabled
                                                        ? 'text-oz-black'
                                                        : 'text-oz-black/30',
                                                )}
                                                aria-hidden
                                            />
                                            Two-factor is{' '}
                                            {profile.two_factor_enabled
                                                ? 'on'
                                                : 'off'}
                                        </p>
                                        <a
                                            href={editSecurity.url()}
                                            className={ozSecondaryButtonClass}
                                        >
                                            Manage security
                                        </a>
                                    </div>
                                </SettingsCard>
                            )}
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}

function SettingsCard({
    title,
    text,
    children,
}: {
    title: string;
    text?: string;
    children: ReactNode;
}) {
    return (
        <section className="rounded-[2rem] bg-oz-white p-6 sm:p-8">
            <h3 className="font-oz-display text-2xl font-extrabold tracking-tight">
                {title}
            </h3>
            {text && <p className="mt-1 text-oz-ink">{text}</p>}
            <div className="mt-6">{children}</div>
        </section>
    );
}

function PasswordSettings({
    hasPassword,
    emailVerified,
    passwordRules,
}: {
    hasPassword: boolean;
    emailVerified: boolean;
    passwordRules: string;
}) {
    if (!hasPassword) {
        return (
            <p className="text-oz-ink">
                You log in with Google, so there's no password to change here.
            </p>
        );
    }

    if (!emailVerified) {
        return (
            <p className="text-oz-ink">
                Verify your email address first, then you can change your
                password here.
            </p>
        );
    }

    return (
        <Form
            {...updatePassword.form()}
            options={{ preserveScroll: true }}
            resetOnSuccess
            resetOnError
            className="grid gap-4"
        >
            {({ processing, errors }) => (
                <>
                    <OzField
                        id="current_password"
                        label="Current password"
                        error={errors.current_password}
                    >
                        <PasswordInput
                            id="current_password"
                            name="current_password"
                            required
                            autoComplete="current-password"
                            className={ozInputClass}
                        />
                    </OzField>
                    <OzField
                        id="password"
                        label="New password"
                        error={errors.password}
                    >
                        <PasswordInput
                            id="password"
                            name="password"
                            required
                            autoComplete="new-password"
                            passwordrules={passwordRules}
                            className={ozInputClass}
                        />
                    </OzField>
                    <OzField
                        id="password_confirmation"
                        label="Confirm new password"
                        error={errors.password_confirmation}
                    >
                        <PasswordInput
                            id="password_confirmation"
                            name="password_confirmation"
                            required
                            autoComplete="new-password"
                            passwordrules={passwordRules}
                            className={ozInputClass}
                        />
                    </OzField>
                    <button
                        type="submit"
                        disabled={processing}
                        className={cn(
                            ozPrimaryButtonClass,
                            'justify-self-start',
                        )}
                        data-test="update-password-button"
                    >
                        {processing && <Spinner />}
                        Update password
                    </button>
                </>
            )}
        </Form>
    );
}
