# Quickreads admin

The admin remains at `/quickreads`, protected by the existing `quickreads-admin` session guard. The mobile and desktop interface uses local CSS, JavaScript and SVG icons; it has no CDN, jQuery, DataTables, chart-library or asset-build dependency.

## Pages and workflows

- **Dashboard:** catalogue and reader totals, 30-day activity, recent stories/readers, and links to fix missing author/category relationships or invalid reading times. Summary counts refresh every five minutes and are invalidated by admin changes.
- **Statistics:** 7/30/90/365-day presets or historical custom windows of up to 366 days, grouped by day or month. Missing buckets are filled with zero. Each report uses eight SQL queries on a cache miss, at most 366 chart points, and ten entries per ranking. Reports cache for five minutes and are invalidated by admin changes. Dates use the application timezone.
- **Users:** search full name/email, filter by saved-story activity, sort, and paginate. Detail pages use numeric IDs because legacy user slugs are not unique. Saved stories paginate independently; recent ratings/comments each stop at ten.
- **Stories:** search title/author, filter by author/category/classic/data issue, sort, and paginate. Editors search author/category lookups instead of downloading every record. Cover uploads validate MIME/size and show a preview; the legacy `.jpeg` URL is preserved even when uploaded bytes are PNG/WebP, matching the existing app contract.
- **Authors/categories:** searchable lists, story counts, links to filtered stories, and focused forms. Category order respects the existing signed tiny-integer column (-128 to 127).

Lists return 25, 50 or 100 rows with preserved filters; story lists omit story text and author lists omit biographies. Editors keep slugs stable when titles/names change so existing links and artwork paths remain valid. Creation checks for slug collisions.

The old `/stories|authors|categories/edit` and `/delete` selection URLs redirect to the corresponding list. Their unused templates and the obsolete statistics controller were removed. Legacy application API routes and response contracts remain covered by regression tests.

Deletion requires an explicit checkbox. Authors/categories with stories cannot be deleted; first reassign their stories. Story deletion cleans up saved records, ratings, comments and local cover files. User deletion cleans up saved records, ratings, comments, v2 access tokens and password resets. These are permanent operations, as before; there is no soft-delete schema.

“Saves” means rows in `user_purchase_records`, not verified downloads or completed reads. Legacy duplicates count as separate saves. Distinct reader/story metrics exclude deleted entities. Null-date saves do not belong to a reporting window. A partially elapsed reporting day/month is included through today; no data after the selected end date is included.

## Deployment

Deploy the PHP/views and both `public/css/quickreads-admin.css` and `public/js/quickreads-admin.js`. No npm build is required. Ensure previous Quickreads migrations, including `api_v2_tokens`, are already installed.

Run the new additive index migration against the **Quickreads connection**:

```sh
php artisan migrate --database=quickreads --path=database/migrations/quickreads/2026_10_12_000001_add_admin_query_indexes.php --force
php artisan view:clear
```

Rebuild the deployment's usual route/view caches if used. The existing public storage link and a writable public disk are required for covers.

The migration adds date, relationship, slug and per-user activity indexes. It does not delete or rewrite data. Index creation can take time and consume disk space on a large production database; schedule it with normal database-change procedures. `down()` removes only indexes introduced here. The new code functions before indexes are installed, but the performance improvement depends on them.

## Verification

```sh
php vendor/bin/phpunit tests/Feature/Quickreads
node --check public/js/quickreads-admin.js
php artisan view:cache
php artisan view:clear
```

The feature suite covers authentication, all admin pages, legacy redirects/API contracts, report bounds and caching, orphaned/empty data, pagination and filters, validation, cover storage, stable URLs and deletion cleanup. Tests replace every named database connection with isolated databases; never run a fresh migration against production to test this change.

The same suite was also run against an isolated MySQL 8.4 instance. A local synthetic benchmark with 250,000 saves spread across 730 days produced a cold 30-day report in approximately 41 ms with eight SQL queries and 10,290 in-range saves. This measures a local fixture, not production latency; a longer range necessarily reads more index entries. MySQL rankings aggregate the date window before joining story/user metadata to avoid scanning complete histories.

Desktop (1440px) and mobile (390px) browser checks use synthetic data and exercise login, report filters, asynchronous lookups, story updates, reader search/detail, navigation and responsive layouts. No production data or admin accounts were changed during development.
