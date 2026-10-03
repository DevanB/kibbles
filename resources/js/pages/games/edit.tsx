import { Form, Head } from '@inertiajs/react';
import GameController from '@/actions/App/Http/Controllers/GameController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/games';
import type { BreadcrumbItem, Game } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Games',
        href: index(),
    },
    {
        title: 'Edit',
        href: index(),
    },
];

export default function Edit({ game }: { game: Game }) {
    return (
        <>
            <Head title={`Edit ${game.title}`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Edit game"
                    description="Update the title of this game"
                />

                <Form
                    {...GameController.update.form(game)}
                    options={{
                        preserveScroll: true,
                    }}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    className="mt-1 block w-full"
                                    defaultValue={game.title}
                                    required
                                    autoFocus
                                    maxLength={255}
                                    placeholder="Game title"
                                />
                                <InputError
                                    className="mt-2"
                                    message={errors.title}
                                />
                            </div>

                            <Button
                                disabled={processing}
                                data-test="update-game-button"
                            >
                                Save
                            </Button>
                        </>
                    )}
                </Form>

                <div className="max-w-xl space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                    <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                        <p className="font-medium">Delete game</p>
                        <p className="text-sm">
                            Permanently delete this game. This cannot be undone.
                        </p>
                    </div>

                    <Form {...GameController.destroy.form(game)}>
                        {({ processing }) => (
                            <Button
                                variant="destructive"
                                disabled={processing}
                                data-test="delete-game-button"
                            >
                                Delete game
                            </Button>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

Edit.layout = [AppLayout, { breadcrumbs }];
