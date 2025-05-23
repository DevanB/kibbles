import { GameStatus } from "@/pages/Game/types"
import { Badge } from "./ui/badge"

function formatStatusDisplay(status: GameStatus): string {
  switch (status) {
    case "to_play":
      return "To Play"
    case "in_progress":
      return "In Progress"
    case "completed":
      return "Completed"
    case "abandoned":
      return "Abandoned"
  }
}

const getStatusColor = (status: GameStatus) => {
  switch (status) {
    case "to_play":
      return "bg-blue-500"
    case "in_progress":
      return "bg-yellow-500"
    case "completed":
      return "bg-green-500"
    case "abandoned":
    default:
      return "bg-gray-500"
  }
}

export function GameStatusBadge({ status }: { status: GameStatus }) {
  return (
    <Badge className={`${getStatusColor(status)} text-white`}>{formatStatusDisplay(status)}</Badge>
  )
}
