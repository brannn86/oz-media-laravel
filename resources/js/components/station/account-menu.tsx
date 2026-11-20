import { Link, usePage } from '@inertiajs/react';
import { LogOut, UserRound } from 'lucide-react';
import { useAuthDialog } from '@/components/auth/auth-dialog';
import { useLive } from '@/components/live/live-context';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import stationRoutes from '@/routes/station';
import type { Account } from '@/types';

export function ListenerAvatar({
    account,
    className,
}: {
    account: Pick<Account, 'name' | 'avatar_url'>;
    className?: string;
}) {
    const getInitials = useInitials();

    return (
        <Avatar className={cn('size-10', className)}>
            {account.avatar_url && (
                <AvatarImage
                    src={account.avatar_url}
                    alt=""
                    referrerPolicy="no-referrer"
                />
            )}
            <AvatarFallback className="bg-oz-black font-oz-display font-extrabold text-oz-yellow">
                {getInitials(account.name)}
            </AvatarFallback>
        </Avatar>
    );
}

/**
 * Top-right of the station header: Log in / Sign up for guests (opening the
 * auth popup), or the listener's avatar with Profile and Log out.
 */
export function AccountMenu({ stationSlug }: { stationSlug: string }) {
    const { account } = useLive();
    const { openAuth } = useAuthDialog();
    const { url } = usePage();

    if (!account) {
        return (
            <div className="flex items-center gap-1.5">
                <button
                    type="button"
                    onClick={() => openAuth('login')}
                    className="rounded-full px-4 py-2 text-sm font-semibold text-oz-black/70 transition-colors hover:bg-oz-black/5 hover:text-oz-black focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                >
                    Log in
                </button>
                <button
                    type="button"
                    onClick={() => openAuth('register')}
                    className="hidden rounded-full bg-oz-black px-4 py-2 text-sm font-bold text-oz-white transition-transform hover:scale-[1.03] focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none sm:block"
                >
                    Sign up
                </button>
            </div>
        );
    }

    const profileUrl = stationRoutes.profile(stationSlug).url;
    // The profile page needs an account, so log out from there to the station home.
    const afterLogout = url.split('?')[0].startsWith(profileUrl)
        ? stationRoutes.home(stationSlug).url
        : url;

    return (
        <DropdownMenu modal={false}>
            <DropdownMenuTrigger
                className="rounded-full ring-2 ring-transparent transition-shadow hover:ring-oz-yellow focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none data-[state=open]:ring-oz-yellow"
                aria-label="Your account"
                data-test="account-menu-trigger"
            >
                <ListenerAvatar account={account} />
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="end"
                sideOffset={10}
                className="w-64 rounded-2xl border-oz-black/10 bg-oz-white p-2 font-oz text-oz-black shadow-xl"
            >
                <DropdownMenuLabel className="flex items-center gap-3 px-2 py-2 font-normal">
                    <ListenerAvatar account={account} className="size-9" />
                    <span className="min-w-0">
                        <span className="block truncate font-bold">
                            {account.name}
                        </span>
                        <span className="block truncate text-xs text-oz-ink">
                            {account.points_balance.toLocaleString()} OZ Poin
                        </span>
                    </span>
                </DropdownMenuLabel>
                <DropdownMenuSeparator className="bg-oz-black/10" />
                <DropdownMenuItem
                    asChild
                    className="cursor-pointer rounded-xl px-3 py-2.5 font-semibold focus:bg-oz-yellow focus:text-oz-black"
                >
                    <Link href={profileUrl}>
                        <UserRound className="size-4 text-current" />
                        Profile
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem
                    asChild
                    className="cursor-pointer rounded-xl px-3 py-2.5 font-semibold focus:bg-oz-black/5 focus:text-oz-black"
                >
                    <Link
                        href={logout()}
                        data={{ return: afterLogout }}
                        as="button"
                        className="w-full"
                        data-test="logout-button"
                    >
                        <LogOut className="size-4 text-current" />
                        Log out
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
