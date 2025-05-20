class GameCollectionsGame < ApplicationRecord
  belongs_to :game
  belongs_to :game_collection

  validates :position, numericality: { only_integer: true, greater_than: 0 }
end
