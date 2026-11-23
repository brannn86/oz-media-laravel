/**
 * Date/time formatting in station local time (WIB), whatever the visitor's timezone.
 */

export const STATION_TIMEZONE = 'Asia/Jakarta';

const locale = 'en-GB';

const timeFormat = new Intl.DateTimeFormat(locale, {
    timeZone: STATION_TIMEZONE,
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
});

const dateFormat = new Intl.DateTimeFormat(locale, {
    timeZone: STATION_TIMEZONE,
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const shortDateFormat = new Intl.DateTimeFormat(locale, {
    timeZone: STATION_TIMEZONE,
    day: 'numeric',
    month: 'short',
});

const weekdayFormat = new Intl.DateTimeFormat(locale, {
    timeZone: STATION_TIMEZONE,
    weekday: 'long',
});

const dayIndexFormat = new Intl.DateTimeFormat('en-US', {
    timeZone: STATION_TIMEZONE,
    weekday: 'short',
});

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

export function formatTime(iso: string): string {
    return timeFormat.format(new Date(iso));
}

export function formatDate(iso: string): string {
    return dateFormat.format(new Date(iso));
}

export function formatShortDate(iso: string): string {
    return shortDateFormat.format(new Date(iso));
}

export function formatWeekday(iso: string): string {
    return weekdayFormat.format(new Date(iso));
}

/** Today's day index (0 = Sunday) in WIB. */
export function todayInWib(now: Date = new Date()): number {
    return WEEKDAYS.indexOf(dayIndexFormat.format(now));
}

/** "06:00 – 10:00" with 00:00 shown as 24:00 for end times. */
export function slotRange(start: string, end: string): string {
    return `${start} – ${end === '00:00' ? '24:00' : end}`;
}

/** Monday first, the way listeners think about the week. */
const WEEK_ORDER = [1, 2, 3, 4, 5, 6, 0];

/**
 * A program's week as readable lines, one per time slot:
 * "Mon – Fri · 06:00 – 10:00", "Sat, Sun · 08:00 – 11:00".
 */
export function scheduleSummary(
    slots: { day: number; start: string; end: string }[],
): string[] {
    const byTime = new Map<string, number[]>();

    for (const slot of slots) {
        const key = `${slot.start}|${slot.end}`;
        byTime.set(key, [...(byTime.get(key) ?? []), slot.day]);
    }

    return [...byTime.entries()]
        .map(([key, days]) => {
            const [start, end] = key.split('|');
            const ordered = WEEK_ORDER.filter((day) => days.includes(day));

            return {
                first: WEEK_ORDER.indexOf(ordered[0]),
                start,
                line: `${dayList(ordered)} · ${slotRange(start, end)}`,
            };
        })
        .sort((a, b) => a.first - b.first || a.start.localeCompare(b.start))
        .map((entry) => entry.line);
}

/** Consecutive days collapse into a range: [1,2,3,4,5] → "Mon – Fri". */
function dayList(days: number[]): string {
    if (days.length === 7) {
        return 'Every day';
    }

    const positions = days.map((day) => WEEK_ORDER.indexOf(day));
    const consecutive = positions.every(
        (position, i) => i === 0 || position === positions[i - 1] + 1,
    );

    if (consecutive && days.length >= 3) {
        return `${WEEKDAYS[days[0]]} – ${WEEKDAYS[days[days.length - 1]]}`;
    }

    return days.map((day) => WEEKDAYS[day]).join(', ');
}
