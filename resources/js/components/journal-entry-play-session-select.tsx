import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { formatSessionRange } from '@/lib/local-date-time';
import type { PlaySession } from '@/types';

export default function JournalEntryPlaySessionSelect({
    sessions,
    defaultValue = '',
    error,
}: {
    sessions: PlaySession[];
    defaultValue?: string;
    error?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor="play_session_id">Play session</Label>
            <select
                id="play_session_id"
                name="play_session_id"
                defaultValue={defaultValue === '' ? 'none' : defaultValue}
                data-test="journal-entry-play-session"
                className="flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm dark:bg-input/30 dark:aria-invalid:ring-destructive/40"
            >
                <option value="none">None</option>
                {sessions.map((session) => (
                    <option key={session.id} value={session.id}>
                        {formatSessionRange(session.startedAt, session.endedAt)}
                    </option>
                ))}
            </select>
            <InputError message={error} />
        </div>
    );
}
