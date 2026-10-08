import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { cn } from 'cn';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import GameController from '@/actions/App/Http/Controllers/GameController';
import JournalEntryController from '@/actions/App/Http/Controllers/JournalEntryController';
import {
    create as createPlaySession,
    destroy as destroyPlaySession,
    edit as editPlaySession,
    store as storePlaySession,
} from '@/actions/App/Http/Controllers/PlaySessionController';
import DeleteConfirmationDialog from '@/components/delete-confirmation-dialog';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { formatSessionRow } from '@/lib/local-date-time';
import { index, show } from '@/routes/games';
import type { Game, JournalEntry, OpenPlaySession, PlaySession } from '@/types';

type GameTab = 'sessions' | 'journal';

function parseGameTab(tab: string): GameTab {
    return tab === 'journal' ? 'journal' : 'sessions';
}

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
    playSessions,
    openPlaySession,
    totalPlayedMinutes,
    totalPlayedLabel,
    tab,
}: {
    game: Game;
    journalEntries: JournalEntry[];
    playSessions: PlaySession[];
    openPlaySession: OpenPlaySession | null;
    totalPlayedMinutes: number | null;
    totalPlayedLabel: string;
    tab: GameTab;
}) {
    const [deleteOpen, setDeleteOpen] = useState(false);
    const activeTab = parseGameTab(tab);
    const [sessionToDelete, setSessionToDelete] = useState<PlaySession | null>(
        null,
    );
    const openOnThisGame = openPlaySession?.gameId === game.id;
    const openOnAnotherGame =
        openPlaySession !== null && openPlaySession.gameId !== game.id;

    const selectTab = (next: string): void => {
        const value = parseGameTab(next);

        if (value === activeTab) {
            return;
        }

        router.get(
            show.url(
                game.id,
                value === 'journal' ? { query: { tab: 'journal' } } : {},
            ),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

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
                    <header className="mb-8 flex min-w-0 flex-wrap items-center gap-3">
                        <h2 className="text-xl font-semibold tracking-tight">
                            {game.title}
                        </h2>
                        <Badge variant="secondary" data-test="game-status">
                            {game.statusLabel}
                        </Badge>
                    </header>

                    <div className="flex items-center gap-2">
                        {openOnThisGame ? (
                            <Button variant="outline" size="sm" asChild>
                                <ModalLink
                                    href={editPlaySession.url({
                                        game: game.id,
                                        play_session: openPlaySession.id,
                                    })}
                                    navigate
                                    data-test="stop-play-session-button"
                                >
                                    Stop
                                </ModalLink>
                            </Button>
                        ) : null}
                        {!openPlaySession ? (
                            <Form {...storePlaySession.form(game.id)}>
                                <Button
                                    type="submit"
                                    variant="outline"
                                    size="sm"
                                    data-test="start-play-session-button"
                                >
                                    Start
                                </Button>
                            </Form>
                        ) : null}
                        <div className="inline-flex">
                            <Button
                                variant="outline"
                                size="sm"
                                asChild
                                className="rounded-r-none"
                            >
                                <ModalLink
                                    href={GameController.edit.url(game.id)}
                                    navigate
                                    data-test="edit-game-button"
                                >
                                    Edit
                                </ModalLink>
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
                                        onSelect={() => {
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
                </div>

                <div className="flex flex-col gap-6 sm:flex-row sm:items-start">
                    {game.imageUrl ? (
                        <img
                            src={game.imageUrl}
                            alt=""
                            data-test="game-art"
                            className="aspect-[3/4] w-40 shrink-0 rounded-xl object-cover"
                        />
                    ) : (
                        <div
                            aria-label="Box art"
                            data-test="game-art-slot"
                            className="flex aspect-[3/4] w-40 items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 p-4 text-center text-sm text-muted-foreground dark:border-sidebar-border"
                        >
                            No box art yet
                        </div>
                    )}
                    {game.description ? (
                        <p
                            data-test="game-description"
                            className="max-w-prose text-sm leading-relaxed whitespace-pre-wrap text-muted-foreground"
                        >
                            {game.description}
                        </p>
                    ) : null}
                </div>

                <div className="space-y-1">
                    <Heading variant="small" title="Time played" />
                    <p
                        data-test="play-time-total"
                        data-minutes={
                            totalPlayedMinutes === null
                                ? undefined
                                : String(totalPlayedMinutes)
                        }
                    >
                        {totalPlayedLabel}
                    </p>
                </div>

                {openOnAnotherGame ? (
                    <p className="rounded-xl border border-sidebar-border/70 px-4 py-3 text-sm dark:border-sidebar-border">
                        You&apos;re playing{' '}
                        <Link
                            href={show(openPlaySession.gameId)}
                            className="font-medium underline"
                            data-test="open-session-game-link"
                        >
                            {openPlaySession.gameTitle}
                        </Link>
                        .
                    </p>
                ) : null}

                <Separator />

                <Tabs
                    value={activeTab}
                    onValueChange={selectTab}
                    className="flex flex-1 flex-col gap-4"
                    data-test="game-tabs"
                >
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <TabsList>
                            <TabsTrigger
                                value="sessions"
                                data-test="game-tab-sessions"
                            >
                                Play Sessions
                            </TabsTrigger>
                            <TabsTrigger
                                value="journal"
                                data-test="game-tab-journal"
                            >
                                Journal Entries
                            </TabsTrigger>
                        </TabsList>

                        {activeTab === 'sessions' && playSessions.length > 0 ? (
                            <ModalLink
                                href={createPlaySession.url(game.id)}
                                navigate
                                className={cn(buttonVariants({ size: 'sm' }))}
                                data-test="add-play-session-button"
                            >
                                Add Session
                            </ModalLink>
                        ) : null}
                        {activeTab === 'journal' &&
                        journalEntries.length > 0 ? (
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
                        ) : null}
                    </div>

                    <TabsContent
                        value="sessions"
                        className="flex flex-1 flex-col space-y-4"
                    >
                        {playSessions.length === 0 ? (
                            <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border">
                                <p className="text-lg font-medium">
                                    No play sessions yet
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Log a session after you play.
                                </p>
                                <div className="mt-4 flex flex-wrap items-center justify-center gap-2">
                                    {!openPlaySession ? (
                                        <Form
                                            {...storePlaySession.form(game.id)}
                                        >
                                            <Button
                                                type="submit"
                                                data-test="start-play-session-button"
                                            >
                                                Start
                                            </Button>
                                        </Form>
                                    ) : null}
                                    <ModalLink
                                        href={createPlaySession.url(game.id)}
                                        navigate
                                        className={cn(buttonVariants())}
                                        data-test="add-play-session-button"
                                    >
                                        Add Session
                                    </ModalLink>
                                </div>
                            </div>
                        ) : (
                            <ul className="w-full divide-y rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                                {playSessions.map((session) => {
                                    const isOpen = session.endedAt === null;

                                    return (
                                        <li
                                            key={session.id}
                                            className="flex flex-wrap items-center justify-between gap-4 px-4 py-3"
                                            data-test={`play-session-${session.id}`}
                                        >
                                            <div className="min-w-0">
                                                <p>
                                                    {formatSessionRow(
                                                        session.startedAt,
                                                        session.endedAt,
                                                        session.durationMinutes,
                                                    )}
                                                </p>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                {isOpen ? (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <ModalLink
                                                            href={editPlaySession.url(
                                                                {
                                                                    game: game.id,
                                                                    play_session:
                                                                        session.id,
                                                                },
                                                            )}
                                                            navigate
                                                            data-test={`stop-play-session-button-${session.id}`}
                                                        >
                                                            Stop
                                                        </ModalLink>
                                                    </Button>
                                                ) : (
                                                    <>
                                                        {session.journalEntryId ? (
                                                            <ModalLink
                                                                href={JournalEntryController.show.url(
                                                                    {
                                                                        game: game.id,
                                                                        journal_entry:
                                                                            session.journalEntryId,
                                                                    },
                                                                )}
                                                                navigate
                                                                className={cn(
                                                                    buttonVariants(
                                                                        {
                                                                            variant:
                                                                                'secondary',
                                                                            size: 'sm',
                                                                        },
                                                                    ),
                                                                )}
                                                                data-test={`play-session-journal-${session.id}`}
                                                            >
                                                                Journal
                                                            </ModalLink>
                                                        ) : null}
                                                        <ModalLink
                                                            href={editPlaySession.url(
                                                                {
                                                                    game: game.id,
                                                                    play_session:
                                                                        session.id,
                                                                },
                                                            )}
                                                            navigate
                                                            className={cn(
                                                                buttonVariants({
                                                                    variant:
                                                                        'outline',
                                                                    size: 'sm',
                                                                }),
                                                            )}
                                                            data-test={`edit-play-session-button-${session.id}`}
                                                        >
                                                            Edit
                                                        </ModalLink>
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger
                                                                asChild
                                                            >
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    className="px-2"
                                                                    aria-label="Session actions"
                                                                    data-test={`play-session-actions-button-${session.id}`}
                                                                >
                                                                    <ChevronDown />
                                                                </Button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent align="end">
                                                                <DropdownMenuItem
                                                                    variant="destructive"
                                                                    data-test={`delete-play-session-button-${session.id}`}
                                                                    onSelect={() => {
                                                                        setSessionToDelete(
                                                                            session,
                                                                        );
                                                                    }}
                                                                >
                                                                    Delete
                                                                </DropdownMenuItem>
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    </>
                                                )}
                                            </div>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                        {sessionToDelete ? (
                            <DeleteConfirmationDialog
                                open
                                onOpenChange={(open) => {
                                    if (!open) {
                                        setSessionToDelete(null);
                                    }
                                }}
                                title="Delete Session?"
                                description="This will permanently delete this play session. Linked journal entries are kept."
                                confirmLabel="Delete Session"
                                confirmTest={`confirm-delete-play-session-button-${sessionToDelete.id}`}
                                cancelTest={`cancel-delete-play-session-button-${sessionToDelete.id}`}
                                form={destroyPlaySession.form({
                                    game: game.id,
                                    play_session: sessionToDelete.id,
                                })}
                            />
                        ) : null}
                    </TabsContent>

                    <TabsContent
                        value="journal"
                        className="flex flex-1 flex-col space-y-4"
                    >
                        {journalEntries.length === 0 ? (
                            <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border">
                                <p className="text-lg font-medium">
                                    No journal entries yet
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Write what happened the last time you
                                    played.
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
                                            href={JournalEntryController.show.url(
                                                {
                                                    game: game.id,
                                                    journal_entry: entry.id,
                                                },
                                            )}
                                            navigate
                                            className="flex w-full items-center justify-between gap-4 px-4 py-3 hover:bg-accent/50"
                                            data-test={`journal-entry-${entry.id}`}
                                        >
                                            <time
                                                dateTime={entry.createdAt}
                                                className="text-sm font-medium"
                                            >
                                                {formatEntryDate(
                                                    entry.createdAt,
                                                )}
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
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}

Show.layout = [AppLayout];
