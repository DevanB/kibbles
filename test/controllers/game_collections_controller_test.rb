require "test_helper"

class GameCollectionsControllerTest < ActionDispatch::IntegrationTest
  setup do
    @game_collection = game_collections(:one)
  end

  test "should get index" do
    get game_collections_url
    assert_response :success
  end

  test "should get new" do
    get new_game_collection_url
    assert_response :success
  end

  test "should create game_collection" do
    assert_difference("GameCollection.count") do
      post game_collections_url, params: { game_collection: { name: @game_collection.name } }
    end

    assert_redirected_to game_collection_url(GameCollection.last)
  end

  test "should show game_collection" do
    get game_collection_url(@game_collection)
    assert_response :success
  end

  test "should get edit" do
    get edit_game_collection_url(@game_collection)
    assert_response :success
  end

  test "should update game_collection" do
    patch game_collection_url(@game_collection), params: { game_collection: { name: @game_collection.name } }
    assert_redirected_to game_collection_url(@game_collection)
  end

  test "should destroy game_collection" do
    assert_difference("GameCollection.count", -1) do
      delete game_collection_url(@game_collection)
    end

    assert_redirected_to game_collections_url
  end
end
