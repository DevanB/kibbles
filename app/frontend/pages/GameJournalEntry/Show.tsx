import { Head, Link } from '@inertiajs/react'
import GameJournalEntry from './GameJournalEntry'
import { GameJournalEntryType } from './types'

interface ShowProps {
  game_journal_entry: GameJournalEntryType
  flash: { notice?: string }
}

export default function Show({ game_journal_entry, flash }: ShowProps) {
  return (
    <>
      <Head title={`Game journal entry #${game_journal_entry.id}`} />

      <div className="mx-auto md:w-2/3 w-full px-8 pt-8">
        <div className="mx-auto">
          {flash.notice && (
            <p className="py-2 px-3 bg-green-50 mb-5 text-green-500 font-medium rounded-lg inline-block">
              {flash.notice}
            </p>
          )}

          <h1 className="font-bold text-4xl">Game journal entry #{game_journal_entry.id}</h1>

          <GameJournalEntry game_journal_entry={game_journal_entry} />

          <Link
            href={`/game_journal_entries/${game_journal_entry.id}/edit`}
            className="mt-2 rounded-lg py-3 px-5 bg-gray-100 inline-block font-medium"
          >
            Edit this game journal entry
          </Link>
          <Link
            href="/game_journal_entries"
            className="ml-2 rounded-lg py-3 px-5 bg-gray-100 inline-block font-medium"
          >
            Back to game journal entries
          </Link>
          <div className="inline-block ml-2">
            <Link
              href={`/game_journal_entries/${game_journal_entry.id}`}
              as="button"
              method="delete"
              className="mt-2 rounded-lg py-3 px-5 bg-gray-100 font-medium"
            >
              Destroy this game journal entry
            </Link>
          </div>
        </div>
      </div>
    </>
  )
}
