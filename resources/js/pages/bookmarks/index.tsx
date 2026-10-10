import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { Bookmark as BookmarkIcon, Plus, Trash2 } from 'lucide-react';
import { show } from '@/actions/App/Http/Controllers/BookmarkController';
import { store as storeBookmarkSync } from '@/actions/App/Http/Controllers/BookmarkSyncController';
import {
    create as createXConnection,
    destroy as destroyXConnection,
} from '@/actions/App/Http/Controllers/XConnectionController';
import {
    BookmarkCard,
    bookmarkIconActionClassName,
    formatSyncedAt,
} from '@/components/bookmark-card';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/bookmarks';
import type { Bookmark, BreadcrumbItem, XConnection } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Bookmarks',
        href: index(),
    },
];

function headerDescription(connection: XConnection | null): string {
    if (connection === null) {
        return 'Connect X to sync your saved posts';
    }

    if (connection.lastSyncedAt === null) {
        return `Saved posts from @${connection.username}`;
    }

    return `Saved posts from @${connection.username} · ${formatSyncedAt(connection.lastSyncedAt)}`;
}

export default function Index({
    bookmarks,
    xConnection,
}: {
    bookmarks: Bookmark[];
    xConnection: XConnection | null;
}) {
    setLayoutProps({ breadcrumbs });

    return (
        <>
            <Head title="Bookmarks" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Bookmarks"
                        description={headerDescription(xConnection)}
                    />

                    <div className="flex flex-wrap items-center gap-2">
                        {xConnection ? (
                            <>
                                <Form {...storeBookmarkSync.form()}>
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            disabled={processing}
                                            data-test="sync-bookmarks-button"
                                        >
                                            Sync now
                                        </Button>
                                    )}
                                </Form>
                                <Form {...destroyXConnection.form()}>
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="ghost"
                                            disabled={processing}
                                            data-test="disconnect-x-button"
                                        >
                                            Disconnect X
                                        </Button>
                                    )}
                                </Form>
                            </>
                        ) : (
                            <Button asChild data-test="connect-x-button">
                                <Link href={createXConnection.url()}>
                                    <Plus />
                                    Connect X
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {bookmarks.length === 0 ? (
                    <div
                        data-test="bookmarks-empty"
                        className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
                    >
                        <BookmarkIcon className="mb-3 size-8 text-muted-foreground" />
                        <p className="text-lg font-medium">
                            {xConnection
                                ? 'No bookmarks yet'
                                : 'Connect X to see saved posts'}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {xConnection
                                ? 'Sync now to pull in the latest bookmarks from X.'
                                : 'Kibbles syncs your X bookmarks into a masonry grid here.'}
                        </p>
                    </div>
                ) : (
                    <ul className="columns-1 gap-4 sm:columns-2 xl:columns-3">
                        {bookmarks.map((bookmark) => (
                            <li
                                key={bookmark.id}
                                className="mb-4 break-inside-avoid"
                            >
                                <BookmarkCard
                                    bookmark={bookmark}
                                    actions={
                                        <ModalLink
                                            href={show.url(bookmark)}
                                            navigate
                                            aria-label="Remove bookmark"
                                            className={
                                                bookmarkIconActionClassName
                                            }
                                            data-test={`remove-bookmark-button-${bookmark.id}`}
                                        >
                                            <Trash2 className="size-4" />
                                        </ModalLink>
                                    }
                                />
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

Index.layout = [AppLayout, { breadcrumbs }];
