require "application_system_test_case"

class GameJournalEntriesTest < ApplicationSystemTestCase
  setup do
    @game_journal_entry = game_journal_entries(:one)
  end

  test "visiting the index" do
    visit game_journal_entries_url
    assert_selector "h1", text: "Game journal entries"
  end

  test "should create game journal entry" do
    visit game_journal_entries_url
    click_on "New game journal entry"

    fill_in "Game", with: @game_journal_entry.game_id
    fill_in "Text", with: @game_journal_entry.text
    click_on "Create Game journal entry"

    assert_text "Game journal entry was successfully created"
    click_on "Back"
  end

  test "should update Game journal entry" do
    visit game_journal_entry_url(@game_journal_entry)
    click_on "Edit this game journal entry", match: :first

    fill_in "Game", with: @game_journal_entry.game_id
    fill_in "Text", with: @game_journal_entry.text
    click_on "Update Game journal entry"

    assert_text "Game journal entry was successfully updated"
    click_on "Back"
  end

  test "should destroy Game journal entry" do
    visit game_journal_entry_url(@game_journal_entry)
    click_on "Destroy this game journal entry", match: :first

    assert_text "Game journal entry was successfully destroyed"
  end
end
