import { Form, Head, InfiniteScroll, setLayoutProps } from '@inertiajs/react';
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
import { BookmarkMasonry } from '@/components/bookmark-masonry';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/bookmarks';
import type { BookmarkPage, BreadcrumbItem, XConnection } from '@/types';

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
    bookmarks: BookmarkPage;
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
                                <Form
                                    {...storeBookmarkSync.form()}
                                    options={{ preserveScroll: true }}
                                >
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
                                <a href={createXConnection.url()}>
                                    <Plus />
                                    Connect X
                                </a>
                            </Button>
                        )}
                    </div>
                </div>

                {bookmarks.data.length === 0 ? (
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
                    <InfiniteScroll
                        data="bookmarks"
                        buffer={400}
                        loading={() => (
                            <p
                                data-test="bookmarks-load-more"
                                className="py-4 text-center text-sm text-muted-foreground"
                            >
                                Loading more…
                            </p>
                        )}
                    >
                        <BookmarkMasonry bookmarks={bookmarks.data}>
                            {(bookmark) => (
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
                            )}
                        </BookmarkMasonry>
                    </InfiniteScroll>
                )}
            </div>
        </>
    );
}

Index.layout = [AppLayout, { breadcrumbs }];
