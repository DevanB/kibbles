import { Head, Link } from '@inertiajs/react'
import { GameType } from './types'
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { ChevronDownIcon } from 'lucide-react'
import { format } from 'date-fns'
import { GameStatusBadge } from '@/components/GameStatusBadge'

interface ShowProps {
  game: GameType
}

export default function Show({ game }: ShowProps) {
  return (
    <>
      <Head title={game.name} />

      <div className="space-y-6">
        <div className="bg-card p-6 rounded-lg">
          <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
            <div className="flex flex-col space-y-2">
              <div className="flex space-x-4 items-center">
                <h2 className="text-2xl font-bold">{game.name}</h2>
                <GameStatusBadge status={game.status} />
              </div>
              <p className="text-sm text-muted-foreground">
                Added on {format(new Date(game.created_at), "MMMM d, yyyy")}
              </p>
            </div>
            <div className="flex items-center gap-2">
              <div className="flex mt-4">
                <div className="flex items-center">
                  <Button asChild variant="outline" className={'rounded-r-none'}>
                    <Link href={`/games/${game.id}/edit`}>
                      Edit
                    </Link>
                  </Button>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="outline" className='rounded-l-none border-l-0 px-2 cursor-pointer'>
                        <ChevronDownIcon />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent>
                      <DropdownMenuItem>
                        <Link
                          className="cursor-pointer text-red-600"
                          href={`/games/${game.id}`}
                          as="button"
                          method="delete"
                        >
                          Delete Game
                        </Link>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="bg-card p-6 rounded-lg">
          <div className="space-y-4">
            <div className="flex justify-between items-center">
              <h3 className="text-xl font-semibold">Journal Entries</h3>
              <Button size="sm" variant="secondary">Add Entry</Button>
            </div>
            <div className="bg-muted p-6 rounded-lg text-center">
              <p className="text-muted-foreground">No journal entries yet.</p>
            </div>
            {/* {isAddingEntry ? ( */}
            {/*   <AddJournalEntry onAddEntry={handleAddEntry} onCancel={() => setIsAddingEntry(false)} /> */}
            {/* ) : ( */}
            {/*   <JournalEntryList entries={game.journalEntries} onSelectEntry={onSelectEntry} isMobileView={isMobileView} /> */}
            {/* )} */}
          </div>
        </div>
      </div >
    </>
  )
}
