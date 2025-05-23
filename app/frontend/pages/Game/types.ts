export interface GameType {
  id: number
  name: string
  status: GameStatus
  created_at: string
}

export type GameFormType = Omit<GameType, 'id'>

export type GameStatus = "to_play" | "in_progress" | "completed" | "abandoned"
