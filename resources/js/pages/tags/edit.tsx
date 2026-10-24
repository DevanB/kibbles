import { Form, Head } from '@inertiajs/react';
import { Modal } from '@inertiaui/modal-react';
import { update } from '@/actions/App/Http/Controllers/TagController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { Tag } from '@/types';

export default function Edit({ tag }: { tag: Tag }) {
    return (
        <Modal>
            <Head title={`Rename ${tag.name}`} />

            <div className="space-y-4" data-test={`tag-edit-modal-${tag.id}`}>
                <h2 className="text-lg font-semibold tracking-tight">
                    Rename tag
                </h2>

                <Form
                    {...update.form(tag)}
                    className="space-y-4"
                    disableWhileProcessing
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={tag.name}
                                    autoFocus
                                    data-test={`tag-name-input-${tag.id}`}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <Button
                                disabled={processing}
                                data-test={`save-tag-button-${tag.id}`}
                            >
                                {processing && <Spinner />}
                                Save
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </Modal>
    );
}
