import { Form, Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { create, destroy, edit, index } from '@/routes/games';
import type { BreadcrumbItem, Game } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Games',
        href: index(),
    },
];

export default function Index({ games }: { games: Game[] }) {
    return (
        <>
            <Head title="Games" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Games"
                        description="Your personal catalog of games"
                    />

                    <Button asChild data-test="create-game-button">
                        <Link href={create()}>
                            <Plus />
                            Add game
                        </Link>
                    </Button>
                </div>

                {games.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border">
                        <p className="text-lg font-medium">No games yet</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Add your first game to start your catalog.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href={create()}>Add game</Link>
                        </Button>
                    </div>
                ) : (
                    <ul className="divide-y rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        {games.map((game) => (
                            <li
                                key={game.id}
                                className="flex items-center justify-between gap-4 px-4 py-3"
                            >
                                <Link
                                    href={edit(game)}
                                    className="font-medium hover:underline"
                                    data-test={`game-title-${game.id}`}
                                >
                                    {game.title}
                                </Link>

                                <div className="flex items-center gap-2">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={edit(game)}>Edit</Link>
                                    </Button>

                                    <Form {...destroy.form(game)}>
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                variant="destructive"
                                                size="sm"
                                                disabled={processing}
                                                data-test={`delete-game-button-${game.id}`}
                                            >
                                                Delete
                                            </Button>
                                        )}
                                    </Form>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

Index.layout = [AppLayout, { breadcrumbs }];
