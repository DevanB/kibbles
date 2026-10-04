import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { ChevronRight } from 'lucide-react';
import GameController from '@/actions/App/Http/Controllers/GameController';
import JournalEntryController from '@/actions/App/Http/Controllers/JournalEntryController';
import Heading from '@/components/heading';
import { Button, buttonVariants } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { edit, index, show } from '@/routes/games';
import type { Game, JournalEntry } from '@/types';

function formatEntryDate(value: string): string {
    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        timeZone: 'UTC',
    });
}

export default function Show({
    game,
    journalEntries,
}: {
    game: Game;
    journalEntries: JournalEntry[];
}) {
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

                <Separator />

                <section className="max-w-2xl space-y-4">
                    <div className="flex items-center justify-between gap-4">
                        <Heading variant="small" title="Journal" />

                        <ModalLink
                            href={JournalEntryController.create.url(game.id)}
                            navigate
                            className={cn(buttonVariants({ size: 'sm' }))}
                            data-test="create-journal-entry-button"
                        >
                            Create Entry
                        </ModalLink>
                    </div>

                    {journalEntries.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No journal entries yet.
                        </p>
                    ) : (
                        <ul className="divide-y rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            {journalEntries.map((entry) => (
                                <li key={entry.id}>
                                    <ModalLink
                                        href={JournalEntryController.show.url({
                                            game: game.id,
                                            journal_entry: entry.id,
                                        })}
                                        navigate
                                        className="flex items-center justify-between gap-4 px-4 py-3 hover:bg-accent/50"
                                        data-test={`journal-entry-${entry.id}`}
                                    >
                                        <time
                                            dateTime={entry.createdAt}
                                            className="text-sm font-medium"
                                        >
                                            {formatEntryDate(entry.createdAt)}
                                        </time>
                                        <ChevronRight
                                            aria-hidden
                                            className="size-4 text-muted-foreground"
                                        />
                                    </ModalLink>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

Show.layout = [AppLayout];
