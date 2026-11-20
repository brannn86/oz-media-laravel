import { createContext, useCallback, useContext, useState } from 'react';
import type { ReactNode } from 'react';
import type { Account } from '@/types';

export type DrawerTab = 'chat' | 'requests';

type LiveApi = {
    /** The side drawer holding Chat and Requests. */
    drawerOpen: boolean;
    drawerTab: DrawerTab;
    openDrawer: (tab: DrawerTab) => void;
    closeDrawer: () => void;
    /** The signed-in listener, kept fresh by heartbeats, votes and redemptions. */
    account: Account | null;
    updatePoints: (balance: number, streakDays?: number) => void;
};

const LiveContext = createContext<LiveApi | null>(null);

/**
 * Drawer and OZ Poin state for the station site. Lives in StationLayout so
 * both survive page navigation.
 */
export function LiveProvider({
    account: accountProp,
    children,
}: {
    account: Account | null;
    children: ReactNode;
}) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [drawerTab, setDrawerTab] = useState<DrawerTab>('chat');

    // Follow the server's copy when it changes (login, page reload), but keep
    // optimistic point updates in between.
    const [accountProps, setAccountProps] = useState(accountProp);
    const [account, setAccount] = useState(accountProp);

    if (accountProps !== accountProp) {
        setAccountProps(accountProp);
        setAccount(accountProp);
    }

    const openDrawer = useCallback((tab: DrawerTab) => {
        setDrawerTab(tab);
        setDrawerOpen(true);
    }, []);

    const closeDrawer = useCallback(() => setDrawerOpen(false), []);

    const updatePoints = useCallback(
        (balance: number, streakDays?: number) =>
            setAccount((current) =>
                current
                    ? {
                          ...current,
                          points_balance: balance,
                          current_streak_days:
                              streakDays ?? current.current_streak_days,
                      }
                    : current,
            ),
        [],
    );

    return (
        <LiveContext.Provider
            value={{
                drawerOpen,
                drawerTab,
                openDrawer,
                closeDrawer,
                account,
                updatePoints,
            }}
        >
            {children}
        </LiveContext.Provider>
    );
}

export function useLive(): LiveApi {
    const live = useContext(LiveContext);

    if (!live) {
        throw new Error('useLive() must be used inside <LiveProvider>.');
    }

    return live;
}
