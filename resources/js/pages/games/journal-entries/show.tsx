import { Form, Head } from '@inertiajs/react';
import { Modal, ModalLink } from '@inertiaui/modal-react';
import JournalEntryController from '@/actions/App/Http/Controllers/JournalEntryController';
import { Button, buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
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
    return (
        <Modal>
            <Head title="Journal Entries" />

            <div
                className="space-y-4"
                data-test={`journal-entry-modal-${journalEntry.id}`}
            >
                <p className="text-sm text-muted-foreground">
                    {formatEntryDate(journalEntry.createdAt)}
                </p>

                <p className="whitespace-pre-wrap">{journalEntry.body}</p>

                <div className="flex items-center gap-2">
                    <ModalLink
                        href={JournalEntryController.edit.url({
                            game: game.id,
                            journal_entry: journalEntry.id,
                        })}
                        navigate
                        className={cn(
                            buttonVariants({ variant: 'outline', size: 'sm' }),
                        )}
                        data-test={`edit-journal-entry-button-${journalEntry.id}`}
                    >
                        Edit
                    </ModalLink>
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
            </div>
        </Modal>
    );
}
