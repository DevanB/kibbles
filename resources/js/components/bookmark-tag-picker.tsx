import { router, usePage } from '@inertiajs/react';
import { Check, Plus, Tag as TagIcon } from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    destroy,
    store,
} from '@/actions/App/Http/Controllers/BookmarkTagController';
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
import type { Bookmark, Tag } from '@/types';

function replaceBookmarkTags(bookmarkId: string, tags: Tag[]): void {
    router.replaceProp('bookmarks.data', (current: unknown) =>
        (current as Bookmark[]).map((item) =>
            item.id === bookmarkId ? { ...item, tags } : item,
        ),
    );
}

export function BookmarkTagPicker({
    bookmark,
    open: controlledOpen,
    onOpenChange,
    onTagsChange,
}: {
    bookmark: Bookmark;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    onTagsChange?: (tags: Tag[]) => void;
}) {
    const { tags } = usePage<{ tags: Tag[] }>().props;
    const [uncontrolledOpen, setUncontrolledOpen] = useState(false);
    const open =
        onOpenChange === undefined
            ? uncontrolledOpen
            : (controlledOpen ?? false);
    const setOpen = onOpenChange ?? setUncontrolledOpen;
    const [query, setQuery] = useState('');
    const attachedIds = new Set(bookmark.tags.map((tag) => tag.id));
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

    const applyTags = (nextTags: Tag[]): void => {
        if (onTagsChange) {
            onTagsChange(nextTags);

            return;
        }

        replaceBookmarkTags(bookmark.id, nextTags);
    };

    const toggle = (tag: Tag): void => {
        if (attachedIds.has(tag.id)) {
            router.delete(destroy.url({ bookmark: bookmark.id, tag: tag.id }), {
                preserveScroll: true,
                except: ['bookmarks'],
                onSuccess: () => {
                    applyTags(
                        bookmark.tags.filter((item) => item.id !== tag.id),
                    );
                },
            });

            return;
        }

        router.post(
            store.url(bookmark.id),
            { tag_id: tag.id },
            {
                preserveScroll: true,
                except: ['bookmarks'],
                onSuccess: () => {
                    applyTags([...bookmark.tags, tag]);
                },
            },
        );
    };

    const create = (): void => {
        router.post(
            store.url(bookmark.id),
            { name: trimmedQuery },
            {
                preserveScroll: true,
                except: ['bookmarks'],
                onSuccess: (page) => {
                    const created = page.props.tags.find(
                        (tag) =>
                            tag.name.toLowerCase() ===
                            trimmedQuery.toLowerCase(),
                    );

                    if (created) {
                        applyTags([...bookmark.tags, created]);
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
                    data-test={`tag-bookmark-button-${bookmark.id}`}
                >
                    <TagIcon className="size-4" />
                </button>
            </PopoverTrigger>
            <PopoverContent
                className="w-64 p-0"
                align="end"
                data-test={`tag-combobox-${bookmark.id}`}
            >
                <Command>
                    <CommandInput
                        placeholder="Filter or create a tag"
                        value={query}
                        onValueChange={setQuery}
                        data-test={`tag-combobox-input-${bookmark.id}`}
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
                                    data-test={`tag-option-${tag.id}`}
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
                                    data-test={`create-tag-option-${bookmark.id}`}
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
