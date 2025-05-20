export interface GameCollectionType {
  id: number
  name: string
}

export type GameCollectionFormType = Omit<GameCollectionType, 'id'>
