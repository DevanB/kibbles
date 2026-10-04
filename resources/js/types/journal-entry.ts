import type { Game } from './game';

export type JournalEntry = {
    id: string;
    body: string;
    createdAt: string;
    updatedAt: string;
};

export type RecentJournalEntry = {
    id: string;
    preview: string;
    createdAt: string;
    game: Game;
};
