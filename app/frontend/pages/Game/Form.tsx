import { useForm } from '@inertiajs/react'
import { FormEvent } from 'react'
import { GameFormType, GameType } from './types'

// Temporary fix for InertiaFormProps not being exported from @inertiajs/react
type InertiaFormProps<TForm extends Record<string, any>> = ReturnType<typeof useForm<TForm>>

interface FormProps {
  game: GameType
  onSubmit: (form: InertiaFormProps<GameFormType>) => void
  submitText: string
}

export default function Form({ game, onSubmit, submitText }: FormProps) {
  const form = useForm<GameFormType>({
    name: game.name,
    status: game.status,
  })
  const { data, setData, errors, processing } = form

  const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    onSubmit(form)
  }

  return (
    <form onSubmit={handleSubmit} className="contents">
      <div className="my-5">
        <label htmlFor="name">Name</label>
        <input
          type="text"
          name="name"
          id="name"
          value={data.name}
          className="block shadow rounded-md border border-gray-400 outline-none px-3 py-2 mt-2 w-full"
          onChange={(e) => setData('name', e.target.value)}
        />
        {errors.name && (
          <div className="text-red-500 px-3 py-2 font-medium">
            {errors.name}
          </div>
        )}
      </div>

      <div className="my-5">
        <label htmlFor="status">Status</label>
        <input
          type="number"
          name="status"
          id="status"
          value={data.status}
          className="block shadow rounded-md border border-gray-400 outline-none px-3 py-2 mt-2 w-full"
          onChange={(e) => setData('status', parseInt(e.target.value))}
        />
        {errors.status && (
          <div className="text-red-500 px-3 py-2 font-medium">
            {errors.status}
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
