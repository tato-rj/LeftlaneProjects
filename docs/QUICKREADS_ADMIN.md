# Quickreads admin

The admin remains at `/quickreads`, protected by the existing `quickreads-admin` session guard. The mobile and desktop interface uses local CSS, JavaScript and SVG icons; it has no CDN, jQuery, DataTables, chart-library or asset-build dependency.

Typography uses a 16px body size, 14px secondary text, 13px compact labels and headings of at least 16px, including on mobile. Sizes use rem units to respect browser font preferences. Explore covers are 60 × 88px (76 × 110px for the featured story), and library-picker thumbnails are 32 × 46px. The preview adapts its columns to keep story titles readable.

## Pages and workflows

- **Dashboard:** catalogue and reader totals, 30-day activity, recent stories/readers, and links to fix missing author/category relationships or invalid reading times. Summary counts refresh every five minutes and are invalidated by admin changes.
- **Statistics:** 7/30/90/365-day presets or historical custom windows of up to 366 days, grouped by day or month. Missing buckets are filled with zero. Each report uses eight SQL queries on a cache miss, at most 366 chart points, and ten entries per ranking. Reports cache for five minutes and are invalidated by admin changes. Dates use the application timezone.
- **Story activity:** available directly in the sidebar, dashboard and Statistics. Shows current Saved/Reading/Finished counts, the latest report received, and 25 reader/story reports per page. Latest reports include registered reader names with links to their protected profiles; guests stay anonymous. Reading and saved states have separate observation timestamps, plus the server's received time. Search by story, author or registered reader; filter Saved/Reading/Finished and guest/account. Metric cards preserve the reader/search filters and select a state. **By story** switches to paginated totals grouped separately for guests and accounts; selecting a story opens its latest reader reports. All times convert stored UTC to the application timezone. The report is private/no-store and uncached: **Refresh report** immediately reads committed activity.
- **Users:** search full name/email, filter by saved-story activity, sort, and paginate. Detail pages use numeric IDs because legacy user slugs are not unique. Saved stories paginate independently; recent ratings/comments each stop at ten.
- **Explore:** a compact app-style preview with small covers and an adjacent section editor. Add, rename, remove and drag sections into order. **Add a section** opens a form for its name and hand-picked stories; cancelling leaves the layout unchanged. Every editable shelf has a story picker with selected stories, remove buttons, title/author search and alphabetical browsing. **Load more stories** browses the entire available library, 20 records at a time, without fetching bodies or counting the full catalogue. Drag selected stories in the picker or preview to arrange them; arrow keys on drag handles also work. Editing the membership of an existing automatic shelf preserves its current stories and switches it to hand-picked, retaining the section ID. Optional automatic selection rules are in a collapsed settings panel. Today’s pick stays first with one story; automatic Latest additions remains the newest ten. Up to 20 sections and 24 stories per shelf keep responses bounded. Section movement buttons provide an alternative to dragging; section/preview-card removals offer Undo.
- **Stories:** search title/author, filter by author/category/classic/data issue, sort, and paginate. Editors search author/category lookups instead of downloading every record. Admin saves always set the cost to Free. Reading time is estimated at 200 words per minute when the editor loads and as story text changes, rounded to tenths of a minute. Cover uploads validate MIME/size and show a preview; the legacy `.jpeg` URL is preserved even when uploaded bytes are PNG/WebP, matching the existing app contract.
- **Authors/categories:** searchable lists, story counts, links to filtered stories, and focused forms. Category order respects the existing signed tiny-integer column (-128 to 127).

Latest additions is a system collection, not an editable category. It is excluded from category lists, dashboard category totals, story assignment lookups and the v2 category directory. Creating a category with this reserved name or assigning stories to its existing ID is rejected. Stories previously assigned there retain their data and visibility; choose a regular category when editing them. The existing category row/ID stays internally for older app versions and is protected from renaming/deletion through admin.

The legacy `/quickreads/app/categories` response retains that ID and reports the automatic collection's size. `/quickreads/app/categories/show?category_id=<existing ID>` returns the ten newest stories across categories, ordered by creation date and then descending story ID. A fresh catalogue with no legacy row receives a virtual category with ID `0`, without a database write. `/quickreads/app/stories/latest` exposes the same flat story list directly. These routes work independently of whether Explore includes a Latest additions shelf. Fewer than ten available stories returns the available set; an empty catalogue returns an empty list. No migration is needed for this system collection behavior.

Explore edits stay private until **Save Explore**. Previews resolve the current library without database writes. Saving stores the layout with a revision check so another open editor cannot silently overwrite it. Both `/quickreads/app/explore` and `/api/v2/explore` use this layout, preserving their respective response fields. Legacy Explore remains an array of `{title, collection}` objects with the featured item first; it defaults to six sections and supports a different count after curation. V2 includes stable section IDs and paged summaries. Story text and biographies are omitted from admin searches; all preview/API shelves are bounded and metadata is fetched in a batch. The old legacy Explore cache is ignored, so saved curation takes effect on the next request. V2 clients may retain responses for their existing 60-second HTTP cache lifetime. Continue reading remains local to the app, and its typography/spacing remain controlled by the client.

Lists return 25, 50 or 100 rows with preserved filters; story lists omit story text and author lists omit biographies. Editors keep slugs stable when titles/names change so existing links and artwork paths remain valid. Creation checks for slug collisions.

