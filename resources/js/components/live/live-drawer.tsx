import { Coins, X } from 'lucide-react';
import { useChat } from '@/components/chat/chat-context';
import { ChatPanel } from '@/components/chat/chat-panel';
import { useLive } from '@/components/live/live-context';
import type { DrawerTab } from '@/components/live/live-context';
import { RequestsPanel } from '@/components/live/requests-panel';
import { cn } from '@/lib/utils';

const TABS: { id: DrawerTab; label: string }[] = [
    { id: 'chat', label: 'Chat' },
    { id: 'requests', label: 'Request a song' },
];

/**
 * The player's side drawer: live chat and song requests. Right-hand panel on
 * desktop, full screen above the player bar on phones. Listening carries on
 * while it is open.
 */
export function LiveDrawer() {
    const live = useLive();
    const { unread } = useChat();

    if (!live.drawerOpen) {
        return null;
    }

    return (
        <section
            aria-label="Live chat and song requests"
            className="fixed inset-x-0 top-0 bottom-(--oz-player-bar) z-40 flex flex-col bg-oz-white font-oz text-oz-black shadow-2xl motion-safe:animate-in motion-safe:slide-in-from-right-8 sm:top-16 sm:left-auto sm:w-[24rem] sm:border-l sm:border-oz-black/10"
        >
            <header className="flex items-center justify-between gap-2 border-b border-oz-black/10 px-3 py-2">
                <div role="tablist" aria-label="Drawer" className="flex gap-1">
                    {TABS.map((tab) => (
                        <button
                            key={tab.id}
                            type="button"
                            role="tab"
                            id={`drawer-tab-${tab.id}`}
                            aria-selected={live.drawerTab === tab.id}
                            aria-controls="drawer-panel"
                            onClick={() => live.openDrawer(tab.id)}
                            className={cn(
                                'relative rounded-full px-4 py-2 text-sm font-bold transition-colors focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none',
                                live.drawerTab === tab.id
                                    ? 'bg-oz-black text-oz-yellow'
                                    : 'hover:bg-oz-black/5',
                            )}
                        >
                            {tab.label}
                            {tab.id === 'chat' &&
                                unread > 0 &&
                                live.drawerTab !== 'chat' && (
                                    <span className="ml-1.5 rounded-full bg-oz-red px-1.5 text-[0.7rem] text-oz-white">
                                        {unread}
                                    </span>
                                )}
                        </button>
                    ))}
                </div>
                <div className="flex items-center gap-1">
                    {live.account && (
                        <span
                            className="inline-flex items-center gap-1 rounded-full bg-oz-butter px-2.5 py-1 text-xs font-extrabold tabular-nums"
                            title="Your OZ Poin"
                        >
                            <Coins className="size-3.5" aria-hidden />
                            {live.account.points_balance.toLocaleString()}
                        </span>
                    )}
                    <button
                        type="button"
                        onClick={live.closeDrawer}
                        aria-label="Close drawer"
                        className="grid size-10 place-items-center rounded-full hover:bg-oz-black/5 focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                    >
                        <X className="size-5" />
                    </button>
                </div>
            </header>

            <div
                id="drawer-panel"
                role="tabpanel"
                aria-labelledby={`drawer-tab-${live.drawerTab}`}
                className="flex min-h-0 flex-1 flex-col"
            >
                {live.drawerTab === 'chat' ? <ChatPanel /> : <RequestsPanel />}
            </div>
        </section>
    );
}
