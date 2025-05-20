import { GameCollectionType } from './types'

interface GameCollectionProps {
  game_collection: GameCollectionType
}

export default function GameCollection({ game_collection }: GameCollectionProps) {
  return (
    <div>
      <p className="my-5">
        <strong className="block font-medium mb-1">Name:</strong>
        {game_collection.name?.toString()}
      </p>
    </div>
  )
}
