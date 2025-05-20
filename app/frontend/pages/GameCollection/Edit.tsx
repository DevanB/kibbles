import { Head, Link } from '@inertiajs/react'
import Form from './Form'
import { GameCollectionType } from './types'

interface EditProps {
  game_collection: GameCollectionType
}

export default function Edit({ game_collection }: EditProps) {
  return (
    <>
      <Head title="Editing game collection" />

      <div className="mx-auto md:w-2/3 w-full px-8 pt-8">
        <h1 className="font-bold text-4xl">Editing game collection</h1>

        <Form
          game_collection={game_collection}
          onSubmit={(form) => {
            form.transform((data) => ({ game_collection: data }))
            form.patch(`/game_collections/${game_collection.id}`)
          }}
          submitText="Update Game collection"
        />

        <Link
          href={`/game_collections/${game_collection.id}`}
          className="ml-2 rounded-lg py-3 px-5 bg-gray-100 inline-block font-medium"
        >
          Show this game collection
        </Link>
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
