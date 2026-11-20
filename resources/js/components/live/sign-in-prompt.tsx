import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { login, register } from '@/routes';
import google from '@/routes/auth/google';
import type { StationPageProps } from '@/types';

/**
 * Log in / create account / Google, returning to the current page afterwards.
 * Used wherever a guest reaches something that needs an account.
 */
export function SignInPrompt({
    title,
    text,
    className,
}: {
    title: string;
    text: string;
    className?: string;
}) {
    const { url, props } = usePage<StationPageProps>();
    const query = { query: { return: url } };

    return (
        <div className={cn('font-oz', className)}>
            <p className="font-semibold">{title}</p>
            <p className="mt-1 text-sm text-oz-ink">{text}</p>
            <div className="mt-3 flex flex-wrap gap-2">
                <Link
                    href={login.url(query)}
                    className="rounded-full bg-oz-black px-5 py-2 text-sm font-bold text-oz-white focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                >
                    Log in
                </Link>
                <Link
                    href={register.url(query)}
                    className="rounded-full border-2 border-oz-black px-5 py-2 text-sm font-bold focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                >
                    Create account
                </Link>
                {props.chat.google_sign_in && (
                    <a
                        href={google.redirect.url(query)}
                        className="rounded-full border-2 border-oz-black/15 px-5 py-2 text-sm font-bold hover:border-oz-black focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                    >
                        Continue with Google
                    </a>
                )}
            </div>
        </div>
    );
}
