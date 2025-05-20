import { Head, Link } from '@inertiajs/react'
import Form from './Form'
import { GameCollectionType } from './types'

interface NewProps {
  game_collection: GameCollectionType
}

export default function New({ game_collection }: NewProps) {
  return (
    <>
      <Head title="New game collection" />

      <div className="mx-auto md:w-2/3 w-full px-8 pt-8">
        <h1 className="font-bold text-4xl">New game collection</h1>

        <Form
          game_collection={game_collection}
          onSubmit={(form) => {
            form.transform((data) => ({ game_collection: data }))
            form.post('/game_collections')
          }}
          submitText="Create Game collection"
        />

        <Link
          href="/game_collections"
          className="ml-2 rounded-lg py-3 px-5 bg-gray-100 inline-block font-medium"
        >
          Back to game collections
        </Link>
      </div>
    </>
  )
}
