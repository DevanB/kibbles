import { GameJournalEntryType } from './types'

interface GameJournalEntryProps {
  game_journal_entry: GameJournalEntryType
}

export default function GameJournalEntry({ game_journal_entry }: GameJournalEntryProps) {
  return (
    <div>
      <p className="my-5">
        <strong className="block font-medium mb-1">Game:</strong>
        {game_journal_entry.game_id?.toString()}
      </p>
      <p className="my-5">
        <strong className="block font-medium mb-1">Text:</strong>
        {game_journal_entry.text?.toString()}
      </p>
    </div>
  )
}
