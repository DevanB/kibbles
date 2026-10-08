import { Form } from '@inertiajs/react';
import type { ComponentProps, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

type Props = {
    title: string;
    description: string;
    confirmLabel: string;
    confirmTest: string;
    cancelTest: string;
    form: Omit<ComponentProps<typeof Form>, 'children'>;
    trigger?: ReactNode;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
};

export default function DeleteConfirmationDialog({
    title,
    description,
    confirmLabel,
    confirmTest,
    cancelTest,
    form,
    trigger,
    open,
    onOpenChange,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            {trigger ? <DialogTrigger asChild>{trigger}</DialogTrigger> : null}
            <DialogContent
                onCloseAutoFocus={(event) => {
                    event.preventDefault();
                }}
                onInteractOutside={(event) => {
                    event.preventDefault();
                }}
            >
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary" data-test={cancelTest}>
                            Cancel
                        </Button>
                    </DialogClose>
                    <Form
                        {...form}
                        onSuccess={(page) => {
                            onOpenChange?.(false);
                            form.onSuccess?.(page);
                        }}
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                                data-test={confirmTest}
                            >
                                {confirmLabel}
                            </Button>
                        )}
                    </Form>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
