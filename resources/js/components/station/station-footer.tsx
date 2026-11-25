import { Link } from '@inertiajs/react';
import { BrandIcon } from '@/components/station/brand-icon';
import { BrandLogo } from '@/components/station/brand-logo';
import { SocialLinks } from '@/components/station/social-links';
import { stationNavigation } from '@/components/station/station-header';
import { home } from '@/routes';
import stationRoutes from '@/routes/station';
import type { Station } from '@/types';

export function StationFooter({ station }: { station: Station }) {
    const { contact } = station;

    return (
        <footer className="bg-oz-black font-oz text-oz-white">
            <div className="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 md:grid-cols-[1.4fr_1fr_1fr]">
                <div>
                    <BrandLogo brand={station.slug} onDark className="h-20" />
                    {station.tagline && (
                        <p className="mt-5 max-w-xs font-oz-display text-2xl leading-tight font-extrabold text-oz-yellow">
                            {station.tagline}
                        </p>
                    )}
                    <SocialLinks
                        social={station.social}
                        className="mt-8"
                        tone="dark"
                        variant="icons"
                    />
                </div>

                <nav aria-label="Footer">
                    <h2 className="text-sm font-semibold text-oz-white/50">
                        Explore
                    </h2>
                    <ul className="mt-4 space-y-2">
                        {stationNavigation(station.slug).map((item) => (
                            <li key={item.href}>
                                <Link
                                    href={item.href}
                                    className="font-semibold hover:text-oz-yellow"
                                >
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                        <li>
                            <Link
                                href={stationRoutes.privacy(station.slug)}
                                className="font-semibold hover:text-oz-yellow"
                            >
                                Privacy policy
                            </Link>
                        </li>
                    </ul>
                </nav>

                <div>
                    <h2 className="text-sm font-semibold text-oz-white/50">
                        Get in touch
                    </h2>
                    <ul className="mt-4 space-y-2 text-oz-white/90">
                        {contact.whatsapp && (
                            <li>
                                <a
                                    href={`https://wa.me/${contact.whatsapp.replace(/\D/g, '')}`}
                                    className="inline-flex items-center gap-2 font-semibold hover:text-oz-yellow"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <BrandIcon brand="whatsapp" />
                                    WhatsApp {contact.whatsapp}
                                </a>
                            </li>
                        )}
                        {contact.email && (
                            <li>
                                <a
                                    href={`mailto:${contact.email}`}
                                    className="hover:text-oz-yellow"
                                >
                                    {contact.email}
                                </a>
                            </li>
                        )}
                        {contact.address && (
                            <li className="pt-2 text-sm leading-relaxed whitespace-pre-line text-oz-white/60">
                                {contact.map_url ? (
                                    <a
                                        href={contact.map_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="underline decoration-oz-white/30 underline-offset-4 hover:text-oz-yellow hover:decoration-oz-yellow"
                                    >
                                        {contact.address}
                                        <span className="sr-only">
                                            {' '}
                                            (open in maps)
                                        </span>
                                    </a>
                                ) : (
                                    contact.address
                                )}
                            </li>
                        )}
                    </ul>
                </div>
            </div>
            <div className="border-t border-oz-white/10">
                <div className="mx-auto max-w-7xl px-4 py-6 text-sm text-oz-white/50 sm:px-6">
                    <p>
                        {station.name} is part of{' '}
                        <Link
                            href={home()}
                            className="font-semibold text-oz-white hover:text-oz-yellow"
                        >
                            OZ Media
                        </Link>
                        .
                    </p>
                </div>
            </div>
        </footer>
    );
}
