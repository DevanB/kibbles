# Tags (X bookmarks)

Authenticated, verified tag manager at `/tags` (route `tags.index`, Inertia `tags/index`) and bookmark filters on `/bookmarks?tag=` and `/x?tag=`. Tags are user-owned, kibbles-only, and never sync to or from X. Names are unique per user case-insensitively: stored as typed, compared folded (`name_normalized` generated column), same approach as Games titles. The same tag rows attach to saved links and to X posts.

## Sub-features

- Sidebar **Bookmarks** nests **All**, **Untagged**, and one item per tag (alphabetical). Links: `/bookmarks`, `/bookmarks?tag=untagged`, `/bookmarks?tag={id}`.
- Sidebar **X** nests **All**, **Review**, **Untagged**, and one item per tag (alphabetical). Links: `/x`, `/x-reviews`, `/x?tag=untagged`, `/x?tag={id}`.
- Bookmark index cards show tag badges and a **Tags** combobox (`aria-label="Tags"`) in the action cluster: Tag, Open on X, Remove. The review card keeps **Tags** and Open on X; Remove is the separate **Remove** control under the card. The remove modal card has no Tags control. Quoted-post inner cards have no tag control.
- Creating a tag happens by typing in that combobox (`POST x-bookmarks.tags.store` with `name`). Toggling an existing tag attaches or detaches it. Attach/detach is scoped to the owning user.
- `/tags` lists tags. Rename is an inertia-modal (`tags.edit` → `PUT tags.update`). Delete is an inertia-modal (`tags.show` → `DELETE tags.destroy`) and removes only the tag; bookmarks stay. Pivot rows go away when a bookmark is removed (Remove or sync) via FK cascade.
- Incremental and weekly full `SyncXBookmarks` skip a local row that already exists (text and tags stay as stored). Full sync deletes local posts missing from the X id list and inserts unknown ids. It does not rewrite posts it still sees.

## How to get to it (user POV)

Log in as a verified user. Open **X** in the sidebar (All / Review / Untagged / a tag) or **Manage tags** from `/x`.

## Driving it with Pest Browser / Feature tests

- **Tagging + filter + manage (Browser) →** `php artisan test --compact tests/Browser/XBookmarkTagsTest.php`
- **HTTP / gates / uniqueness / sync →** `php artisan test --compact tests/Feature/Controllers/TagControllerTest.php tests/Feature/Controllers/XBookmarkTagControllerTest.php tests/Feature/Controllers/XBookmarkControllerTest.php tests/Feature/SyncXBookmarksTest.php`
- **Durable proof →** `.agents/skills/verify-kibbles/bin/prove-x-bookmarks`

## Gotchas

- Display casing is whatever the user typed. `Laravel` and `laravel` are the same tag for one user.
- Filtering is one tag at a time. An unknown or other-user tag id on `?tag=` is **404**.
- Index pagination is still cursor 20 under a filter. Follow-up pages keep `tag` and add `cursor`.
- Deleting a tag does not delete bookmarks. Deleting a bookmark deletes only that bookmark's pivot rows.
