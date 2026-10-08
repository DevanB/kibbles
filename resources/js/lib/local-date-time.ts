export function browserTimeZone(): string {
    return Intl.DateTimeFormat().resolvedOptions().timeZone;
}

export function toDateTimeLocalValue(iso: string): string {
    const date = new Date(iso);
    const pad = (value: number): string => String(value).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function isSameLocalDay(left: Date, right: Date): boolean {
    return (
        left.getFullYear() === right.getFullYear() &&
        left.getMonth() === right.getMonth() &&
        left.getDate() === right.getDate()
    );
}

function meridem(date: Date): 'AM' | 'PM' {
    return date.getHours() < 12 ? 'AM' : 'PM';
}

function formatDate(date: Date, now: Date): string {
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        ...(date.getFullYear() === now.getFullYear()
            ? {}
            : { year: 'numeric' }),
    });
}

function formatTime(date: Date, includeMeridem: boolean): string {
    const hour12 = date.getHours() % 12 === 0 ? 12 : date.getHours() % 12;
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const time = `${hour12}:${minutes}`;

    if (!includeMeridem) {
        return time;
    }

    return `${time} ${meridem(date)}`;
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
    const sameDay = isSameLocalDay(start, end);
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
