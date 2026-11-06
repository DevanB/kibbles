import { Form, Head, router } from '@inertiajs/react';
import { Modal, useModal } from '@inertiaui/modal-react';
import { destroy } from '@/actions/App/Http/Controllers/LinkController';
import { LinkCard } from '@/components/link-card';
import { Button } from '@/components/ui/button';
import type { SavedLink } from '@/types';

export default function Show({ link }: { link: SavedLink }) {
    const modal = useModal();

    return (
        <Modal>
            <Head title="Remove bookmark" />

            <div className="space-y-4" data-test={`link-modal-${link.id}`}>
                <div className="space-y-1">
                    <h2 className="text-lg font-semibold tracking-tight">
                        Remove this bookmark?
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        This removes the saved link from Kibbles. This cannot be
                        undone.
                    </p>
                </div>

                <LinkCard link={link} />

                <div className="flex items-center gap-2">
                    <Button
                        variant="secondary"
                        size="sm"
                        data-test={`cancel-remove-link-button-${link.id}`}
                        onClick={() => {
                            modal?.close();
                        }}
                    >
                        Cancel
                    </Button>
                    <Form
                        {...destroy.form(link)}
                        options={{
                            preserveScroll: true,
                            except: ['links'],
                        }}
                        onSuccess={() => {
                            router.replaceProp(
                                'links.data',
                                (current: unknown) =>
                                    (current as SavedLink[]).filter(
                                        (item) => item.id !== link.id,
                                    ),
                            );
                        }}
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="destructive"
                                size="sm"
                                disabled={processing}
                                data-test={`confirm-remove-link-button-${link.id}`}
                            >
                                Remove
                            </Button>
                        )}
                    </Form>
                </div>
            </div>
        </Modal>
    );
}
