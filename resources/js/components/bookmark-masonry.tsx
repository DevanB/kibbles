import { useSyncExternalStore, type ReactNode } from 'react';
import type { Bookmark } from '@/types';

const SM = '(min-width: 40rem)';
const XL = '(min-width: 80rem)';

function subscribe(onStoreChange: () => void): () => void {
    const sm = window.matchMedia(SM);
    const xl = window.matchMedia(XL);

    sm.addEventListener('change', onStoreChange);
    xl.addEventListener('change', onStoreChange);

    return () => {
        sm.removeEventListener('change', onStoreChange);
        xl.removeEventListener('change', onStoreChange);
    };
}

function columnCount(): number {
    if (window.matchMedia(XL).matches) {
        return 3;
    }

    if (window.matchMedia(SM).matches) {
        return 2;
    }

    return 1;
}

function splitColumns<T>(items: T[], count: number): T[][] {
    const columns = Array.from({ length: count }, (): T[] => []);

    items.forEach((item, index) => {
        columns[index % count]?.push(item);
    });

    return columns;
}

export function BookmarkMasonry({
    bookmarks,
    children,
}: {
    bookmarks: Bookmark[];
    children: (bookmark: Bookmark) => ReactNode;
}) {
    const columns = useSyncExternalStore(subscribe, columnCount, () => 1);

    return (
        <div
            data-test="bookmark-masonry"
            className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            {splitColumns(bookmarks, columns).map((column, columnIndex) => (
                <ul key={columnIndex} className="flex min-w-0 flex-col gap-4">
                    {column.map((bookmark) => (
                        <li key={bookmark.id}>{children(bookmark)}</li>
                    ))}
                </ul>
            ))}
        </div>
    );
}
