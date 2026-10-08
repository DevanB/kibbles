export function browserTimeZone(): string {
    return Intl.DateTimeFormat().resolvedOptions().timeZone;
}

export function toDateTimeLocalValue(iso: string): string {
    const date = new Date(iso);
    const pad = (value: number): string => String(value).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export function formatSessionDateTime(iso: string): string {
    return new Date(iso).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
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
