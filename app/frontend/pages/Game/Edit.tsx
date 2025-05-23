import { Head, Link } from '@inertiajs/react'
import Form from './Form'
import { GameType } from './types'
import { Button } from '@/components/ui/button'

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

        <Button asChild variant="outline" className="ml-2">
          <Link href={`/games/${game.id}`}>
            Cancel
          </Link>
        </Button>
      </div>
    </>
  )
}
