import { Masonry, type RenderComponentProps } from 'masonic';
import {
    useEffect,
    useMemo,
    useRef,
    useState,
    useSyncExternalStore,
    type ReactNode,
} from 'react';
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

function usePositionerReset(ids: string[]): number {
    const [generation, setGeneration] = useState(0);
    const [seen, setSeen] = useState(ids);

    if (seen.some((id) => !ids.includes(id))) {
        setGeneration((current) => current + 1);
        setSeen(ids);
    } else if (seen.length !== ids.length) {
        setSeen(ids);
    }

    return generation;
}

export function BookmarkMasonry({
    bookmarks,
    children,
}: {
    bookmarks: Bookmark[];
    children: (bookmark: Bookmark) => ReactNode;
}) {
    const columns = useSyncExternalStore(subscribe, columnCount, () => 1);
    const childrenRef = useRef(children);
    const positionerReset = usePositionerReset(
        bookmarks.map((bookmark) => bookmark.id),
    );

    useEffect(() => {
        childrenRef.current = children;
    });

    const render = useMemo(() => {
        function Cell({ data }: RenderComponentProps<Bookmark>) {
            return childrenRef.current(data);
        }

        return Cell;
    }, []);

    return (
        <div data-test="bookmark-masonry">
            <Masonry
                key={positionerReset}
                items={bookmarks}
                columnCount={columns}
                columnGutter={16}
                rowGutter={16}
                overscanBy={8}
                itemHeightEstimate={280}
                ssrWidth={1280}
                ssrHeight={720}
                itemKey={(item) => item.id}
                render={render}
            />
        </div>
    );
}
