import { Head, Link } from '@inertiajs/react'
import { Fragment } from 'react'
import GameCollection from './GameCollection'
import { GameCollectionType } from './types'

interface IndexProps {
  game_collections: GameCollectionType[]
  flash: { notice?: string }
}

export default function Index({ game_collections, flash }: IndexProps) {
  return (
    <>
      <Head title="Game collections" />
      <div className="mx-auto md:w-2/3 w-full px-8 pt-8">
        {flash.notice && (
          <p className="py-2 px-3 bg-green-50 mb-5 text-green-500 font-medium rounded-lg inline-block">
            {flash.notice}
          </p>
        )}
        <div className="flex justify-between items-center">
          <h1 className="font-bold text-4xl">Game collections</h1>
          <Link
            href="/game_collections/new"
            className="rounded-lg py-3 px-5 bg-blue-600 text-white block font-medium"
          >
            New game collection
          </Link>
        </div>

        <div className="min-w-full">
          {game_collections.map((game_collection) => (
            <Fragment key={game_collection.id}>
              <GameCollection game_collection={game_collection} />
              <p>
                <Link
                  href={`/game_collections/${game_collection.id}`}
                  className="ml-2 rounded-lg py-3 px-5 bg-gray-100 inline-block font-medium"
                >
                  Show this game collection
                </Link>
              </p>
            </Fragment>
          ))}
        </div>
      </div>
    </>
  )
}
