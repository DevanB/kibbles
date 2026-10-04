import { Head, Link } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { ChevronRight } from 'lucide-react';
import GameController from '@/actions/App/Http/Controllers/GameController';
import JournalEntryController from '@/actions/App/Http/Controllers/JournalEntryController';
import Heading from '@/components/heading';
import { buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as games } from '@/routes/games';
import type { BreadcrumbItem, RecentJournalEntry } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

function formatEntryDate(value: string): string {
    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        timeZone: 'UTC',
    });
}

export default function Dashboard({
    recentJournalEntries,
}: {
    recentJournalEntries: RecentJournalEntry[];
}) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Dashboard"
                    description="Latest notes from your catalog"
                />

                <section
                    className="flex flex-1 flex-col space-y-4"
                    data-test="recent-journal-entries"
                >
                    <Heading variant="small" title="Recent Journal Entries" />

                    {recentJournalEntries.length === 0 ? (
                        <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border">
                            <p className="text-lg font-medium">
                                No journal entries yet
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Add a game and write what happened the last time
                                you played.
                            </p>
                            <div className="mt-4 flex flex-wrap items-center justify-center gap-2">
                                <Link
                                    href={games()}
                                    className={cn(
                                        buttonVariants({ variant: 'outline' }),
                                    )}
                                    data-test="dashboard-games-link"
                                >
                                    Games
                                </Link>
                                <ModalLink
                                    href={GameController.create.url()}
                                    navigate
                                    className={cn(buttonVariants())}
                                    data-test="dashboard-add-game-button"
                                >
                                    Add Game
                                </ModalLink>
                            </div>
                        </div>
                    ) : (
                        <ul className="w-full divide-y rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            {recentJournalEntries.map((entry) => (
                                <li key={entry.id}>
                                    <ModalLink
                                        href={JournalEntryController.show.url({
                                            game: entry.game.id,
                                            journal_entry: entry.id,
                                        })}
                                        navigate
                                        className="flex w-full items-center justify-between gap-4 px-4 py-3 hover:bg-accent/50"
                                        data-test={`recent-journal-entry-${entry.id}`}
                                    >
                                        <div className="min-w-0 text-left">
                                            <p className="font-medium">
                                                {entry.game.title}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                <time
                                                    dateTime={entry.createdAt}
                                                >
                                                    {formatEntryDate(
                                                        entry.createdAt,
                                                    )}
                                                </time>
                                                {entry.preview ? (
                                                    <> · {entry.preview}</>
                                                ) : null}
                                            </p>
                                        </div>
                                        <ChevronRight
                                            aria-hidden
                                            className="size-4 shrink-0 text-muted-foreground"
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

Dashboard.layout = [AppLayout, { breadcrumbs }];
