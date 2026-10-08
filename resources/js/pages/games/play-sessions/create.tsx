import { Form, Head } from '@inertiajs/react';
import { Modal } from '@inertiaui/modal-react';
import { store } from '@/actions/App/Http/Controllers/PlaySessionController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { browserTimeZone } from '@/lib/local-date-time';
import type { Game } from '@/types';

export default function Create({ game }: { game: Game }) {
    return (
        <Modal>
            <Head title="Add Session" />

            <div className="space-y-4">
                <h2 className="text-lg font-semibold tracking-tight">
                    Add Session
                </h2>

                <Form
                    {...store.form(game.id)}
                    className="space-y-4"
                    disableWhileProcessing
                >
                    {({ processing, errors }) => (
                        <>
                            <input
                                type="hidden"
                                name="timezone"
                                value={browserTimeZone()}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="play-session-started-at">
                                    Started
                                </Label>
                                <Input
                                    id="play-session-started-at"
                                    name="started_at"
                                    type="datetime-local"
                                    required
                                    data-test="play-session-started-at"
                                />
                                <InputError message={errors.started_at} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="play-session-ended-at">
                                    Ended
                                </Label>
                                <Input
                                    id="play-session-ended-at"
                                    name="ended_at"
                                    type="datetime-local"
                                    required
                                    data-test="play-session-ended-at"
                                />
                                <InputError message={errors.ended_at} />
                            </div>

                            <InputError message={errors.timezone} />

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
