import { router, usePage } from '@inertiajs/react';
import { Check, Plus, Tag as TagIcon } from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    destroy,
    store,
} from '@/actions/App/Http/Controllers/LinkTagController';
import { bookmarkIconActionClassName } from '@/components/bookmark-card';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import type { SavedLink, Tag } from '@/types';

function replaceLinkTags(linkId: string, tags: Tag[]): void {
    router.replaceProp('links.data', (current: unknown) =>
        (current as SavedLink[]).map((item) =>
            item.id === linkId ? { ...item, tags } : item,
        ),
    );
}

export function LinkTagPicker({ link }: { link: SavedLink }) {
    const { tags } = usePage<{ tags: Tag[] }>().props;
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const attachedIds = new Set(link.tags.map((tag) => tag.id));
    const trimmedQuery = query.trim();

    const filtered = useMemo(() => {
        const needle = trimmedQuery.toLowerCase();

        if (needle === '') {
            return tags;
        }

        return tags.filter((tag) => tag.name.toLowerCase().includes(needle));
    }, [tags, trimmedQuery]);

    const canCreate =
        trimmedQuery !== '' &&
        !tags.some(
            (tag) => tag.name.toLowerCase() === trimmedQuery.toLowerCase(),
        );

    const toggle = (tag: Tag): void => {
        if (attachedIds.has(tag.id)) {
            router.delete(destroy.url({ link: link.id, tag: tag.id }), {
                preserveScroll: true,
                except: ['links'],
                onSuccess: () => {
                    replaceLinkTags(
                        link.id,
                        link.tags.filter((item) => item.id !== tag.id),
                    );
                },
            });

            return;
        }

        router.post(
            store.url(link.id),
            { tag_id: tag.id },
            {
                preserveScroll: true,
                except: ['links'],
                onSuccess: () => {
                    replaceLinkTags(link.id, [...link.tags, tag]);
                },
            },
        );
    };

    const create = (): void => {
        router.post(
            store.url(link.id),
            { name: trimmedQuery },
            {
                preserveScroll: true,
                except: ['links'],
                onSuccess: (page) => {
                    const created = page.props.tags.find(
                        (tag) =>
                            tag.name.toLowerCase() ===
                            trimmedQuery.toLowerCase(),
                    );

                    if (created) {
                        replaceLinkTags(link.id, [...link.tags, created]);
                    }

                    setQuery('');
                },
            },
        );
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <button
                    type="button"
                    aria-label="Tags"
                    className={bookmarkIconActionClassName}
                    data-test={`tag-link-button-${link.id}`}
                >
                    <TagIcon className="size-4" />
                </button>
            </PopoverTrigger>
            <PopoverContent
                className="w-64 p-0"
                align="end"
                data-test={`link-tag-combobox-${link.id}`}
            >
                <Command>
                    <CommandInput
                        placeholder="Filter or create a tag"
                        value={query}
                        onValueChange={setQuery}
                        data-test={`link-tag-combobox-input-${link.id}`}
                    />
                    <CommandList>
                        <CommandEmpty>No tags yet.</CommandEmpty>
                        <CommandGroup>
                            {filtered.map((tag) => (
                                <CommandItem
                                    key={tag.id}
                                    value={tag.name}
                                    onSelect={() => {
                                        toggle(tag);
                                    }}
                                    data-test={`link-tag-option-${tag.id}`}
                                >
                                    <Check
                                        className={
                                            attachedIds.has(tag.id)
                                                ? 'opacity-100'
                                                : 'opacity-0'
                                        }
                                    />
                                    {tag.name}
                                </CommandItem>
                            ))}
                            {canCreate ? (
                                <CommandItem
                                    value={`create-${trimmedQuery}`}
                                    onSelect={create}
                                    data-test={`create-link-tag-option-${link.id}`}
                                >
                                    <Plus className="size-4" />
                                    Create “{trimmedQuery}”
                                </CommandItem>
                            ) : null}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
