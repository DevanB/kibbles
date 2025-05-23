import { Head, Link } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import { GameStatusBadge } from '@/components/GameStatusBadge'
import { GameType } from './types'

interface IndexProps {
  games: GameType[]
}

export default function Index({ games }: IndexProps) {
  return (
    <>
      <Head title="Games" />
      <div className="mx-auto md:w-2/3 w-full px-8 pt-8">
        <div className="flex justify-between items-center">
          <h1 className="font-bold text-4xl">My Games</h1>
          <Button variant="default" asChild size="sm">
            <Link href="/games/new">
              Add Game
            </Link>
          </Button>
        </div>

        <div className="min-w-full">
          {games?.length ? games.map((game) => (
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
          )) : (
            <div className="bg-muted p-6 rounded-lg text-center mt-4">
              <p className="text-muted-foreground">No games added yet.</p>
            </div>
          )}
        </div>
      </div>
    </>
  )
}
