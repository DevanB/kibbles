import {
    Form,
    Head,
    InfiniteScroll,
    Link,
    router,
    setLayoutProps,
} from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { Bookmark as BookmarkIcon, Ellipsis, Plus, Trash2 } from 'lucide-react';
import { show } from '@/actions/App/Http/Controllers/XBookmarkController';
import { store as storeBookmarkSync } from '@/actions/App/Http/Controllers/XBookmarkSyncController';
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
import { BookmarkTagPicker } from '@/components/bookmark-tag-picker';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/x-bookmarks';
import { index as tagsIndex } from '@/routes/tags';
import type { BookmarkPage, BreadcrumbItem, Tag, XConnection } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'X',
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
    activeTag,
}: {
    bookmarks: BookmarkPage;
    xConnection: XConnection | null;
    activeTag: Tag | null;
}) {
    setLayoutProps({ breadcrumbs });

    return (
        <>
            <Head title="X" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <Heading
                            title="X"
                            description={headerDescription(xConnection)}
                        />
                        {activeTag ? (
                            <Badge
                                variant="secondary"
                                data-test="active-tag-name"
                            >
                                {activeTag.name}
                            </Badge>
                        ) : null}
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            asChild
                            variant="outline"
                            data-test="manage-tags-button"
                        >
                            <Link href={tagsIndex()}>Manage tags</Link>
                        </Button>
                        {xConnection ? (
                            <>
                                <Form
                                    {...storeBookmarkSync.form()}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <ButtonGroup aria-label="Bookmark sync">
                                            <Button
                                                type="submit"
                                                variant="outline"
                                                disabled={processing}
                                                data-test="sync-bookmarks-button"
                                            >
                                                Sync now
                                            </Button>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        className="px-2"
                                                        aria-label="More sync options"
                                                        data-test="sync-bookmarks-actions-button"
                                                    >
                                                        <Ellipsis />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem
                                                        data-test="full-sync-bookmarks-button"
                                                        onSelect={() => {
                                                            router.post(
                                                                storeBookmarkSync.url(),
                                                                { full: true },
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            );
                                                        }}
                                                    >
                                                        <span className="flex flex-col items-start gap-0.5">
                                                            <span>
                                                                Full sync
                                                            </span>
                                                            <span className="text-xs font-normal text-muted-foreground">
                                                                Also removes
                                                                posts you've
                                                                unbookmarked on
                                                                X
                                                            </span>
                                                        </span>
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </ButtonGroup>
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
                            {activeTag?.id === 'untagged'
                                ? 'No untagged bookmarks'
                                : activeTag
                                  ? `No bookmarks tagged ${activeTag.name}`
                                  : xConnection
                                    ? 'No bookmarks yet'
                                    : 'Connect X to see saved posts'}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {activeTag
                                ? 'Try another tag or add one from a bookmark card.'
                                : xConnection
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
                                    leadingActions={
                                        <BookmarkTagPicker
                                            bookmark={bookmark}
                                        />
                                    }
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