The old `/stories|authors|categories/edit` and `/delete` selection URLs redirect to the corresponding list. Their unused templates and the obsolete statistics controller were removed. Legacy application API routes and response contracts remain covered by regression tests.

Deletion requires an explicit checkbox. Authors/categories with stories cannot be deleted; first reassign their stories. Story deletion cleans up saved records, ratings, comments and local cover files. User deletion cleans up saved records, ratings, comments, v2 access tokens and password resets. These are permanent operations, as before; there is no soft-delete schema.

“Saves” means rows in `user_purchase_records`, not verified downloads or completed reads. Legacy duplicates count as separate saves. Distinct reader/story metrics exclude deleted entities. Null-date saves do not belong to a reporting window. A partially elapsed reporting day/month is included through today; no data after the selected end date is included.

Story activity uses the separate v2 `story_activity` reports. Each row is the latest known state for one reader and story, rather than an event log or lifetime action counter. Finished replaces Reading for that pair; Saved remains independent. Summary cards count reader/story pairs and follow the search/reader/story criteria; selecting a status filters the table without hiding the other summary totals. An unknown reading/saved state is shown as **Not reported**. Inactive reports expire after 90 days. Guest counts represent installations, not authenticated people. Guest reporting UUIDs/hashes, account emails, credentials and story bodies are never fetched or rendered by this report.

### Test activity from the iOS app

1. Deploy the updated PHP, Blade views and admin CSS; clear compiled views. This admin update adds no migration or API schema change. The existing `2026_10_15_000002_create_story_activity_tables.php` migration must already be applied to the Quickreads connection.
2. Open **Story activity** in the sidebar and select **Clear filters**. The page shows when the latest report reached the backend.
3. In an iOS build with the `POST /api/v2/story-activity` transport enabled, open a story for reading. Select **Refresh report** in the admin: its latest row should show Reading and its report time.
4. Sign in and save that story. Refresh: Saved should appear independently of the reading state. Mark it finished through the app's completion flow and refresh: Finished replaces Reading, while Saved remains set. Remove the save and refresh: Not saved appears, with the reading state retained according to the app's reported state.
5. Try guest reading and completion separately. Those rows appear as Guest reader and must not be reassigned to an account after login.

If the page is empty or the received time does not change, confirm that the installed iOS build actually sends and receives successful acknowledgements from the activity endpoint. Existing legacy save/favorite calls alone do not report Reading or Finished. Offline queues may arrive after reconnecting/foregrounding. No fake production reports or backfill are created by opening this admin page. The app remains responsible for recording only successful actions and preserving Finished on reopening.

## Deployment

Deploy the PHP/views, `public/css/quickreads-admin.css`, `public/js/quickreads-admin.js`, `public/css/quickreads-explore.css` and `public/js/quickreads-explore.js`. No npm build is required. Ensure previous Quickreads migrations, including `api_v2_tokens`, are already installed.

Run the additive migrations against the **Quickreads connection**:

```sh
php artisan migrate --database=quickreads --path=database/migrations/quickreads/2026_10_12_000001_add_admin_query_indexes.php --force
php artisan migrate --database=quickreads --path=database/migrations/quickreads/2026_10_12_000002_create_explore_layouts_table.php --force
php artisan view:clear
```

Rebuild the deployment's usual route/view caches if used. The existing public storage link and a writable public disk are required for covers.

The migration adds date, relationship, slug and per-user activity indexes. It does not delete or rewrite data. Index creation can take time and consume disk space on a large production database; schedule it with normal database-change procedures. `down()` removes only indexes introduced here. The new code functions before indexes are installed, but the performance improvement depends on them.

The Explore migration creates only `explore_layouts`; it does not rewrite stories or categories. Install it before serving the new editor/API code. An empty layout table uses the default sections until the first admin save. Dropping this table discards curation, so retain it when rolling back code.

## Verification

```sh
php vendor/bin/phpunit tests/Feature/Quickreads
node --check public/js/quickreads-admin.js
node --check public/js/quickreads-explore.js
php artisan view:cache
php artisan view:clear
```

The feature suite covers authentication, all admin pages, legacy redirects/API contracts, report bounds and caching, orphaned/empty data, pagination and filters, validation, cover storage, stable URLs and deletion cleanup. Explore checks cover private previews, save conflicts, manual ordering, all automatic sources, validation limits, both API envelopes, custom section pagination and guest/favorite isolation. Tests replace every named database connection with isolated databases; never run a fresh migration against production to test this change.

The same suite was also run against an isolated MySQL 8.4 instance. A local synthetic benchmark with 250,000 saves spread across 730 days produced a cold 30-day report in approximately 41 ms with eight SQL queries and 10,290 in-range saves. This measures a local fixture, not production latency; a longer range necessarily reads more index entries. MySQL rankings aggregate the date window before joining story/user metadata to avoid scanning complete histories.

Desktop (1440px) and mobile (390px) browser checks use synthetic data and exercise login, report filters, asynchronous lookups, story updates, reader search/detail, navigation and responsive layouts. Explore checks also cover creating/cancelling named sections, paged story browsing, search, membership edits, drag ordering and save/reload persistence. No production data or admin accounts were changed during development.
