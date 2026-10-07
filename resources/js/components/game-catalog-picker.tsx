import { useId, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCatalogSearch } from '@/hooks/use-catalog-search';
import type { CatalogSearchResult } from '@/types';

export default function GameCatalogPicker({
    initialTitle = '',
    initialRawgId = null,
    autoFocus = false,
    error,
}: {
    initialTitle?: string;
    initialRawgId?: number | null;
    autoFocus?: boolean;
    error?: string;
}) {
    const listId = useId();
    const [title, setTitle] = useState(initialTitle);
    const [rawgId, setRawgId] = useState<number | null>(initialRawgId);
    const [open, setOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(0);
    const { results, searching, searched } = useCatalogSearch(title);

    const showResults = open && title.trim().length >= 2 && results.length > 0;
    const showEmpty =
        open &&
        title.trim().length >= 2 &&
        searched &&
        !searching &&
        results.length === 0;

    function pick(result: CatalogSearchResult): void {
        setTitle(result.name);
        setRawgId(result.id);
        setOpen(false);
    }

    function clearLink(): void {
        setRawgId(null);
    }

    return (
        <div className="grid gap-2">
            <Label htmlFor="title">Title</Label>
            <p className="text-sm text-muted-foreground">
                Search the catalog or type your own title.
            </p>
            <div className="relative">
                <Input
                    id="title"
                    name="title"
                    required
                    autoFocus={autoFocus}
                    autoComplete="off"
                    maxLength={255}
                    placeholder="Hades"
                    value={title}
                    role="combobox"
                    aria-expanded={showResults}
                    aria-controls={listId}
                    aria-autocomplete="list"
                    aria-activedescendant={
                        showResults && results[activeIndex]
                            ? `${listId}-${results[activeIndex].id}`
                            : undefined
                    }
                    data-test="game-title-input"
                    onChange={(event) => {
                        setTitle(event.target.value);
                        setOpen(true);
                        setActiveIndex(0);
                    }}
                    onFocus={() => {
                        setOpen(true);
                    }}
                    onKeyDown={(event) => {
                        if (!showResults) {
                            return;
                        }

                        if (event.key === 'ArrowDown') {
                            event.preventDefault();
                            setActiveIndex((index) =>
                                index + 1 < results.length ? index + 1 : 0,
                            );
                        }

                        if (event.key === 'ArrowUp') {
                            event.preventDefault();
                            setActiveIndex((index) =>
                                index === 0 ? results.length - 1 : index - 1,
                            );
                        }

                        if (event.key === 'Enter' && results[activeIndex]) {
                            event.preventDefault();
                            pick(results[activeIndex]);
                        }

                        if (event.key === 'Escape') {
                            event.preventDefault();
                            setOpen(false);
                        }
                    }}
                />
                <input
                    type="hidden"
                    name="rawg_id"
                    value={rawgId ?? ''}
                    data-test="game-rawg-id"
                />
                {showResults && (
                    <ul
                        id={listId}
                        role="listbox"
                        data-test="game-catalog-results"
                        className="mt-2 max-h-64 overflow-y-auto rounded-md border border-input bg-popover text-popover-foreground shadow-md"
                    >
                        {results.map((result, index) => (
                            <li key={result.id} role="presentation">
                                <button
                                    type="button"
                                    id={`${listId}-${result.id}`}
                                    role="option"
                                    aria-selected={index === activeIndex}
                                    data-test={`game-catalog-result-${result.id}`}
                                    className={`flex w-full items-center gap-3 px-3 py-2 text-left text-sm ${
                                        index === activeIndex
                                            ? 'bg-accent text-accent-foreground'
                                            : ''
                                    }`}
                                    onMouseEnter={() => {
                                        setActiveIndex(index);
                                    }}
                                    onClick={() => {
                                        pick(result);
                                    }}
                                >
                                    {result.backgroundImage ? (
                                        <img
                                            src={result.backgroundImage}
                                            alt=""
                                            className="size-10 shrink-0 rounded object-cover"
                                        />
                                    ) : (
                                        <span
                                            aria-hidden
                                            className="size-10 shrink-0 rounded bg-muted"
                                        />
                                    )}
                                    <span className="min-w-0">
                                        <span className="block truncate font-medium">
                                            {result.name}
                                        </span>
                                        {result.releasedYear !== null && (
                                            <span className="block text-xs text-muted-foreground">
                                                {result.releasedYear}
                                            </span>
                                        )}
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
                {showEmpty && (
                    <p className="mt-2 text-sm text-muted-foreground">
                        No catalog matches. Save with this title anyway.
                    </p>
                )}
            </div>
            {rawgId !== null && (
                <div className="flex items-center justify-between gap-3">
                    <p className="text-sm text-muted-foreground">
                        Linked to the catalog. Title stays editable.
                    </p>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        data-test="clear-catalog-link-button"
                        onClick={clearLink}
                    >
                        Clear Link
                    </Button>
                </div>
            )}
            <InputError message={error} />
        </div>
    );
}
