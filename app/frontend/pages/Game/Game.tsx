import { GameType } from './types'

interface GameProps {
  game: GameType
}

export default function Game({ game }: GameProps) {
  return (
    <div>
      <p className="my-5">
        <strong className="block font-medium mb-1">Title:</strong>
        {game.title?.toString()}
      </p>
      <p className="my-5">
        <strong className="block font-medium mb-1">Status:</strong>
        {game.status?.toString()}
      </p>
    </div>
  )
}
