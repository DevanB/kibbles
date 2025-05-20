class GameCollectionsController < ApplicationController
  before_action :set_game_collection, only: %i[ show edit update destroy ]

  inertia_share flash: -> { flash.to_hash }

  # GET /game_collections
  def index
    @game_collections = GameCollection.all
    render inertia: "GameCollection/Index", props: {
      game_collections: @game_collections.map do |game_collection|
        serialize_game_collection(game_collection)
      end
    }
  end

  # GET /game_collections/1
  def show
    render inertia: "GameCollection/Show", props: {
      game_collection: serialize_game_collection(@game_collection)
    }
  end

  # GET /game_collections/new
  def new
    @game_collection = GameCollection.new
    render inertia: "GameCollection/New", props: {
      game_collection: serialize_game_collection(@game_collection)
    }
  end

  # GET /game_collections/1/edit
  def edit
    render inertia: "GameCollection/Edit", props: {
      game_collection: serialize_game_collection(@game_collection)
    }
  end

  # POST /game_collections
  def create
    @game_collection = GameCollection.new(game_collection_params)

    if @game_collection.save
      redirect_to @game_collection, notice: "Game collection was successfully created."
    else
      redirect_to new_game_collection_url, inertia: { errors: @game_collection.errors }
    end
  end

  # PATCH/PUT /game_collections/1
  def update
    if @game_collection.update(game_collection_params)
      redirect_to @game_collection, notice: "Game collection was successfully updated."
    else
      redirect_to edit_game_collection_url(@game_collection), inertia: { errors: @game_collection.errors }
    end
  end

  # DELETE /game_collections/1
  def destroy
    @game_collection.destroy!
    redirect_to game_collections_url, notice: "Game collection was successfully destroyed."
  end

  private
    # Use callbacks to share common setup or constraints between actions.
    def set_game_collection
      @game_collection = GameCollection.find(params[:id])
    end

    # Only allow a list of trusted parameters through.
    def game_collection_params
      params.require(:game_collection).permit(:name)
    end

    def serialize_game_collection(game_collection)
      game_collection.as_json(only: [
        :id, :name
      ])
    end
end
