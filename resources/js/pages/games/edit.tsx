import { Form, Head } from '@inertiajs/react';
import { Modal } from '@inertiaui/modal-react';
import GameController from '@/actions/App/Http/Controllers/GameController';
import GameCatalogPicker from '@/components/game-catalog-picker';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { Game, GameStatusOption } from '@/types';

export default function Edit({
    game,
    statuses,
}: {
    game: Game;
    statuses: GameStatusOption[];
}) {
    return (
        <Modal>
            <Head title={`Edit ${game.title}`} />

            <div className="space-y-4">
                <h2 className="text-lg font-semibold tracking-tight">
                    Edit Game
                </h2>

                <Form
                    {...GameController.update.form(game.id)}
                    className="space-y-4"
                    disableWhileProcessing
                >
                    {({ processing, errors }) => (
                        <>
                            <GameCatalogPicker
                                autoFocus
                                initialTitle={game.title}
                                initialRawgId={game.rawgId}
                                error={errors.title ?? errors.rawg_id}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <select
                                    id="status"
                                    name="status"
                                    required
                                    defaultValue={game.status}
                                    data-test="game-status-select"
                                    className="flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm dark:bg-input/30 dark:aria-invalid:ring-destructive/40"
                                >
                                    {statuses.map((status) => (
                                        <option
                                            key={status.value}
                                            value={status.value}
                                        >
                                            {status.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.status} />
                            </div>

                            <Button
                                disabled={processing}
                                data-test="save-game-button"
                            >
                                {processing && <Spinner />}
                                Save Changes
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </Modal>
    );
}
