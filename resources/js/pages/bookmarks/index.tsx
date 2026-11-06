import { Head, InfiniteScroll, Link, setLayoutProps } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { Bookmark as BookmarkIcon, Plus } from 'lucide-react';
import { create } from '@/actions/App/Http/Controllers/LinkController';
import Heading from '@/components/heading';
import { LinkCard } from '@/components/link-card';
import { LinkTagPicker } from '@/components/link-tag-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/bookmarks';
import { index as tagsIndex } from '@/routes/tags';
import type { BreadcrumbItem, LinkPage, Tag } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Bookmarks',
        href: index(),
    },
];

export default function Index({
    links,
    activeTag,
}: {
    links: LinkPage;
    activeTag: Tag | null;
}) {
    setLayoutProps({ breadcrumbs });

    return (
        <>
            <Head title="Bookmarks" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <Heading
                            title="Bookmarks"
                            description="Links you saved from the web"
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
                        <Button asChild data-test="add-link-button">
                            <ModalLink href={create.url()} navigate>
                                <Plus />
                                Add link
                            </ModalLink>
                        </Button>
                    </div>
                </div>

                {links.data.length === 0 ? (
                    <div
                        data-test="links-empty"
                        className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
                    >
                        <BookmarkIcon className="mb-3 size-8 text-muted-foreground" />
                        <p className="text-lg font-medium">
                            {activeTag?.id === 'untagged'
                                ? 'No untagged bookmarks'
                                : activeTag
                                  ? `No bookmarks tagged ${activeTag.name}`
                                  : 'No bookmarks yet'}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {activeTag
                                ? 'Try another tag or add one from a bookmark card.'
                                : 'Paste a URL to keep it here.'}
                        </p>
                    </div>
                ) : (
                    <InfiniteScroll
                        data="links"
                        buffer={400}
                        loading={() => (
                            <p
                                data-test="links-load-more"
                                className="py-4 text-center text-sm text-muted-foreground"
                            >
                                Loading more…
                            </p>
                        )}
                    >
                        <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {links.data.map((link) => (
                                <li key={link.id}>
                                    <LinkCard
                                        link={link}
                                        leadingActions={
                                            <LinkTagPicker link={link} />
                                        }
                                    />
                                </li>
                            ))}
                        </ul>
                    </InfiniteScroll>
                )}
            </div>
        </>
    );
}

Index.layout = [AppLayout, { breadcrumbs }];
