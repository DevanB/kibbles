import { Head, Link } from '@inertiajs/react'
import Game from './Game'
import { GameType } from './types'

interface ShowProps {
  game: GameType
  flash: { notice?: string }
}

export default function Show({ game, flash }: ShowProps) {
  return (
    <>
      <Head title={`Game #${game.id}`} />

      <div className="mx-auto md:w-2/3 w-full px-8 pt-8">
        <div className="mx-auto">
          {flash.notice && (
            <p className="py-2 px-3 bg-green-50 mb-5 text-green-500 font-medium rounded-lg inline-block">
              {flash.notice}
            </p>
          )}

          <h1 className="font-bold text-4xl">Game #{game.id}</h1>

          <Game game={game} />

          <Link
            href={`/games/${game.id}/edit`}
            className="mt-2 rounded-lg py-3 px-5 bg-gray-100 inline-block font-medium"
          >
            Edit this game
          </Link>
          <Link
            href="/games"
            className="ml-2 rounded-lg py-3 px-5 bg-gray-100 inline-block font-medium"
          >
            Back to games
          </Link>
          <div className="inline-block ml-2">
            <Link
              href={`/games/${game.id}`}
              as="button"
              method="delete"
              className="mt-2 rounded-lg py-3 px-5 bg-gray-100 font-medium"
            >
              Destroy this game
            </Link>
          </div>
        </div>
      </div>
    </>
  )
}
