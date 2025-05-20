class CreateGameJournalEntries < ActiveRecord::Migration[8.0]
  def change
    create_table :game_journal_entries do |t|
      t.references :game, null: false, foreign_key: true
      t.text :text, null: false

      t.timestamps
    end
  end
end
