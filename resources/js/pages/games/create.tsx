import { Form, Head } from '@inertiajs/react';
import { Modal } from '@inertiaui/modal-react';
import GameController from '@/actions/App/Http/Controllers/GameController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function Create() {
    return (
        <Modal>
            <Head title="Add Game" />

            <div className="space-y-4">
                <h2 className="text-lg font-semibold tracking-tight">
                    Add Game
                </h2>

                <Form
                    {...GameController.store.form()}
                    className="space-y-4"
                    disableWhileProcessing
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    required
                                    autoFocus
                                    maxLength={255}
                                    placeholder="Catan"
                                />
                                <InputError message={errors.title} />
                            </div>

                            <Button
                                disabled={processing}
                                data-test="save-game-button"
                            >
                                {processing && <Spinner />}
                                Save Game
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </Modal>
    );
}
