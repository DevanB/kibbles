export type PlaySession = {
    id: string;
    startedAt: string;
    endedAt: string | null;
    durationMinutes: number | null;
    journalEntryId: string | null;
};

export type OpenPlaySession = PlaySession & {
    gameId: string;
    gameTitle: string;
};
