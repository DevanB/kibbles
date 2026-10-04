import { Form, Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import { index, store } from '@/routes/games';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Games',
        href: index(),
    },
    {
        title: 'Add game',
        href: '#',
    },
];

export default function Create() {
    return (
        <>
            <Head title="Add game" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Add game"
                    description="Create a game in your personal catalog"
                />

                <Form
                    {...store.form()}
                    className="max-w-xl space-y-6"
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

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="save-game-button"
                                >
                                    {processing && <Spinner />}
                                    Save game
                                </Button>
                                <Button variant="ghost" asChild>
                                    <Link href={index()}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Create.layout = [AppLayout, { breadcrumbs }];
