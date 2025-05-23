import { GameType } from './types'
import { Link } from '@inertiajs/react'

interface GameProps {
  game: GameType
}

export default function Game({ game }: GameProps) {
  return (
    <div className="p-3 rounded-lg cursor-pointer transition-colors bg-card hover:bg-accent/50 mt-5">
      <Link className="flex flex-col" href={`/games/${game.id}`}>
        <div className="flex justify-between items-center">
          <div className="flex flex-col">
            <h3 className="p-0 font-medium truncate">
              {game.name}
            </h3>
            <p className="text-sm text-muted-foreground">
              1 entry
              {/* {game.journalEntries.length} {game.journalEntries.length === 1 ? "entry" : "entries"} */}
            </p>
          </div>
          <GameStatusBadge status={game.status} />
        </div>
      </Link>
    </div>
  )
}
