import { Head } from '@inertiajs/react'
import Form from './Form'
import { GameType } from './types'

interface NewProps {
  game: GameType
}

export default function New({ game }: NewProps) {
  return (
    <>
      <Head title="New game" />

      <div className="mx-auto md:w-2/3 w-full px-8 pt-8">
        <h1 className="font-bold text-4xl">New Game</h1>

        <Form
          game={game}
          onSubmit={(form) => {
            form.transform((data) => ({ game: data }))
            form.post('/games')
          }}
          submitText="Create Game"
        />
      </div>
    </>
  )
}
