import { Form, Head, Link } from '@inertiajs/react';
import { Gamepad2 } from 'lucide-react';
import GameController from '@/actions/App/Http/Controllers/GameController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { create, edit, index } from '@/routes/games';
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
                        description="Create and manage your games"
                    />
                    <Button asChild>
                        <Link href={create()} prefetch>
                            Create game
                        </Link>
                    </Button>
                </div>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    {games.length > 0 ? (
                        <ul>
                            {games.map((game) => (
                                <li
                                    key={game.id}
                                    className="flex items-center justify-between gap-4 border-b p-4 last:border-b-0"
                                >
                                    <p className="font-medium tracking-tight">
                                        {game.title}
                                    </p>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link href={edit(game)} prefetch>
                                                Edit
                                            </Link>
                                        </Button>
                                        <Form
                                            {...GameController.destroy.form(
                                                game,
                                            )}
                                            options={{
                                                preserveScroll: true,
                                            }}
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    disabled={processing}
                                                    data-test={`delete-game-${game.id}`}
                                                >
                                                    Delete
                                                </Button>
                                            )}
                                        </Form>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="p-8 text-center">
                            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                                <Gamepad2 className="h-7 w-7 text-muted-foreground" />
                            </div>
                            <p className="font-medium">No games yet</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Create your first game to get started
                            </p>
                            <Button asChild className="mt-4">
                                <Link href={create()} prefetch>
                                    Create game
                                </Link>
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

Index.layout = [AppLayout, { breadcrumbs }];
