import { Head, Link } from '@inertiajs/react'
import GameCollection from './GameCollection'
import { GameCollectionType } from './types'

interface ShowProps {
  game_collection: GameCollectionType
  flash: { notice?: string }
}

export default function Show({ game_collection, flash }: ShowProps) {
  return (
    <>
      <Head title={`Game collection #${game_collection.id}`} />

      <div className="mx-auto md:w-2/3 w-full px-8 pt-8">
        <div className="mx-auto">
          {flash.notice && (
            <p className="py-2 px-3 bg-green-50 mb-5 text-green-500 font-medium rounded-lg inline-block">
              {flash.notice}
            </p>
          )}

          <h1 className="font-bold text-4xl">Game collection #{game_collection.id}</h1>

          <GameCollection game_collection={game_collection} />

          <Link
            href={`/game_collections/${game_collection.id}/edit`}
            className="mt-2 rounded-lg py-3 px-5 bg-gray-100 inline-block font-medium"
          >
            Edit this game collection
          </Link>
          <Link
            href="/game_collections"
            className="ml-2 rounded-lg py-3 px-5 bg-gray-100 inline-block font-medium"
          >
            Back to game collections
          </Link>
          <div className="inline-block ml-2">
            <Link
              href={`/game_collections/${game_collection.id}`}
              as="button"
              method="delete"
              className="mt-2 rounded-lg py-3 px-5 bg-gray-100 font-medium"
            >
              Destroy this game collection
            </Link>
          </div>
        </div>
      </div>
    </>
  )
}
