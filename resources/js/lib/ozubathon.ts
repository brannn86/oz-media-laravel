import type { Ozubathon } from '@/types';

export type OzubathonPhase = 'before' | 'live' | 'after';

export type OzubathonClock = {
    phase: OzubathonPhase;
    /** Before: what's left until the start. */
    countdown: {
        days: number;
        hours: number;
        minutes: number;
        seconds: number;
    };
    /** Live: the hour on air, 1 to `hours`; 0 before, `hours` after. */
    hour: number;
    /** Hours fully done (fills the meter). */
    hoursDone: number;
};

const HOUR = 3_600_000;

export function ozubathonClock(event: Ozubathon, now: number): OzubathonClock {
    const start = Date.parse(event.starts_at);
    const end = Date.parse(event.ends_at);

    if (now < start) {
        const left = Math.max(0, Math.floor((start - now) / 1000));

        return {
            phase: 'before',
            countdown: {
                days: Math.floor(left / 86_400),
                hours: Math.floor((left % 86_400) / 3_600),
                minutes: Math.floor((left % 3_600) / 60),
                seconds: left % 60,
            },
            hour: 0,
            hoursDone: 0,
        };
    }

    const zero = { days: 0, hours: 0, minutes: 0, seconds: 0 };

    if (now >= end) {
        return {
            phase: 'after',
            countdown: zero,
            hour: event.hours,
            hoursDone: event.hours,
        };
    }

    const done = Math.floor((now - start) / HOUR);

    return { phase: 'live', countdown: zero, hour: done + 1, hoursDone: done };
}

const day = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Asia/Jakarta',
    day: 'numeric',
});
const monthYear = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Asia/Jakarta',
    month: 'short',
    year: 'numeric',
});
const time = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Asia/Jakarta',
    hour: '2-digit',
    minute: '2-digit',
});

/** "23–25 Dec 2026" (WIB), as the poster sets it. */
export function ozubathonDates(event: Ozubathon): string {
    const start = new Date(event.starts_at);
    const end = new Date(event.ends_at);
    const sameMonth = monthYear.format(start) === monthYear.format(end);

    return sameMonth
        ? `${day.format(start)}–${day.format(end)} ${monthYear.format(end)}`
        : `${day.format(start)} ${monthYear.format(start)} – ${day.format(end)} ${monthYear.format(end)}`;
}

const startDay = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Asia/Jakarta',
    weekday: 'short',
    day: 'numeric',
    month: 'short',
});

/** "Wed 23 Dec, 00:00 WIB": when it goes live. */
export function ozubathonStart(event: Ozubathon): string {
    const start = new Date(event.starts_at);

    return `${startDay.format(start)}, ${time.format(start)} WIB`;
}
