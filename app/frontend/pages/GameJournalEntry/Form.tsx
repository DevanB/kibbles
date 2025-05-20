import { useForm } from '@inertiajs/react'
import { FormEvent } from 'react'
import { GameJournalEntryFormType, GameJournalEntryType } from './types'

// Temporary fix for InertiaFormProps not being exported from @inertiajs/react
type InertiaFormProps<TForm extends Record<string, any>> = ReturnType<typeof useForm<TForm>>

interface FormProps {
  game_journal_entry: GameJournalEntryType
  onSubmit: (form: InertiaFormProps<GameJournalEntryFormType>) => void
  submitText: string
}

export default function Form({ game_journal_entry, onSubmit, submitText }: FormProps) {
  const form = useForm<GameJournalEntryFormType>({
    game_id: game_journal_entry.game_id,
    text: game_journal_entry.text,
  })
  const { data, setData, errors, processing } = form

  const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    onSubmit(form)
  }

  return (
    <form onSubmit={handleSubmit} className="contents">
      <div className="my-5">
        <label htmlFor="game">Game</label>
        <input
          type="text"
          name="game"
          id="game"
          value={data.game_id}
          className="block shadow rounded-md border border-gray-400 outline-none px-3 py-2 mt-2 w-full"
          onChange={(e) => setData('game_id', e.target.value)}
        />
        {errors.game_id && (
          <div className="text-red-500 px-3 py-2 font-medium">
            {errors.game_id}
          </div>
        )}
      </div>

      <div className="my-5">
        <label htmlFor="text">Text</label>
        <textarea
          name="text"
          id="text"
          value={data.text}
          rows={4}
          className="block shadow rounded-md border border-gray-400 outline-none px-3 py-2 mt-2 w-full"
          onChange={(e) => setData('text', e.target.value)}
        />
        {errors.text && (
          <div className="text-red-500 px-3 py-2 font-medium">
            {errors.text}
          </div>
        )}
      </div>

      <div className="inline">
        <button
          type="submit"
          disabled={processing}
          className="rounded-lg py-3 px-5 bg-blue-600 text-white inline-block font-medium cursor-pointer"
        >
          {submitText}
        </button>
      </div>
    </form>
  )
}
