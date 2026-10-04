import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import GameController from '@/actions/App/Http/Controllers/GameController';
import JournalEntryController from '@/actions/App/Http/Controllers/JournalEntryController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { edit, index, show } from '@/routes/games';
import type { Game, JournalEntry } from '@/types';

type JournalEntryFields = {
    body: string;
    next: string;
};

function formatEntryDate(value: string): string {
    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        timeZone: 'UTC',
    });
}

function JournalEntryFields({
    body,
    next,
    idPrefix,
    errors,
}: JournalEntryFields & {
    idPrefix: string;
    errors: Partial<Record<'body' | 'next', string>>;
}) {
    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor={`${idPrefix}-body`}>What happened</Label>
                <Textarea
                    id={`${idPrefix}-body`}
                    name="body"
                    required
                    maxLength={10000}
                    rows={4}
                    defaultValue={body}
                    data-test={`${idPrefix}-body`}
                />
                <InputError message={errors.body} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`${idPrefix}-next`}>What's next</Label>
                <Textarea
                    id={`${idPrefix}-next`}
                    name="next"
                    maxLength={2000}
                    rows={2}
                    defaultValue={next}
                    data-test={`${idPrefix}-next`}
                />
                <InputError message={errors.next} />
            </div>
        </>
    );
}

function JournalEntryForm({
    action,
    submitLabel,
    submitTest,
    idPrefix,
    defaults,
}: {
    action: { action: string; method: 'post' };
    submitLabel: string;
    submitTest: string;
    idPrefix: string;
    defaults?: JournalEntryFields;
}) {
    return (
        <Form
            {...action}
            className="space-y-4"
            disableWhileProcessing
            resetOnSuccess={!defaults}
        >
            {({ processing, errors }) => (
                <>
                    <JournalEntryFields
                        idPrefix={idPrefix}
                        body={defaults?.body ?? ''}
                        next={defaults?.next ?? ''}
                        errors={errors}
                    />

                    <Button disabled={processing} data-test={submitTest}>
                        {processing && <Spinner />}
                        {submitLabel}
                    </Button>
                </>
            )}
        </Form>
    );
}

function JournalEntryCard({
    gameId,
    entry,
}: {
    gameId: string;
    entry: JournalEntry;
}) {
    const [editing, setEditing] = useState(false);

    return (
        <article
            className="space-y-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            data-test={`journal-entry-${entry.id}`}
        >
            {editing ? (
                <>
                    <JournalEntryForm
                        action={JournalEntryController.update.form({
                            game: gameId,
                            journal_entry: entry.id,
                        })}
                        submitLabel="Save entry"
                        submitTest={`save-journal-entry-button-${entry.id}`}
                        idPrefix={`edit-${entry.id}`}
                        defaults={{
                            body: entry.body,
                            next: entry.next ?? '',
                        }}
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => setEditing(false)}
                    >
                        Cancel
                    </Button>
                </>
            ) : (
                <>
                    <p className="text-sm text-muted-foreground">
                        {formatEntryDate(entry.createdAt)}
                    </p>
                    <p className="whitespace-pre-wrap">{entry.body}</p>
                    {entry.next !== null && (
                        <p className="text-sm">
                            <span className="font-medium">Next: </span>
                            {entry.next}
                        </p>
                    )}
                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => setEditing(true)}
                            data-test={`edit-journal-entry-button-${entry.id}`}
                        >
                            Edit
                        </Button>
                        <Form
                            {...JournalEntryController.destroy.form({
                                game: gameId,
                                journal_entry: entry.id,
                            })}
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    size="sm"
                                    disabled={processing}
                                    data-test={`delete-journal-entry-button-${entry.id}`}
                                >
                                    Delete
                                </Button>
                            )}
                        </Form>
                    </div>
                </>
            )}
        </article>
    );
}

export default function Show({
    game,
    journalEntries,
    resume,
}: {
    game: Game;
    journalEntries: JournalEntry[];
    resume: string | null;
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

                <section className="max-w-2xl space-y-4">
                    <Heading
                        variant="small"
                        title="Journal"
                        description="What you played, and what you meant to do next"
                    />

                    {resume !== null && (
                        <div
                            data-test="journal-resume"
                            className="rounded-xl border border-sidebar-border/70 bg-sidebar/40 p-4 dark:border-sidebar-border"
                        >
                            <p className="text-sm font-medium">Up next</p>
                            <p className="mt-1 text-sm whitespace-pre-wrap">
                                {resume}
                            </p>
                        </div>
                    )}

                    <JournalEntryForm
                        action={JournalEntryController.store.form(game.id)}
                        submitLabel="Add entry"
                        submitTest="add-journal-entry-button"
                        idPrefix="compose"
                    />

                    {journalEntries.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No journal entries yet.
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {journalEntries.map((entry) => (
                                <JournalEntryCard
                                    key={entry.id}
                                    gameId={game.id}
                                    entry={entry}
                                />
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

Show.layout = [AppLayout];
