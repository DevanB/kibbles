import { Form, Head } from '@inertiajs/react';
import { Modal } from '@inertiaui/modal-react';
import { useState } from 'react';
import JournalEntryController from '@/actions/App/Http/Controllers/JournalEntryController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Game, JournalEntry } from '@/types';

function formatEntryDate(value: string): string {
    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        timeZone: 'UTC',
    });
}

export default function Show({
    game,
    journalEntry,
}: {
    game: Game;
    journalEntry: JournalEntry;
}) {
    const [editing, setEditing] = useState(false);

    return (
        <Modal>
            <Head title={`Journal — ${game.title}`} />

            <div
                className="space-y-4"
                data-test={`journal-entry-modal-${journalEntry.id}`}
            >
                <p className="text-sm text-muted-foreground">
                    {formatEntryDate(journalEntry.createdAt)}
                </p>

                {editing ? (
                    <>
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
                                        <Label
                                            htmlFor={`edit-${journalEntry.id}-body`}
                                        >
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

                                    <div className="flex items-center gap-2">
                                        <Button
                                            disabled={processing}
                                            data-test={`save-journal-entry-button-${journalEntry.id}`}
                                        >
                                            {processing && <Spinner />}
                                            Save entry
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            onClick={() => setEditing(false)}
                                        >
                                            Cancel
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </>
                ) : (
                    <>
                        <p className="whitespace-pre-wrap">
                            {journalEntry.body}
                        </p>

                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setEditing(true)}
                                data-test={`edit-journal-entry-button-${journalEntry.id}`}
                            >
                                Edit
                            </Button>
                            <Form
                                {...JournalEntryController.destroy.form({
                                    game: game.id,
                                    journal_entry: journalEntry.id,
                                })}
                            >
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        size="sm"
                                        disabled={processing}
                                        data-test={`delete-journal-entry-button-${journalEntry.id}`}
                                    >
                                        Delete
                                    </Button>
                                )}
                            </Form>
                        </div>
                    </>
                )}
            </div>
        </Modal>
    );
}
