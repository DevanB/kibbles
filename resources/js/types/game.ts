export type GameStatus = 'backlog' | 'in_progress' | 'abandoned' | 'finished';

export type GameStatusOption = {
    value: GameStatus;
    label: string;
};

export type Game = {
    id: string;
    title: string;
    status: GameStatus;
    statusLabel: string;
};
