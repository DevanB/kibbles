import { Form, Head } from '@inertiajs/react';
import { Modal } from '@inertiaui/modal-react';
import { update } from '@/actions/App/Http/Controllers/PlaySessionController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Game, PlaySession } from '@/types';

export default function Stop({
    game,
    playSession,
}: {
    game: Game;
    playSession: PlaySession;
}) {
    return (
        <Modal>
            <Head title="Stop Session" />

            <div className="space-y-4">
                <h2 className="text-lg font-semibold tracking-tight">
                    Stop Session
                </h2>

                <Form
                    {...update.form({
                        game: game.id,
                        play_session: playSession.id,
                    })}
                    className="space-y-4"
                    disableWhileProcessing
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="stop-session-body">
                                    What happened
                                </Label>
                                <Textarea
                                    id="stop-session-body"
                                    name="body"
                                    maxLength={10000}
                                    rows={6}
                                    data-test="stop-session-body"
                                />
                                <InputError message={errors.body} />
                            </div>

                            <InputError message={errors.play_session} />

                            <Button
                                disabled={processing}
                                data-test="save-play-session-button"
                            >
                                {processing && <Spinner />}
                                Save Session
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </Modal>
    );
}
