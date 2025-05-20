import { Head, Link } from '@inertiajs/react'
import { Fragment } from 'react'
import Game from './Game'
import { GameType } from './types'

interface IndexProps {
  games: GameType[]
  flash: { notice?: string }
}

export default function Index({ games, flash }: IndexProps) {
  return (
    <>
      <Head title="Games" />
      <div className="mx-auto md:w-2/3 w-full px-8 pt-8">
        {flash.notice && (
          <p className="py-2 px-3 bg-green-50 mb-5 text-green-500 font-medium rounded-lg inline-block">
            {flash.notice}
          </p>
        )}
        <div className="flex justify-between items-center">
          <h1 className="font-bold text-4xl">Games</h1>
          <Link
            href="/games/new"
            className="rounded-lg py-3 px-5 bg-blue-600 text-white block font-medium"
          >
            New game
          </Link>
        </div>

        <div className="min-w-full">
          {games.map((game) => (
            <Fragment key={game.id}>
              <Game game={game} />
              <p>
                <Link
                  href={`/games/${game.id}`}
                  className="ml-2 rounded-lg py-3 px-5 bg-gray-100 inline-block font-medium"
                >
                  Show this game
                </Link>
              </p>
            </Fragment>
          ))}
        </div>
      </div>
    </>
  )
}
