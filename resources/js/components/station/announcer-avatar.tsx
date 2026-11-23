import { cn } from '@/lib/utils';
import type { Announcer } from '@/types';

export function initials(name: string): string {
    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

export function AnnouncerAvatar({
    announcer,
    className,
}: {
    announcer: Pick<Announcer, 'display_name' | 'avatar_url'>;
    className?: string;
}) {
    if (announcer.avatar_url) {
        return (
            <img
                src={announcer.avatar_url}
                alt=""
                className={cn('rounded-full object-cover', className)}
                loading="lazy"
            />
        );
    }

    return (
        <span
            aria-hidden
            className={cn(
                'grid place-items-center rounded-full bg-oz-black font-oz-display font-extrabold text-oz-yellow',
                className,
            )}
        >
            {initials(announcer.display_name)}
        </span>
    );
}

/** Overlapping portraits, e.g. the hosts of a show. */
export function AvatarStack({
    announcers,
    size = 'md',
    className,
}: {
    announcers: Announcer[];
    /** md: the on-air hero (yellow); sm: cards on paper. */
    size?: 'md' | 'sm';
    className?: string;
}) {
    return (
        <span
            className={cn(
                'flex shrink-0',
                size === 'md' ? '-space-x-3' : '-space-x-2',
                className,
            )}
        >
            {announcers.map((announcer) => (
                <AnnouncerAvatar
                    key={announcer.id}
                    announcer={announcer}
                    className={
                        size === 'md'
                            ? 'size-12 text-base ring-4 ring-oz-yellow sm:size-14'
                            : 'size-9 text-xs ring-3 ring-oz-paper'
                    }
                />
            ))}
        </span>
    );
}
