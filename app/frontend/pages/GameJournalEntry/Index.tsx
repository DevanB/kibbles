import { Head, Link } from '@inertiajs/react'
import { Fragment } from 'react'
import GameJournalEntry from './GameJournalEntry'
import { GameJournalEntryType } from './types'

interface IndexProps {
  game_journal_entries: GameJournalEntryType[]
  flash: { notice?: string }
}

export default function Index({ game_journal_entries, flash }: IndexProps) {
  return (
    <>
      <Head title="Game journal entries" />
      <div className="mx-auto md:w-2/3 w-full px-8 pt-8">
        {flash.notice && (
          <p className="py-2 px-3 bg-green-50 mb-5 text-green-500 font-medium rounded-lg inline-block">
            {flash.notice}
          </p>
        )}
        <div className="flex justify-between items-center">
          <h1 className="font-bold text-4xl">Game journal entries</h1>
          <Link
            href="/game_journal_entries/new"
            className="rounded-lg py-3 px-5 bg-blue-600 text-white block font-medium"
          >
            New game journal entry
          </Link>
        </div>

        <div className="min-w-full">
          {game_journal_entries.map((game_journal_entry) => (
            <Fragment key={game_journal_entry.id}>
              <GameJournalEntry game_journal_entry={game_journal_entry} />
              <p>
                <Link
                  href={`/game_journal_entries/${game_journal_entry.id}`}
                  className="ml-2 rounded-lg py-3 px-5 bg-gray-100 inline-block font-medium"
                >
                  Show this game journal entry
                </Link>
              </p>
            </Fragment>
          ))}
        </div>
      </div>
    </>
  )
}
