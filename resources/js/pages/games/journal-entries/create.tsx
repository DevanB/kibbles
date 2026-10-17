import { Form, Head } from '@inertiajs/react';
import { Modal } from '@inertiaui/modal-react';
import JournalEntryController from '@/actions/App/Http/Controllers/JournalEntryController';
import InputError from '@/components/input-error';
import JournalEntryPlaySessionSelect from '@/components/journal-entry-play-session-select';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Game, PlaySession } from '@/types';

export default function Create({
    game,
    playSessions,
}: {
    game: Game;
    playSessions: PlaySession[];
}) {
    return (
        <Modal>
            <Head title="Create Entry" />

            <div className="space-y-4">
                <h2 className="text-lg font-semibold tracking-tight">
                    Create Entry
                </h2>

                <Form
                    {...JournalEntryController.store.form(game.id)}
                    className="space-y-4"
                    disableWhileProcessing
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="compose-body">
                                    What happened
                                </Label>
                                <Textarea
                                    id="compose-body"
                                    name="body"
                                    required
                                    maxLength={10000}
                                    rows={6}
                                    data-test="compose-body"
                                />
                                <InputError message={errors.body} />
                            </div>

                            <JournalEntryPlaySessionSelect
                                sessions={playSessions}
                                error={errors.play_session_id}
                            />

                            <Button
                                disabled={processing}
                                data-test="add-journal-entry-button"
                            >
                                {processing && <Spinner />}
                                Add Entry
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </Modal>
    );
}
