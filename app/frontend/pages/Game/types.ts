export interface GameType {
  id: number
  title: string
  status: number
}

export type GameFormType = Omit<GameType, 'id'>
