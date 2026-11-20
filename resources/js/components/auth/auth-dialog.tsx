import { Form, usePage } from '@inertiajs/react';
import { createContext, useCallback, useContext, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import PasswordInput from '@/components/password-input';
import { OzMark } from '@/components/station/oz-mark';
import {
    OzField,
    ozInputClass,
    ozPrimaryButtonClass,
    ozSecondaryButtonClass,
    ozTextButtonClass,
} from '@/components/station/oz-field';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { store as loginStore } from '@/routes/login';
import { store as registerStore } from '@/routes/register';
import google from '@/routes/auth/google';

export type AuthMode = 'login' | 'register' | 'forgot';

type AuthDialogApi = {
    openAuth: (mode?: AuthMode) => void;
};

const AuthDialogContext = createContext<AuthDialogApi | null>(null);

export function useAuthDialog(): AuthDialogApi {
    const context = useContext(AuthDialogContext);

    if (!context) {
        throw new Error('useAuthDialog must be used inside AuthDialogProvider');
    }

    return context;
}

const copy: Record<AuthMode, { title: string; text: string }> = {
    login: {
        title: 'Welcome back.',
        text: 'Log in to chat, request songs and collect OZ Poin.',
    },
    register: {
        title: 'Join the crew.',
        text: 'Create a free account to chat, request songs and collect OZ Poin.',
    },
    forgot: {
        title: 'Forgot your password?',
        text: "Enter your email and we'll send you a link to reset it.",
    },
};

/**
 * One popup for log in, sign up and forgot password on the station site.
 * Lives in StationLayout, so it survives the redirect after a failed attempt
 * (errors show in place) and the visitor never leaves the page they are on.
 */
export function AuthDialogProvider({
    stationName,
    googleSignIn,
    children,
}: {
    stationName: string;
    googleSignIn: boolean;
    children: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const [mode, setMode] = useState<AuthMode>('login');

    const openAuth = useCallback((next: AuthMode = 'login') => {
        setMode(next);
        setOpen(true);
    }, []);

    const close = useCallback(() => setOpen(false), []);

    return (
        <AuthDialogContext.Provider value={{ openAuth }}>
            {children}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent
                    className="max-h-[calc(100dvh-2rem)] gap-0 overflow-y-auto rounded-[2rem] border-none bg-oz-white p-0 font-oz text-oz-black sm:max-w-md"
                    onOpenAutoFocus={(event) => {
                        // Focus the first field, not the close button.
                        event.preventDefault();
                        (event.currentTarget as HTMLElement)
                            .querySelector<HTMLInputElement>(
                                'input:not([type=hidden])',
                            )
                            ?.focus();
                    }}
                >
                    <div className="bg-oz-yellow px-7 pt-7 pb-6 sm:px-8">
                        <OzMark suffix={stationName.replace(/^OZ\s+/i, '')} />
                        <DialogTitle className="mt-6 font-oz-display text-3xl leading-none font-extrabold tracking-tight">
                            {copy[mode].title}
                        </DialogTitle>
                        <DialogDescription className="mt-2 text-oz-black/75">
                            {copy[mode].text}
                        </DialogDescription>
                    </div>

                    <div className="px-7 pt-6 pb-7 sm:px-8">
                        {mode === 'login' && (
                            <LoginForm
                                googleSignIn={googleSignIn}
                                onSuccess={close}
                                onModeChange={setMode}
                            />
                        )}
                        {mode === 'register' && (
                            <RegisterForm
                                googleSignIn={googleSignIn}
                                onSuccess={close}
                                onModeChange={setMode}
                            />
                        )}
                        {mode === 'forgot' && (
                            <ForgotPasswordForm onModeChange={setMode} />
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </AuthDialogContext.Provider>
    );
}

type FormProps = {
    googleSignIn: boolean;
    onSuccess: () => void;
    onModeChange: (mode: AuthMode) => void;
};

/** Where to come back to after signing in (read by RememberReturnPath). */
function useReturnPath(): string {
    return usePage().url;
}

function LoginForm({ googleSignIn, onSuccess, onModeChange }: FormProps) {
    const returnPath = useReturnPath();

    return (
        <>
            {googleSignIn && <GoogleButton returnPath={returnPath} />}
            <Form
                {...loginStore.form()}
                options={{ preserveScroll: true }}
                resetOnError={['password']}
                onSuccess={onSuccess}
                className="grid gap-4"
            >
                {({ processing, errors }) => (
                    <>
                        <input type="hidden" name="return" value={returnPath} />
                        <OzField
                            id="auth-email"
                            label="Email address"
                            error={errors.email}
                        >
                            <Input
                                id="auth-email"
                                type="email"
                                name="email"
                                required
                                autoComplete="email"
                                placeholder="you@example.com"
                                aria-invalid={Boolean(errors.email)}
                                className={ozInputClass}
                            />
                        </OzField>
                        <OzField
                            id="auth-password"
                            label="Password"
                            error={errors.password}
                            aside={
                                <button
                                    type="button"
                                    onClick={() => onModeChange('forgot')}
                                    className={cn(ozTextButtonClass, 'text-sm')}
                                >
                                    Forgot password?
                                </button>
                            }
                        >
                            <PasswordInput
                                id="auth-password"
                                name="password"
                                required
                                autoComplete="current-password"
                                placeholder="Your password"
                                aria-invalid={Boolean(errors.password)}
                                className={ozInputClass}
                            />
                        </OzField>
                        <label className="flex items-center gap-2.5 text-sm font-semibold">
                            <input
                                type="checkbox"
                                name="remember"
                                defaultChecked
                                className="size-4 accent-oz-black"
                            />
                            Keep me logged in
                        </label>
                        <button
                            type="submit"
                            disabled={processing}
                            className={cn(ozPrimaryButtonClass, 'mt-1 w-full')}
                            data-test="popup-login-button"
                        >
                            {processing && <Spinner />}
                            Log in
                        </button>
                    </>
                )}
            </Form>
            <SwitchPrompt
                text="No account?"
                action="Sign up"
                onClick={() => onModeChange('register')}
            />
        </>
    );
}

function RegisterForm({ googleSignIn, onSuccess, onModeChange }: FormProps) {
    const returnPath = useReturnPath();

    return (
        <>
            {googleSignIn && <GoogleButton returnPath={returnPath} />}
            <Form
                {...registerStore.form()}
                options={{ preserveScroll: true }}
                resetOnError={['password', 'password_confirmation']}
                onSuccess={onSuccess}
                className="grid gap-4"
            >
                {({ processing, errors }) => (
                    <>
                        <input type="hidden" name="return" value={returnPath} />
                        <OzField
                            id="auth-name"
                            label="Name"
                            error={errors.name}
                        >
                            <Input
                                id="auth-name"
                                name="name"
                                required
                                autoComplete="name"
                                placeholder="Your name"
                                aria-invalid={Boolean(errors.name)}
                                className={ozInputClass}
                            />
                        </OzField>
                        <OzField
                            id="auth-email"
                            label="Email address"
                            error={errors.email}
                        >
                            <Input
                                id="auth-email"
                                type="email"
                                name="email"
                                required
                                autoComplete="email"
                                placeholder="you@example.com"
                                aria-invalid={Boolean(errors.email)}
                                className={ozInputClass}
                            />
                        </OzField>
                        <OzField
                            id="auth-password"
                            label="Password"
                            error={errors.password}
                        >
                            <PasswordInput
                                id="auth-password"
                                name="password"
                                required
                                autoComplete="new-password"
                                placeholder="Create a password"
                                aria-invalid={Boolean(errors.password)}
                                className={ozInputClass}
                            />
                        </OzField>
                        <OzField
                            id="auth-password-confirmation"
                            label="Confirm password"
                            error={errors.password_confirmation}
                        >
                            <PasswordInput
                                id="auth-password-confirmation"
                                name="password_confirmation"
                                required
                                autoComplete="new-password"
                                placeholder="Type it again"
                                aria-invalid={Boolean(
                                    errors.password_confirmation,
                                )}
                                className={ozInputClass}
                            />
                        </OzField>
                        <button
                            type="submit"
                            disabled={processing}
                            className={cn(ozPrimaryButtonClass, 'mt-1 w-full')}
                            data-test="popup-register-button"
                        >
                            {processing && <Spinner />}
                            Create account
                        </button>
                    </>
                )}
            </Form>
            <SwitchPrompt
                text="Already have an account?"
                action="Log in"
                onClick={() => onModeChange('login')}
            />
        </>
    );
}

/** UI only for now: sending reset links from the popup comes later. */
function ForgotPasswordForm({
    onModeChange,
}: {
    onModeChange: (mode: AuthMode) => void;
}) {
    const [submitted, setSubmitted] = useState(false);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setSubmitted(true);
    };

    return (
        <>
            <form onSubmit={submit} className="grid gap-4">
                <OzField id="auth-reset-email" label="Email address">
                    <Input
                        id="auth-reset-email"
                        type="email"
                        name="email"
                        required
                        autoComplete="email"
                        placeholder="you@example.com"
                        className={ozInputClass}
                    />
                </OzField>
                {submitted && (
                    <p
                        role="status"
                        className="rounded-2xl bg-oz-butter px-4 py-3 text-sm font-semibold"
                    >
                        Password reset is coming soon. Until then, contact the
                        studio and we'll help you get back in.
                    </p>
                )}
                <button
                    type="submit"
                    className={cn(ozPrimaryButtonClass, 'w-full')}
                >
                    Send reset link
                </button>
            </form>
            <SwitchPrompt
                text="Remembered it?"
                action="Back to log in"
                onClick={() => onModeChange('login')}
            />
        </>
    );
}

function SwitchPrompt({
    text,
    action,
    onClick,
}: {
    text: string;
    action: string;
    onClick: () => void;
}) {
    return (
        <p className="mt-6 text-center text-sm text-oz-ink">
            {text}{' '}
            <button
                type="button"
                onClick={onClick}
                className={ozTextButtonClass}
            >
                {action}
            </button>
        </p>
    );
}

function GoogleButton({ returnPath }: { returnPath: string }) {
    return (
        <>
            <a
                href={google.redirect.url({ query: { return: returnPath } })}
                className={cn(ozSecondaryButtonClass, 'w-full')}
            >
                <svg aria-hidden viewBox="0 0 24 24" className="size-5">
                    <path
                        fill="#4285F4"
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z"
                    />
                    <path
                        fill="#34A853"
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23z"
                    />
                    <path
                        fill="#FBBC05"
                        d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.18a11 11 0 0 0 0 9.88l3.66-2.84z"
                    />
                    <path
                        fill="#EA4335"
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15A10.95 10.95 0 0 0 12 1 11 11 0 0 0 2.18 7.06l3.66 2.84C6.71 7.31 9.14 5.38 12 5.38z"
                    />
                </svg>
                Continue with Google
            </a>
            <div className="my-5 flex items-center gap-3 text-xs font-semibold text-oz-black/50">
                <span className="h-px flex-1 bg-oz-black/10" />
                or use your email
                <span className="h-px flex-1 bg-oz-black/10" />
            </div>
        </>
    );
}
