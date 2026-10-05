import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import GameController from '@/actions/App/Http/Controllers/GameController';
import JournalEntryController from '@/actions/App/Http/Controllers/JournalEntryController';
import DeleteConfirmationDialog from '@/components/delete-confirmation-dialog';
import Heading from '@/components/heading';
import { Button, buttonVariants } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
    const [deleteOpen, setDeleteOpen] = useState(false);

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

                    <div className="inline-flex">
                        <Button
                            variant="outline"
                            size="sm"
                            asChild
                            className="rounded-r-none"
                            data-test="edit-game-button"
                        >
                            <Link href={edit(game)}>Edit</Link>
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="rounded-l-none border-l-0 px-2"
                                    aria-label="More actions"
                                    data-test="game-actions-button"
                                >
                                    <ChevronDown />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                    variant="destructive"
                                    data-test="delete-game-button"
                                    onSelect={(event) => {
                                        event.preventDefault();
                                        setDeleteOpen(true);
                                    }}
                                >
                                    Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        <DeleteConfirmationDialog
                            open={deleteOpen}
                            onOpenChange={setDeleteOpen}
                            title={`Delete ${game.title}?`}
                            description={`This will permanently delete ${game.title} and its journal entries. This cannot be undone.`}
                            confirmLabel="Delete Game"
                            confirmTest="confirm-delete-game-button"
                            cancelTest="cancel-delete-game-button"
                            form={GameController.destroy.form(game.id)}
                        />
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

                <section className="flex flex-1 flex-col space-y-4">
                    <div className="flex items-center justify-between gap-4">
                        <Heading variant="small" title="Journal Entries" />

                        {journalEntries.length > 0 && (
                            <ModalLink
                                href={JournalEntryController.create.url(
                                    game.id,
                                )}
                                navigate
                                className={cn(buttonVariants({ size: 'sm' }))}
                                data-test="create-journal-entry-button"
                            >
                                Create Entry
                            </ModalLink>
                        )}
                    </div>

                    {journalEntries.length === 0 ? (
                        <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border">
                            <p className="text-lg font-medium">
                                No journal entries yet
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Write what happened the last time you played.
                            </p>
                            <ModalLink
                                href={JournalEntryController.create.url(
                                    game.id,
                                )}
                                navigate
                                className={cn(buttonVariants(), 'mt-4')}
                                data-test="create-journal-entry-button"
                            >
                                Create Entry
                            </ModalLink>
                        </div>
                    ) : (
                        <ul className="w-full divide-y rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            {journalEntries.map((entry) => (
                                <li key={entry.id}>
                                    <ModalLink
                                        href={JournalEntryController.show.url({
                                            game: game.id,
                                            journal_entry: entry.id,
                                        })}
                                        navigate
                                        className="flex w-full items-center justify-between gap-4 px-4 py-3 hover:bg-accent/50"
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
