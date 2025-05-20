import { Head, Link } from '@inertiajs/react'
import Form from './Form'
import { GameJournalEntryType } from './types'

interface NewProps {
  game_journal_entry: GameJournalEntryType
}

export default function New({ game_journal_entry }: NewProps) {
  return (
    <>
      <Head title="New game journal entry" />

      <div className="mx-auto md:w-2/3 w-full px-8 pt-8">
        <h1 className="font-bold text-4xl">New game journal entry</h1>

        <Form
          game_journal_entry={game_journal_entry}
          onSubmit={(form) => {
            form.transform((data) => ({ game_journal_entry: data }))
            form.post('/game_journal_entries')
          }}
          submitText="Create Game journal entry"
        />

        <Link
          href="/game_journal_entries"
          className="ml-2 rounded-lg py-3 px-5 bg-gray-100 inline-block font-medium"
        >
          Back to game journal entries
        </Link>
      </div>
    </>
  )
}
