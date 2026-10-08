import { format, isSameDay, isSameYear } from 'date-fns';

export function browserTimeZone(): string {
    return Intl.DateTimeFormat().resolvedOptions().timeZone;
}

export function toDateTimeLocalValue(iso: string): string {
    return format(new Date(iso), "yyyy-MM-dd'T'HH:mm");
}

function meridem(date: Date): string {
    return format(date, 'a');
}

function formatDate(date: Date, now: Date): string {
    return format(date, isSameYear(date, now) ? 'MMM d' : 'MMM d, yyyy');
}

function formatTime(date: Date, includeMeridem: boolean): string {
    return format(date, includeMeridem ? 'h:mm a' : 'h:mm');
}

export function formatSessionRange(
    startedAt: string,
    endedAt: string | null,
    now: Date = new Date(),
): string {
    const start = new Date(startedAt);

    if (endedAt === null) {
        return `${formatDate(start, now)}, ${formatTime(start, true)} – now`;
    }

    const end = new Date(endedAt);
    const sameDay = isSameDay(start, end);
    const sameMeridem = meridem(start) === meridem(end);

    if (sameDay) {
        return `${formatDate(start, now)}, ${formatTime(start, !sameMeridem)} – ${formatTime(end, true)}`;
    }

    return `${formatDate(start, now)}, ${formatTime(start, true)} – ${formatDate(end, now)}, ${formatTime(end, true)}`;
}

export function formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const remaining = minutes % 60;

    if (hours > 0 && remaining > 0) {
        return `${hours}h ${remaining}m`;
    }

    if (hours > 0) {
        return `${hours}h`;
    }

    return `${remaining}m`;
}

export function formatSessionRow(
    startedAt: string,
    endedAt: string | null,
    durationMinutes: number | null,
    now: Date = new Date(),
): string {
    const range = formatSessionRange(startedAt, endedAt, now);

    if (endedAt === null) {
        return `${range} · Open`;
    }

    if (durationMinutes === null) {
        return range;
    }

    return `${range} · ${formatDuration(durationMinutes)}`;
}
