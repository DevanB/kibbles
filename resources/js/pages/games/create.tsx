import { Form, Head } from '@inertiajs/react';
import GameController from '@/actions/App/Http/Controllers/GameController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { create, index } from '@/routes/games';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Games',
        href: index(),
    },
    {
        title: 'Create',
        href: create(),
    },
];

export default function Create() {
    return (
        <>
            <Head title="Create game" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Create game"
                    description="Give your game a title"
                />

                <Form
                    {...GameController.store.form()}
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
                                data-test="create-game-button"
                            >
                                Create game
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Create.layout = [AppLayout, { breadcrumbs }];
