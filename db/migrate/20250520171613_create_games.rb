class CreateGames < ActiveRecord::Migration[8.0]
  def change
    create_table :games do |t|
      t.string :name, null: false
      t.integer :status, null: false, default: 0

      t.timestamps
    end
  end
end
