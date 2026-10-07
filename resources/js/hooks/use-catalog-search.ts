import { useHttp } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { search } from '@/routes/rawg/games';
import type { CatalogSearchResult } from '@/types';

type CatalogSearchResponse = {
    results: CatalogSearchResult[];
};

export function useCatalogSearch(query: string): {
    results: CatalogSearchResult[];
    searching: boolean;
    searched: boolean;
} {
    const trimmed = query.trim();
    const enabled = trimmed.length >= 2;
    const { submit, processing } = useHttp<
        Record<string, never>,
        CatalogSearchResponse
    >();
    const [results, setResults] = useState<CatalogSearchResult[]>([]);
    const [completedQuery, setCompletedQuery] = useState<string | null>(null);
    const requestId = useRef(0);

    useEffect(() => {
        if (!enabled) {
            return;
        }

        const timeout = window.setTimeout(() => {
            const id = ++requestId.current;

            void submit(search.get({ query: { query: trimmed } }))
                .then((response) => {
                    if (id !== requestId.current) {
                        return;
                    }

                    setResults(response.results);
                    setCompletedQuery(trimmed);
                })
                .catch(() => {
                    if (id !== requestId.current) {
                        return;
                    }

                    setResults([]);
                    setCompletedQuery(trimmed);
                });
        }, 300);

        return () => {
            window.clearTimeout(timeout);
        };
    }, [enabled, submit, trimmed]);

    return {
        results: enabled ? results : [],
        searching: enabled && processing,
        searched: enabled && completedQuery === trimmed,
    };
}
