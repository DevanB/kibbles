class GameJournalEntriesController < ApplicationController
  before_action :set_game_journal_entry, only: %i[ show edit update destroy ]

  inertia_share flash: -> { flash.to_hash }

  # GET /game_journal_entries
  def index
    @game_journal_entries = GameJournalEntry.all
    render inertia: "GameJournalEntry/Index", props: {
      game_journal_entries: @game_journal_entries.map do |game_journal_entry|
        serialize_game_journal_entry(game_journal_entry)
      end
    }
  end

  # GET /game_journal_entries/1
  def show
    render inertia: "GameJournalEntry/Show", props: {
      game_journal_entry: serialize_game_journal_entry(@game_journal_entry)
    }
  end

  # GET /game_journal_entries/new
  def new
    @game_journal_entry = GameJournalEntry.new
    render inertia: "GameJournalEntry/New", props: {
      game_journal_entry: serialize_game_journal_entry(@game_journal_entry)
    }
  end

  # GET /game_journal_entries/1/edit
  def edit
    render inertia: "GameJournalEntry/Edit", props: {
      game_journal_entry: serialize_game_journal_entry(@game_journal_entry)
    }
  end

  # POST /game_journal_entries
  def create
    @game_journal_entry = GameJournalEntry.new(game_journal_entry_params)

    if @game_journal_entry.save
      redirect_to @game_journal_entry, notice: "Game journal entry was successfully created."
    else
      redirect_to new_game_journal_entry_url, inertia: { errors: @game_journal_entry.errors }
    end
  end

  # PATCH/PUT /game_journal_entries/1
  def update
    if @game_journal_entry.update(game_journal_entry_params)
      redirect_to @game_journal_entry, notice: "Game journal entry was successfully updated."
    else
      redirect_to edit_game_journal_entry_url(@game_journal_entry), inertia: { errors: @game_journal_entry.errors }
    end
  end

  # DELETE /game_journal_entries/1
  def destroy
    @game_journal_entry.destroy!
    redirect_to game_journal_entries_url, notice: "Game journal entry was successfully destroyed."
  end

  private
    # Use callbacks to share common setup or constraints between actions.
    def set_game_journal_entry
      @game_journal_entry = GameJournalEntry.find(params[:id])
    end

    # Only allow a list of trusted parameters through.
    def game_journal_entry_params
      params.require(:game_journal_entry).permit(:game_id, :text)
    end

    def serialize_game_journal_entry(game_journal_entry)
      game_journal_entry.as_json(only: [
        :id, :game_id, :text
      ])
    end
end
