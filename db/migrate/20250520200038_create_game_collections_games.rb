class CreateGameCollectionsGames < ActiveRecord::Migration[8.0]
  def change
    create_table :game_collections_games do |t|
      t.references :game, null: false, foreign_key: true
      t.references :game_collection, null: false, foreign_key: true
      t.integer :position, null: false

      t.timestamps
    end

    add_index :game_collections_games, [ :game_id, :game_collection_id ], unique: true
  end
end
