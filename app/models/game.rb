class Game < ApplicationRecord
  has_many :game_collections_games, dependent: :destroy
  has_many :game_collections, through: :game_collections_games
  has_many :game_journal_entries, dependent: :destroy

  enum :status, {
    to_play: 0,
    in_progress: 1,
    completed: 2,
    abandoned: 3
  }

  validates :title, presence: true
  validates :status, presence: true
end
