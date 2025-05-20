require "application_system_test_case"

class GameCollectionsTest < ApplicationSystemTestCase
  setup do
    @game_collection = game_collections(:one)
  end

  test "visiting the index" do
    visit game_collections_url
    assert_selector "h1", text: "Game collections"
  end

  test "should create game collection" do
    visit game_collections_url
    click_on "New game collection"

    fill_in "Name", with: @game_collection.name
    click_on "Create Game collection"

    assert_text "Game collection was successfully created"
    click_on "Back"
  end

  test "should update Game collection" do
    visit game_collection_url(@game_collection)
    click_on "Edit this game collection", match: :first

    fill_in "Name", with: @game_collection.name
    click_on "Update Game collection"

    assert_text "Game collection was successfully updated"
    click_on "Back"
  end

  test "should destroy Game collection" do
    visit game_collection_url(@game_collection)
    click_on "Destroy this game collection", match: :first

    assert_text "Game collection was successfully destroyed"
  end
end
