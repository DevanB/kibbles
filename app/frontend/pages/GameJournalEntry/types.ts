export interface GameJournalEntryType {
  id: number
  game_id: string
  text: string
}

export type GameJournalEntryFormType = Omit<GameJournalEntryType, 'id'>
