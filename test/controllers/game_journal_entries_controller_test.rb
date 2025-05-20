require "test_helper"

class GameJournalEntriesControllerTest < ActionDispatch::IntegrationTest
  setup do
    @game_journal_entry = game_journal_entries(:one)
  end

  test "should get index" do
    get game_journal_entries_url
    assert_response :success
  end

  test "should get new" do
    get new_game_journal_entry_url
    assert_response :success
  end

  test "should create game_journal_entry" do
    assert_difference("GameJournalEntry.count") do
      post game_journal_entries_url, params: { game_journal_entry: { game_id: @game_journal_entry.game_id, text: @game_journal_entry.text } }
    end

    assert_redirected_to game_journal_entry_url(GameJournalEntry.last)
  end

  test "should show game_journal_entry" do
    get game_journal_entry_url(@game_journal_entry)
    assert_response :success
  end

  test "should get edit" do
    get edit_game_journal_entry_url(@game_journal_entry)
    assert_response :success
  end

  test "should update game_journal_entry" do
    patch game_journal_entry_url(@game_journal_entry), params: { game_journal_entry: { game_id: @game_journal_entry.game_id, text: @game_journal_entry.text } }
    assert_redirected_to game_journal_entry_url(@game_journal_entry)
  end

  test "should destroy game_journal_entry" do
    assert_difference("GameJournalEntry.count", -1) do
      delete game_journal_entry_url(@game_journal_entry)
    end

    assert_redirected_to game_journal_entries_url
  end
end
