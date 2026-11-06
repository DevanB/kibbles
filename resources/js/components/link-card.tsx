import { Form } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { ExternalLink, RotateCw, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { show } from '@/actions/App/Http/Controllers/LinkController';
import { store as retryPreview } from '@/actions/App/Http/Controllers/LinkPreviewController';
import { bookmarkIconActionClassName } from '@/components/bookmark-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { SavedLink } from '@/types';

export function LinkCard({
    link,
    leadingActions,
}: {
    link: SavedLink;
    leadingActions?: ReactNode;
}) {
    const title = link.title ?? link.url;

    return (
        <article
            data-test={`link-${link.id}`}
            className="flex flex-col overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border"
        >
            {link.imageUrl ? (
                <a href={link.url} target="_blank" rel="noreferrer">
                    <img
                        src={link.imageUrl}
                        alt=""
                        loading="lazy"
                        className="aspect-2/1 w-full object-cover"
                        data-test={`link-image-${link.id}`}
                    />
                </a>
            ) : null}

            <div className="flex flex-1 flex-col gap-3 p-4">
                <div className="space-y-1">
                    {link.siteName ? (
                        <p className="text-xs font-medium text-muted-foreground">
                            {link.siteName}
                        </p>
                    ) : null}
                    <a
                        href={link.url}
                        target="_blank"
                        rel="noreferrer"
                        className="text-base leading-snug font-semibold tracking-tight hover:underline"
                    >
                        {title}
                    </a>
                    {link.description ? (
                        <p className="line-clamp-3 text-sm text-muted-foreground">
                            {link.description}
                        </p>
                    ) : null}
                </div>

                {link.previewStatus === 'pending' ? (
                    <p
                        className="text-sm text-muted-foreground"
                        data-test={`link-pending-${link.id}`}
                    >
                        Fetching preview…
                    </p>
                ) : null}

                {link.tags.length > 0 ? (
                    <div className="flex flex-wrap gap-1">
                        {link.tags.map((tag) => (
                            <Badge
                                key={tag.id}
                                variant="secondary"
                                data-test={`link-tag-${link.id}-${tag.id}`}
                            >
                                {tag.name}
                            </Badge>
                        ))}
                    </div>
                ) : null}

                <div className="mt-auto flex items-center justify-end gap-1">
                    {leadingActions}
                    {link.previewStatus === 'failed' ? (
                        <Form
                            {...retryPreview.form(link)}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="ghost"
                                    size="sm"
                                    disabled={processing}
                                    data-test={`retry-link-preview-${link.id}`}
                                >
                                    <RotateCw className="size-4" />
                                    Retry
                                </Button>
                            )}
                        </Form>
                    ) : null}
                    <a
                        href={link.url}
                        target="_blank"
                        rel="noreferrer"
                        aria-label="Open link"
                        className={bookmarkIconActionClassName}
                        data-test={`open-link-${link.id}`}
                    >
                        <ExternalLink className="size-4" />
                    </a>
                    <ModalLink
                        href={show.url(link)}
                        navigate
                        aria-label="Remove"
                        className={bookmarkIconActionClassName}
                        data-test={`remove-link-button-${link.id}`}
                    >
                        <Trash2 className="size-4" />
                    </ModalLink>
                </div>
            </div>
        </article>
    );
}
