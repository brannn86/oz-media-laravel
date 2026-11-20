import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { useState } from 'react';
import { OzMark } from '@/components/station/oz-mark';
import {
    Sheet,
    SheetContent,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import stationRoutes from '@/routes/station';
import type { Station } from '@/types';

export function stationNavigation(slug: string) {
    return [
        { label: 'Home', href: stationRoutes.home(slug).url, exact: true },
        { label: 'Programs', href: stationRoutes.programs(slug).url },
        { label: 'Announcers', href: stationRoutes.announcers(slug).url },
        { label: 'Events', href: stationRoutes.events(slug).url },
        { label: 'News', href: stationRoutes.news.index(slug).url },
        { label: 'OZ Poin', href: stationRoutes.rewards(slug).url },
        { label: 'About', href: stationRoutes.about(slug).url },
    ];
}

function isActive(currentUrl: string, href: string, exact = false): boolean {
    const path = new URL(href, 'http://x').pathname;
    const current = currentUrl.split('?')[0];

    return exact ? current === path : current.startsWith(path);
}

export function StationHeader({ station }: { station: Station }) {
    const { url } = usePage();
    const [open, setOpen] = useState(false);
    const items = stationNavigation(station.slug);
    const stationSuffix = station.name.replace(/^OZ\s+/i, '');

    return (
        <header className="sticky top-0 z-30 border-b border-oz-black/10 bg-oz-white/95 backdrop-blur supports-[backdrop-filter]:bg-oz-white/80">
            <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6">
                <Link
                    href={stationRoutes.home(station.slug)}
                    className="rounded-xl focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none"
                    aria-label={`${station.name} home`}
                >
                    <OzMark suffix={stationSuffix} />
                </Link>

                <nav aria-label="Station" className="hidden lg:block">
                    <ul className="flex items-center gap-1">
                        {items.map((item) => {
                            const active = isActive(url, item.href, item.exact);

                            return (
                                <li key={item.href}>
                                    <Link
                                        href={item.href}
                                        aria-current={
                                            active ? 'page' : undefined
                                        }
                                        className={cn(
                                            'rounded-full px-4 py-2 font-oz text-sm font-semibold transition-colors focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none',
                                            active
                                                ? 'bg-oz-yellow text-oz-black'
                                                : 'text-oz-black/70 hover:bg-oz-black/5 hover:text-oz-black',
                                        )}
                                    >
                                        {item.label}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </nav>

                <Sheet open={open} onOpenChange={setOpen}>
                    <SheetTrigger
                        className="grid size-10 place-items-center rounded-full text-oz-black hover:bg-oz-black/5 focus-visible:ring-4 focus-visible:ring-oz-yellow focus-visible:outline-none lg:hidden"
                        aria-label="Open menu"
                    >
                        <Menu className="size-6" />
                    </SheetTrigger>
                    <SheetContent
                        side="right"
                        className="w-[85vw] max-w-sm border-none bg-oz-yellow p-0 font-oz text-oz-black"
                    >
                        <SheetTitle className="sr-only">Menu</SheetTitle>
                        <nav aria-label="Station" className="px-6 pt-16">
                            <ul className="space-y-1">
                                {items.map((item) => (
                                    <li key={item.href}>
                                        <Link
                                            href={item.href}
                                            onClick={() => setOpen(false)}
                                            className="block rounded-xl py-2 font-oz-display text-3xl font-extrabold tracking-tight focus-visible:ring-4 focus-visible:ring-oz-black focus-visible:outline-none"
                                        >
                                            {item.label}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    </SheetContent>
                </Sheet>
            </div>
        </header>
    );
}
