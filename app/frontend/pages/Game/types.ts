export interface GameType {
  id: number
  name: string
  status: number
}

export type GameFormType = Omit<GameType, 'id'>
