import { Head, setLayoutProps } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { edit, show } from '@/actions/App/Http/Controllers/TagController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { index as bookmarks } from '@/routes/bookmarks';
import { index } from '@/routes/tags';
import type { BreadcrumbItem, Tag } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Bookmarks',
        href: bookmarks(),
    },
    {
        title: 'Tags',
        href: index(),
    },
];

export default function Index({ tags }: { tags: Tag[] }) {
    setLayoutProps({ breadcrumbs });

    return (
        <>
            <Head title="Tags" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Tags"
                    description="Rename or delete tags. Bookmarks stay when you delete a tag."
                />

                {tags.length === 0 ? (
                    <div
                        data-test="tags-empty"
                        className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
                    >
                        <p className="text-lg font-medium">No tags yet</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create a tag from a bookmark card.
                        </p>
                    </div>
                ) : (
                    <ul className="divide-y rounded-xl border">
                        {tags.map((tag) => (
                            <li
                                key={tag.id}
                                className="flex flex-wrap items-center justify-between gap-3 p-4"
                                data-test={`tag-row-${tag.id}`}
                            >
                                <p className="font-medium">{tag.name}</p>
                                <div className="flex items-center gap-2">
                                    <Button asChild variant="outline" size="sm">
                                        <ModalLink
                                            href={edit.url(tag)}
                                            navigate
                                            data-test={`rename-tag-button-${tag.id}`}
                                        >
                                            Rename
                                        </ModalLink>
                                    </Button>
                                    <Button asChild variant="ghost" size="sm">
                                        <ModalLink
                                            href={show.url(tag)}
                                            navigate
                                            data-test={`delete-tag-button-${tag.id}`}
                                        >
                                            Delete
                                        </ModalLink>
                                    </Button>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

Index.layout = [AppLayout, { breadcrumbs }];
