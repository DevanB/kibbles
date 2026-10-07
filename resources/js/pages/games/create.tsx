import { Form, Head } from '@inertiajs/react';
import { Modal } from '@inertiaui/modal-react';
import GameController from '@/actions/App/Http/Controllers/GameController';
import GameCatalogPicker from '@/components/game-catalog-picker';
import { Button } from '@/components/ui/button';
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
                            <GameCatalogPicker
                                autoFocus
                                error={errors.title ?? errors.rawg_id}
                            />

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
