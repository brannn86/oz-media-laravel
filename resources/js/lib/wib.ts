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
