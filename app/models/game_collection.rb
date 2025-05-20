class GameCollection < ApplicationRecord
  has_many :game_collections_games, dependent: :destroy
  has_many :games, through: :game_collections_games

  validates :name, presence: true

  # Query all game journal entires for games in this collection
  def game_journal_entries
    GameJournalEntry.where(game_id: games.select(:id))
  end
end
