import { Form, Head } from '@inertiajs/react';
import { Modal, useModal } from '@inertiaui/modal-react';
import { destroy } from '@/actions/App/Http/Controllers/TagController';
import { Button } from '@/components/ui/button';
import type { Tag } from '@/types';

export default function Show({ tag }: { tag: Tag }) {
    const modal = useModal();

    return (
        <Modal>
            <Head title={`Delete ${tag.name}`} />

            <div className="space-y-4" data-test={`tag-modal-${tag.id}`}>
                <div className="space-y-1">
                    <h2 className="text-lg font-semibold tracking-tight">
                        Delete {tag.name}?
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        This removes the tag from every bookmark. The bookmarks
                        stay. This cannot be undone.
                    </p>
                </div>

                <div className="flex items-center gap-2">
                    <Button
                        variant="secondary"
                        size="sm"
                        data-test={`cancel-delete-tag-button-${tag.id}`}
                        onClick={() => {
                            modal?.close();
                        }}
                    >
                        Cancel
                    </Button>
                    <Form {...destroy.form(tag)}>
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="destructive"
                                size="sm"
                                disabled={processing}
                                data-test={`confirm-delete-tag-button-${tag.id}`}
                            >
                                Delete tag
                            </Button>
                        )}
                    </Form>
                </div>
            </div>
        </Modal>
    );
}
