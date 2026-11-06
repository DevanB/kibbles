import { Form, Head } from '@inertiajs/react';
import { Modal } from '@inertiaui/modal-react';
import { store } from '@/actions/App/Http/Controllers/LinkController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function Create() {
    return (
        <Modal>
            <Head title="Add link" />

            <div className="space-y-4">
                <h2 className="text-lg font-semibold tracking-tight">
                    Add link
                </h2>

                <Form
                    {...store.form()}
                    className="space-y-4"
                    disableWhileProcessing
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="space-y-2">
                                <Label htmlFor="url">URL</Label>
                                <Input
                                    id="url"
                                    name="url"
                                    type="url"
                                    required
                                    autoFocus
                                    placeholder="https://"
                                    data-test="link-url-input"
                                />
                                <InputError message={errors.url} />
                            </div>

                            <Button
                                disabled={processing}
                                data-test="save-link-button"
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
