import { Link } from '@inertiajs/react';
import { OzMark } from '@/components/station/oz-mark';
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
                    <OzMark
                        inverted
                        suffix={station.name.replace(/^OZ\s+/i, '')}
                    />
                    {station.tagline && (
                        <p className="mt-5 max-w-xs font-oz-display text-2xl leading-tight font-extrabold text-oz-yellow">
                            {station.tagline}
                        </p>
                    )}
                    <SocialLinks
                        social={station.social}
                        className="mt-8"
                        tone="dark"
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
                                    className="font-semibold hover:text-oz-yellow"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    WhatsApp {contact.whatsapp}
                                </a>
                            </li>
                        )}
                        {contact.phone && (
                            <li>
                                <a
                                    href={`tel:${contact.phone.replace(/\s/g, '')}`}
                                    className="hover:text-oz-yellow"
                                >
                                    {contact.phone}
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
                                {contact.address}
                            </li>
                        )}
                    </ul>
                </div>
            </div>
            <div className="border-t border-oz-white/10">
                <div className="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-sm text-oz-white/50 sm:flex-row sm:items-center sm:justify-between sm:px-6">
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
                    <Link
                        href={stationRoutes.privacy(station.slug)}
                        className="hover:text-oz-white"
                    >
                        Privacy policy
                    </Link>
                </div>
            </div>
        </footer>
    );
}
