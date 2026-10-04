import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import GameController from '@/actions/App/Http/Controllers/GameController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { edit, index, show } from '@/routes/games';
import type { Game } from '@/types';

export default function Show({ game }: { game: Game }) {
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
        ],
    });

    return (
        <>
            <Head title={game.title} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading title={game.title} />

                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            asChild
                            data-test="edit-game-button"
                        >
                            <Link href={edit(game)}>Edit</Link>
                        </Button>

                        <Form {...GameController.destroy.form(game.id)}>
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    size="sm"
                                    disabled={processing}
                                    data-test="delete-game-button"
                                >
                                    Delete
                                </Button>
                            )}
                        </Form>
                    </div>
                </div>

                <div
                    aria-label="Box art"
                    data-test="game-art-slot"
                    className="flex aspect-[3/4] w-40 items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 p-4 text-center text-sm text-muted-foreground dark:border-sidebar-border"
                >
                    No box art yet
                </div>

                <p className="text-sm text-muted-foreground">
                    Journal entries will live here.
                </p>
            </div>
        </>
    );
}

Show.layout = [AppLayout];
