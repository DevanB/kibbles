import { Head, Link } from '@inertiajs/react'
import Form from './Form'
import { GameType } from './types'

interface EditProps {
  game: GameType
}

export default function Edit({ game }: EditProps) {
  return (
    <>
      <Head title="Editing game" />

      <div className="mx-auto md:w-2/3 w-full px-8 pt-8">
        <h1 className="font-bold text-4xl">Editing game</h1>

        <Form
          game={game}
          onSubmit={(form) => {
            form.transform((data) => ({ game: data }))
            form.patch(`/games/${game.id}`)
          }}
          submitText="Update Game"
        />

        <Link
          href={`/games/${game.id}`}
          className="ml-2 rounded-lg py-3 px-5 bg-gray-100 inline-block font-medium"
        >
          Show this game
        </Link>
        <Link
          href="/games"
          className="ml-2 rounded-lg py-3 px-5 bg-gray-100 inline-block font-medium"
        >
          Back to games
        </Link>
      </div>
    </>
  )
}
