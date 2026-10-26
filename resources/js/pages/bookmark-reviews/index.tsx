import { Head, router, setLayoutProps, useForm } from '@inertiajs/react';
import { Bookmark as BookmarkIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import { destroy } from '@/actions/App/Http/Controllers/BookmarkController';
import { store } from '@/actions/App/Http/Controllers/BookmarkReviewController';
import { BookmarkCard } from '@/components/bookmark-card';
import { BookmarkTagPicker } from '@/components/bookmark-tag-picker';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/bookmark-reviews';
import type { Bookmark, BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Review',
        href: index(),
    },
];

function isTypingTarget(target: EventTarget | null): boolean {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    if (target.isContentEditable) {
        return true;
    }

    const tag = target.tagName;

    return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
}

function ReviewCard({ bookmark }: { bookmark: Bookmark }) {
    const [tagOpen, setTagOpen] = useState(false);
    const keepForm = useForm({ bookmark_id: bookmark.id });
    const removeForm = useForm({});
    const busy = keepForm.processing || removeForm.processing;

    const keep = (): void => {
        if (busy) {
            return;
        }

        keepForm.post(store.url());
    };

    const remove = (): void => {
        if (busy) {
            return;
        }

        removeForm.delete(destroy.url(bookmark.id));
    };

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent): void => {
            if (
                event.defaultPrevented ||
                event.metaKey ||
                event.ctrlKey ||
                event.altKey ||
                event.repeat ||
                isTypingTarget(event.target)
            ) {
                return;
            }

            const key = event.key.toLowerCase();

            if (key === 't') {
                event.preventDefault();
                setTagOpen(true);

                return;
            }

            if (key === 'k') {
                event.preventDefault();
                keep();

                return;
            }

            if (key === 'x') {
                event.preventDefault();
                remove();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => {
            window.removeEventListener('keydown', onKeyDown);
        };
    });

    return (
        <div className="mx-auto w-full max-w-xl space-y-4">
            <BookmarkCard
                bookmark={bookmark}
                leadingActions={
                    <BookmarkTagPicker
                        bookmark={bookmark}
                        open={tagOpen}
                        onOpenChange={setTagOpen}
                        onTagsChange={(tags) => {
                            router.replaceProp(
                                'bookmark',
                                (current: unknown) => ({
                                    ...(current as Bookmark),
                                    tags,
                                }),
                            );
                        }}
                    />
                }
            />

            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-muted-foreground">
                    k keep · x remove · t tags
                </p>
                <div className="flex items-center gap-2">
                    <Button
                        type="button"
                        onClick={keep}
                        disabled={busy}
                        data-test="keep-bookmark-button"
                    >
                        Keep
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        onClick={remove}
                        disabled={busy}
                        data-test="remove-review-button"
                    >
                        Remove
                    </Button>
                </div>
            </div>
        </div>
    );
}

export default function Index({
    bookmark,
    remaining,
    total,
}: {
    bookmark: Bookmark | null;
    remaining: number;
    total: number;
}) {
    setLayoutProps({ breadcrumbs });

    return (
        <>
            <Head title="Review" />

            <div
                className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
                data-test="bookmark-reviews-page"
            >
                <div className="space-y-2">
                    <Heading
                        title="Review"
                        description="Tag, keep, or remove one bookmark at a time."
                    />
                    {bookmark ? (
                        <p
                            className="text-sm text-muted-foreground"
                            data-test="bookmark-reviews-progress"
                        >
                            {remaining} left · {total} bookmarks
                        </p>
                    ) : null}
                </div>

                {bookmark ? (
                    <ReviewCard key={bookmark.id} bookmark={bookmark} />
                ) : (
                    <div
                        data-test="bookmark-reviews-empty"
                        className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
                    >
                        <BookmarkIcon className="mb-3 size-8 text-muted-foreground" />
                        <p className="text-lg font-medium">All caught up</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Every bookmark has been reviewed.
                        </p>
                    </div>
                )}
            </div>
        </>
    );
}

Index.layout = [AppLayout, { breadcrumbs }];
