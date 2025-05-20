import { Head, Link } from '@inertiajs/react'
import Form from './Form'
import { GameJournalEntryType } from './types'

interface EditProps {
  game_journal_entry: GameJournalEntryType
}

export default function Edit({ game_journal_entry }: EditProps) {
  return (
    <>
      <Head title="Editing game journal entry" />

      <div className="mx-auto md:w-2/3 w-full px-8 pt-8">
        <h1 className="font-bold text-4xl">Editing game journal entry</h1>

        <Form
          game_journal_entry={game_journal_entry}
          onSubmit={(form) => {
            form.transform((data) => ({ game_journal_entry: data }))
            form.patch(`/game_journal_entries/${game_journal_entry.id}`)
          }}
          submitText="Update Game journal entry"
        />

        <Link
          href={`/game_journal_entries/${game_journal_entry.id}`}
          className="ml-2 rounded-lg py-3 px-5 bg-gray-100 inline-block font-medium"
        >
          Show this game journal entry
        </Link>
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
