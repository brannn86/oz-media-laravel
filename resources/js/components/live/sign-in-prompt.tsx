import { useAuthDialog } from '@/components/auth/auth-dialog';
import { cn } from '@/lib/utils';

/**
 * Log in / create account, opening the auth popup so the visitor stays on the
 * page. Used wherever a guest reaches something that needs an account.
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
    const { openAuth } = useAuthDialog();

    return (
        <div className={cn('font-oz', className)}>
            <p className="font-semibold">{title}</p>
            <p className="mt-1 text-sm text-oz-ink">{text}</p>
            <div className="mt-3 flex flex-wrap gap-2">
                <button
                    type="button"
                    onClick={() => openAuth('login')}
                    className="rounded-full bg-oz-black px-5 py-2 text-sm font-bold text-oz-white focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                >
                    Log in
                </button>
                <button
                    type="button"
                    onClick={() => openAuth('register')}
                    className="rounded-full border-2 border-oz-black px-5 py-2 text-sm font-bold focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                >
                    Create account
                </button>
            </div>
        </div>
    );
}
