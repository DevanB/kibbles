import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import GameController from '@/actions/App/Http/Controllers/GameController';
import DeleteConfirmationDialog from '@/components/delete-confirmation-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import { index, show } from '@/routes/games';
import type { Game } from '@/types';

export default function Edit({ game }: { game: Game }) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Games',
                href: index(),
            },
            {
                title: game.title,
                href: show(game),
            },
            {
                title: 'Edit Game',
                href: '#',
            },
        ],
    });

    return (
        <>
            <Head title={`Edit ${game.title}`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Edit Game"
                    description="Update this game in your personal catalog"
                />

                <Form
                    {...GameController.update.form(game.id)}
                    className="max-w-xl space-y-6"
                    disableWhileProcessing
                    options={{ preserveScroll: true }}
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
                                    defaultValue={game.title}
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="save-game-button"
                                >
                                    {processing && <Spinner />}
                                    Save Changes
                                </Button>
                                <Button variant="ghost" asChild>
                                    <Link href={show(game)}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="max-w-xl space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                    <Heading
                        variant="small"
                        title="Delete Game"
                        description="Permanently remove this game from your catalog"
                    />

                    <DeleteConfirmationDialog
                        title={`Delete ${game.title}?`}
                        description={`This will permanently delete ${game.title} and its journal entries. This cannot be undone.`}
                        confirmLabel="Delete Game"
                        confirmTest="confirm-delete-game-button"
                        cancelTest="cancel-delete-game-button"
                        form={GameController.destroy.form(game.id)}
                        trigger={
                            <Button
                                variant="destructive"
                                data-test="delete-game-button"
                            >
                                Delete Game
                            </Button>
                        }
                    />
                </div>
            </div>
        </>
    );
}

Edit.layout = [AppLayout];
