import { Form, Head } from '@inertiajs/react';
import { Modal, useModal } from '@inertiaui/modal-react';
import { destroy } from '@/actions/App/Http/Controllers/BookmarkController';
import { BookmarkCard } from '@/components/bookmark-card';
import { Button } from '@/components/ui/button';
import type { Bookmark } from '@/types';

export default function Show({ bookmark }: { bookmark: Bookmark }) {
    const modal = useModal();

    return (
        <Modal>
            <Head title="Remove bookmark" />

            <div
                className="space-y-4"
                data-test={`bookmark-modal-${bookmark.id}`}
            >
                <div className="space-y-1">
                    <h2 className="text-lg font-semibold tracking-tight">
                        Remove this bookmark?
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        This removes the post from your X bookmarks and from
                        Kibbles. This cannot be undone.
                    </p>
                </div>

                <BookmarkCard bookmark={bookmark} />

                <div className="flex items-center gap-2">
                    <Button
                        variant="secondary"
                        size="sm"
                        data-test={`cancel-remove-bookmark-button-${bookmark.id}`}
                        onClick={() => {
                            modal?.close();
                        }}
                    >
                        Cancel
                    </Button>
                    <Form {...destroy.form(bookmark)}>
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="destructive"
                                size="sm"
                                disabled={processing}
                                data-test={`confirm-remove-bookmark-button-${bookmark.id}`}
                            >
                                Remove bookmark
                            </Button>
                        )}
                    </Form>
                </div>
            </div>
        </Modal>
    );
}
