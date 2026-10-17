import { Form, Head } from '@inertiajs/react';
import { Modal } from '@inertiaui/modal-react';
import JournalEntryController from '@/actions/App/Http/Controllers/JournalEntryController';
import InputError from '@/components/input-error';
import JournalEntryPlaySessionSelect from '@/components/journal-entry-play-session-select';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Game, JournalEntry, PlaySession } from '@/types';

export default function Edit({
    game,
    journalEntry,
    playSessions,
}: {
    game: Game;
    journalEntry: JournalEntry;
    playSessions: PlaySession[];
}) {
    return (
        <Modal>
            <Head title="Edit Entry" />

            <div className="space-y-4">
                <h2 className="text-lg font-semibold tracking-tight">
                    Edit Entry
                </h2>

                <Form
                    {...JournalEntryController.update.form({
                        game: game.id,
                        journal_entry: journalEntry.id,
                    })}
                    className="space-y-4"
                    disableWhileProcessing
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`edit-${journalEntry.id}-body`}>
                                    What happened
                                </Label>
                                <Textarea
                                    id={`edit-${journalEntry.id}-body`}
                                    name="body"
                                    required
                                    maxLength={10000}
                                    rows={6}
                                    defaultValue={journalEntry.body}
                                    data-test={`edit-${journalEntry.id}-body`}
                                />
                                <InputError message={errors.body} />
                            </div>

                            <JournalEntryPlaySessionSelect
                                sessions={playSessions}
                                defaultValue={journalEntry.playSessionId ?? ''}
                                error={errors.play_session_id}
                            />

                            <Button
                                disabled={processing}
                                data-test={`save-journal-entry-button-${journalEntry.id}`}
                            >
                                {processing && <Spinner />}
                                Save Entry
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </Modal>
    );
}
