# QuickReads API v2

Implemented on October 9, 2026 against `API_REQUIREMENTS_V2.md`. The authoritative contract is [openapi.yaml](openapi.yaml); the Swift handoff is [SWIFT_V2_MIGRATION.md](SWIFT_V2_MIGRATION.md). The OpenAPI document uses JSON syntax, which is valid YAML 1.2, allowing dependency-free PHP contract checks.

Both documents are publicly served from the tracked files at `/docs/openapi.yaml` and `/docs/SWIFT_V2_MIGRATION.md` (GET/HEAD).

## Structure and design

The new base path is `/api/v2`. The existing `/quickreads/...` routes remain at their original URLs, with their methods, handlers, middleware and response behavior unchanged. No `/api/v1` routes, redirects or legacy deprecations were introduced. `routes/api.php` only adds the v2 route file. `routes/web.php` only adds a separate v2 password-reset landing page.

V2 controllers live in `app/Http/Controllers/Api/V2`; the explicit query/serialization service and isolated user/token models live in `app/Projects/Quickreads/Api/V2`. It uses the same `quickreads` connection and catalog/account/favorite records. It does not call legacy `formatted()` projections or edit the legacy model relationships, controllers, views or serializers.

Responses are `{data: ...}`. Paginated responses also have `meta` and `links`. Resource IDs are decimal strings; durations/counts are numbers; flags are booleans; nullable values are emitted as `null`. V2 errors always have `{error: {code, message, fields}}`, including unknown routes and wrong HTTP methods. `fields` is an object of string-message arrays on validation errors and an empty object otherwise. Exception SQL, credentials, stack traces and file paths never appear in v2 responses. The exception handler applies this format only to `/api/v2` and descendants.

| Method | Path relative to `/api/v2` | Purpose |
| --- | --- | --- |
| POST | `/auth/login` | Email/password body; return account and bearer token |
| POST | `/auth/register` | Register with name/email/password confirmation; return session |
| POST | `/auth/password/email` | Generic reset-email acknowledgement |
| POST | `/auth/password/reset` | Complete reset; revoke all v2 tokens for this account |
| POST | `/auth/logout` | Revoke the presented bearer token |
| GET | `/me` | Verified own account and existing legacy alias |
| GET | `/explore` | Admin-curated sections, each with a bounded story page |
| GET | `/stories` | Browse/filter/search summaries or expand a section |
| GET | `/stories/resolve` | Resolve exact current title and optional author safely |
| GET | `/stories/{storyId}` | Detail, author biography/date label, first related page |
| GET | `/stories/{storyId}/content` | Direct original text, identity, format and content hash |
| GET | `/authors` | Alphabetical author directory with distinct story counts |
| GET | `/categories` | Category directory with stored editorial order and counts |
| GET | `/me/favorites` | Authenticated own favorite summaries |
| PUT | `/me/favorites/{storyId}` | Idempotent add; requires existing story |
| DELETE | `/me/favorites/{storyId}` | Idempotent removal, even after editorial deletion |

GET routes also accept HEAD, with the same status/headers and no body. No public user directory, credential-in-path login, numerical-rating mutation, audio, reaction or remote-progress endpoint is introduced into v2.

## Authentication and accounts

V2 reuses Laravel Hash and Sanctum personal-access-token generation, hashing and abilities. Existing QuickReads passwords are checked as-is. An isolated v2 user subclass keeps the original user model's behavior intact. Its token relation uses `api_v2_tokens` on the `quickreads` connection. The global Sanctum model, VideoUploader token table, guards, legacy default provider and existing CSRF exceptions remain unchanged.

Send JSON credential bodies over HTTPS. Private requests require `Authorization: Bearer <access_token>`. The middleware accepts only the QuickReads v2 token model, `quickreads:v2` ability, an extant account and a future explicit expiry. It does not accept Basic authentication, cookies, caller-supplied account IDs or VideoUploader tokens. Tokens are stored as SHA-256 hashes; the plaintext token is returned only on creation. The default lifetime is 30 days, and `expires_at` is RFC 3339 UTC. Reauthenticate after expiration or revocation; there is no refresh-token flow. Multiple device sessions can coexist. Logout revokes the current token; password reset revokes every v2 token for that account. No user-session mutation occurs on legacy login.

`GET /me` returns `id`, `legacy_account_id`, `email`, `first_name`, `last_name`, `locale`. The legacy alias is the exact stored `facebook_id`, despite that historical name; it is not evidence of Facebook sign-in and is not constrained unique. Use authenticated canonical `id` for account scope. Neither supplied aliases nor new names can reassign an old account. Login and password-reset writes lock the account row to serialize verification/token issuance with password changes.

Registration requires email (max 255 characters), first/last names (1–255), password confirmation and a password of 8–72 characters **and at most 72 UTF-8 bytes**, reflecting bcrypt limits. Locale is optional/null, max 35 characters. Caller-supplied IDs/password hashes are ignored. Existing email uniqueness is preserved; new accounts retain a legacy-compatible generated alias. Social-only accounts without valid password hashes cannot use password login; a reset with a verified email can establish a password. No social token verification is invented.

Password reset uses a new named `quickreads-v2` provider/broker, the existing QuickReads `password_resets` table, 60-minute reset-token expiry and a 60-second broker email throttle. Email requests return the same `202` acknowledgement for nonexistent accounts and broker throttling. Actual mail failures return sanitized `500`; there must be working outbound mail in deployment. The reset notification points to a separate `/quickreads/v2/password/reset` form; `QUICKREADS_V2_RESET_URL` can specify an agreed HTTPS client/web destination instead. Legacy reset URLs are untouched. New password/reset success does not automatically authenticate the app.

The API middleware group retains its 60-request/minute limit. Each credential/reset operation additionally has a separate 5-attempt/minute/IP bucket. `429` includes `Retry-After` seconds. Mutations accept JSON objects; omit bodies for logout/favorite changes or send `{}`. Nonempty non-JSON bodies produce `415`; malformed JSON or arrays/scalars produce `400`. Credentials in query parameters do not satisfy login validation. Unknown fields are ignored.

## Catalog, lists and discovery

The database stores one `author_id` and one `category_id` per story. V2 does not invent multiple category memberships. `categories` is a zero/one-item array to represent an orphaned category safely; `author` is null if its row is missing. There are no foreign keys or publication/access flags in the checked-in QuickReads schema. Thus the available metadata catalog is the current set of extant story rows, even when body text is empty. There is no entitlement/paywall inferred from `cost`; legacy download access is already public. Author/category counts count distinct extant story IDs in their relationships, not favorite records or deduplicated titles. Missing bodies do not erase metadata/counts.

Story summaries contain ID/title/summary, numeric nullable `reading_minutes`, `is_classic`, small author/category references, nullable artwork and `metadata_revision`. They omit body, long biography, price, views, stars/comments, timestamps and internal columns. Detail adds author biography/date label, unknown `language: null`, and up to eight related summaries of the same canonical author. Related results exclude the current ID and use editorial ordering. Follow the embedded related continuation to expand; a missing author has an empty related collection. Author birth/death strings form `date_label`; this is not a content date/revision.

All directories and story/favorite lists use `page` (default 1) and `per_page` (default 20, max 100). `meta` reports page/size/total, `has_more`, nullable `next_page`, and normalized criteria. `links.next` is a relative URL retaining validated filters/size; resolve against the server origin. The last page has no continuation. An empty valid result is `200` with `data: []`. A nonexistent author/category/excluded-story filter returns `404`; malformed inputs return `422`. Page numbers beyond the end yield an empty successful page.

This is offset pagination with deterministic ID tie-breaks, not a frozen snapshot. Editorial changes/new rows/favorite writes between page requests can shift membership. Clients should deduplicate by ID and restart on refresh/filter changes; no signed snapshot token or stable traversal during concurrent edits is claimed.

`/stories` supports `q`, `author_id`, `category_id`, `exclude_id`, `min_minutes`, `max_minutes`, `sort` and `section`. Filters intersect. Search is a literal title/author substring; `%`, `_` and `!` are escaped, and whitespace-only queries browse the catalog. Queries are lowered using Unicode-aware PHP `mb_strtolower`; database `LOWER` and production MySQL `utf8mb4_unicode_ci` determine matching/order, including case/accent insensitivity. There is no extra normalization, transliteration, full-text/theme search or relevance scoring. SQLite fixture tests do not establish identical non-ASCII collation behavior.

Reading minutes are the existing editor-entered `time` value parsed as a nonnegative decimal. V2 does not recompute or round estimates. Nonnumeric values serialize as null and are excluded from duration filters. Both bounds are inclusive; omit the upper bound for an unbounded range. Thus `(5,10)` and `(10,15)` intentionally overlap at 10. Choose nonoverlapping client bands only as a deliberate UI change. Legacy `whereBetween` filtering remains unchanged.

Ordering:

- `editorial` (default): category `sorting_order` ascending, null last, then story ID ascending.
- `title`: database title order, then ID ascending.
- `latest`: creation time descending, then ID ascending.
- `popular`: count of distinct non-null favoriting account IDs descending, then ID ascending; duplicate legacy saves do not inflate v2 ranking.
- Authors: database name order, then ID. Categories: stored `sorting_order`, null last, then ID. Favorites: story ID ascending.

Explore uses the layout saved in the Quickreads admin Explore tab. The default IDs are `featured`, `latest`, `popular`, `under_10_minutes`, `classics`, `suggestions`; custom sections have stable string IDs. Clients must render the returned array instead of enumerating these defaults. `featured` stays first with role `featured` and one daily UTC pick or a hand-picked story. Other sections have role `collection` and up to eight summaries initially, with count/continuation. The admin can rename/reorder/remove shelves, choose hand-picked order, or use automatic daily/latest/popular/quick/classics/author/category selections. A shelf is bounded at 24 stories; latest additions always resolves the newest ten, ordered by creation date then descending ID. Popularity counts distinct saved account IDs. Automatic sections refresh against the current library; manual sections omit deleted/orphaned stories. Continue Reading remains local.

`/stories?section=<id>` expands the same bounded selection and preserves its ordering. Additional filters intersect the selection; `sort` cannot override section ordering. Original default IDs remain valid fallback filters when their shelves are removed. Removed custom IDs return validation errors. IDs may appear in different sections but never duplicate within a section/page. See [QUICKREADS_ADMIN.md](QUICKREADS_ADMIN.md) for editing and deployment, including the required `explore_layouts` migration.

Categories expose stored slug as `key`, name, count, `description: null`, and `kind: category`. The system-managed Latest additions record is excluded from this directory; v2 clients use the existing `latest` Explore section or `/stories?section=latest` instead. Other source categories have no genre/editorial classification or descriptions, so they retain generic category metadata. Independently cacheable author/category directories satisfy Search discovery without a full-catalog download; no redundant discovery-bundle endpoint was needed.

## Favorites and offline data

The existing `user_purchase_records` table represents current saved/favorite membership: legacy POST inserts rows; legacy DELETE removes all matching account/story rows. It is not a receipt, coin transaction, entitlement check or durable append-only event log. Website statistics use these current records as download/activity counts, so v2 writes reuse them rather than creating competing state.

V2 GET uses `EXISTS` to return each story once. PUT adds a row only if absent; DELETE removes all duplicates for the authenticated account/story and succeeds for already-absent or removed stories. Mutations acknowledge `{account_id, story_id, favorited}` so Swift can update a single item without refetching Explore. Private ownership comes only from the validated token, even if another `user_id` or alias is submitted.

The account row is locked in a QuickReads transaction to serialize concurrent **v2** favorite writes. No unique constraint or cleanup is added to the shared table because repeated legacy POSTs must continue to work. Legacy clients may still insert duplicate rows concurrently; v2 reads collapse them. These are idempotent desired-state operations, not mutation-UUID deduplication or offline conflict ordering. A delayed PUT after a later DELETE can re-add the favorite; clients must serialize/reconcile their own pending operations. No reaction-sync acknowledgement/version semantics are implied.

Remote favorite state is separate from durable local Library availability, local bookmarks, completion and saved body revisions. Reading/content access never requires a favorite acknowledgement. Do not adopt historical unscoped offline Library data into an account automatically. Removed stories disappear from remote favorite lists; clients may retain valid offline copies. The API does not remotely purge a device.

## Content, revisions, media and cache rules

Content is a single identified object fetched by canonical ID; there is no catalog-title lookup and no last-row selection. Text is returned exactly as stored, including Unicode and paragraph/CRLF boundaries. `revision` is lowercase hexadecimal SHA-256 of those exact UTF-8 bytes before JSON escaping, with no normalization, entity decoding or HTML stripping. This matches a client hash only when its original string bytes are identical. Save the original body before deriving cleaned text/speech and keep offsets scoped to its revision.

There is no stored editorial content-format field. `format` conservatively reports `html` when PHP `strip_tags` would alter the stored body, otherwise `plain_text`. The API does not sanitize/render HTML or claim all legacy content is plain text. Validate actual content in staging, render safely and keep original and speech-cleaned representations separate. `language` is explicitly null because no language metadata exists; the client must retain its local voice fallback. A missing story is `404`; an extant story with empty/unavailable body is `503 unavailable`. There is no historical tombstone/replacement mapping, so a `404` does not specify a replacement or require local deletion.

`metadata_revision` hashes the emitted compact summary projection (including artwork/category/author-name references), not the body or long biography. Use the response ETag for detail/related revalidation. Summary changes need not invalidate saved content; content changes always alter its content hash.

Public catalog/detail/content/author/category/Explore responses are account-independent and return ETag plus `Cache-Control: public, max-age=60, must-revalidate`. Send `If-None-Match` for the same URL; matches return `304` with no body. The ETag hashes the actual serialized response, so order, relationships and criteria are included. Conditional reads still compute the representation; there is no new server cache infrastructure. Authentication/profile/favorites and all errors use `private, no-store`. Public resources ignore bearer tokens and account aliases; private favorites must be fetched separately. Client caches must remain account-scoped for private data.

Artwork uses existing local public storage, validates the slug/path and image header, and returns only present JPEG/PNG/WebP assets with HTTPS cover URL, actual dimensions/MIME type and byte SHA-256 revision. URLs include `?v=<revision>` and have no expiry. Missing/malformed images are null; no internal path or fake placeholder URL is exposed. The repository has no thumbnail pipeline, so `thumbnail_url` is null. The deployment must expose `public/storage` (normally `php artisan storage:link`), HTTPS and suitable static-image cache headers. Query revisioning lets clients choose a new cache key, but this task does not configure the static server, guarantee old image bytes are retained forever, verify TLS/redirects or implement durable offline artwork downloads. API JSON validators do not control static image headers. The legacy uploader names all bytes .jpeg without converting formats; v2 reports the actual decoded MIME type even when the extension differs. Confirm the static server supplies appropriate headers for those historical files. JSON gzip/Brotli also remains server configuration.

## Database and deployment

One additive migration creates **only** `quickreads.api_v2_tokens` using Sanctum's token columns, token hash uniqueness, tokenable index and expiry. No existing QuickReads tables, records, relationship constraints, IDs or numerical ratings are changed. The existing password-reset table and favorite table are reused. The migration has run only against isolated test databases, not production.

Deploy code and run the specific migration with the configured QuickReads connection:

```sh
php artisan migrate --database=quickreads --path=database/migrations/quickreads/2026_10_09_000001_create_api_v2_tokens_table.php
```

Existing catalog/account/password-reset tables must already exist. Do not run old create-table migrations over a populated production database. Verify public-storage linkage and outbound mail; rebuild deployment config/route caches according to the existing release procedure. Roll back code by removing only v2 additions; do not rename existing endpoints. Dropping the v2 token table logs out v2 sessions but does not delete legacy accounts/catalog/favorites. No deployment or live account/mail operations were performed here.

## Inspection and legacy contract findings

Inspected all application route files, QuickReads controllers/models/migrations/statistics helpers, HTTP middleware/exception/auth config, shared providers, password controllers, website views and JavaScript usage, VideoUploader authentication/tests, and the complete Swift requirements. The checked-in route table is the evidence for this implementation, not proof of the deployed server or older standalone PHP scripts.

| Existing app URL | Parameters / authentication | Existing response and behavior preserved |
| --- | --- | --- |
| GET `/quickreads/app/explore` | Optional `facebook_id`; no app authentication | Ordered `{title, collection}` sections from the admin layout, defaulting to six. The featured collection stays first with at most one story. Latest additions is always the newest ten. All shelves resolve fresh bounded selections; the old `quickreads-explore-6` cache is ignored. Joined story metadata/model count fields remain, and matching alias adds Boolean favorite state. |
| GET `/quickreads/app/stories` | Optional `facebook_id`; no app authentication | Complete flat array, no pagination. String `id`, author/dates/life/title/summary/time/cost/artwork/category plus sorting_order. Guest rows lack `favorited`; matching alias adds Bool using legacy `contains(id)` behavior. |
| GET `/quickreads/app/stories/text` | `id`; public | Array of `{text}` rows, empty array for nonexistent ID. Primary-key lookup allows at most one matching row in this schema. |
| GET `/quickreads/app/stories/time` | `min`, `max` cast to integers; public | `whereBetween` on legacy string column; complete formatted story array. No personalized favorite addition. Actual MySQL string-column comparisons are retained; v2 explicitly casts numeric minutes. |
| GET `/quickreads/app/categories/show` | `category_id`; public | Complete formatted story array, including eager/count model extras; no personalized favorite addition. The retained Latest additions ID (or virtual ID `0`) returns the ten newest stories across categories rather than manually assigned stories. |
| GET `/quickreads/app/categories` | Public | Raw category models plus stories_count, IDs/slug/order/timestamps. Latest additions retains its existing ID with an automatic count; if absent, a virtual row with ID `0` is included without writing to the database. |
| GET `/quickreads/app/stories/latest` | Public; additive endpoint | Flat formatted array of at most ten newest stories, ordered by creation date descending, then story ID descending. Same fields as legacy category results. |
| GET `/quickreads/app/authors` | Public | Authors with biographies/date columns, story counts, nested stories and their eager relationships/counts. |
| GET `/quickreads/app/users` | Public in this checkout | Raw users with comments/ratings/stories; passwords/remember tokens hidden, other private profile data exposed. The stories relationship has an unqualified created_at order that fails in SQLite; deployed MySQL behavior requires verification. V2 has no equivalent public route. |
| GET `/quickreads/app/login/{email}/{password}` | Credentials in URL; no Basic/session enforcement in handler | User object on valid Hash check, HTTP 200 `[]` on failure. Does not establish a session/bearer lifecycle. |
| POST `/quickreads/users/register` | email/password/names/locale; CSRF-exempt | User JSON, HTTP 200; email validation/duplicate returns JSON string with HTTP 403. Existing weaker constraints retained. |
| POST `/quickreads/users/facebook` | email/names/locale/gender/facebook_id; CSRF-exempt | firstOrCreate user by email; no social-proof validation in this checkout. |
| GET `/quickreads/app/records/purchase` | `user_id`; public | Complete raw records plus appended story_title; relationship accessor can cause N+1 loads and errors for deleted stories. |
| GET `/quickreads/app/records/purchase/check` | `facebook_id`, `title`; alias/title validation only | JSON string `"yes"` or `"no"`; no authenticated ownership. |
| POST `/quickreads/app/records/purchase` | form `facebook_id`, `title`; CSRF-exempt | Inserts duplicate-capable raw record, appended story_title, framework HTTP 201 for new model. No payment/receipt fields. |
| DELETE `/quickreads/app/records/purchase` | query `facebook_id`, `title`; CSRF-exempt | Deletes all matching records; HTTP 200 body `200`. |
| POST `/quickreads/app/stories/views` | `title`; CSRF-exempt | Increment views, empty HTTP 200 body. Missing title dereferences are unchanged. |
| GET `/quickreads/app/stories/{storyTitle}/rating` | exact title; public | Rounded legacy star average/raw number, `0` when no ratings. Missing-title dereference unchanged. |
| GET `/quickreads/app/test` | Public | Debug model for story 65 with counts; unchanged. |
| GET `/quickreads/cache` | `key`; public | Debug dump of cache presence; unchanged. |
| `/quickreads/password/*` | Existing web reset forms/POSTs; email/reset POSTs CSRF-exempt | Existing Laravel traits/default broker/redirect behavior. The checkout has no configured legacy `users` password broker, so successful legacy reset is not established by source/tests. V2's named broker does not repair or replace it. |

Legacy JSON validation responses depend on Accept headers (Laravel standard `422 {message, errors}` versus web redirects), except registration's custom `403` string. Legacy controllers also contain unchecked missing-resource accesses; no error cleanup was applied. Existing complete arrays, default casts, raw-model extras, status codes and authentication gaps remain as found. Website admin pages share the same models, slug bindings, images and favorite statistics, with admin session guards; v2 uses ID lookups separately so those slug bindings are unaffected.

The historical `/quickreads/stories/ratings` POST described in the client document is **not registered** here (the URL matches an existing PATCH/DELETE slug path and POST currently returns 405). Its legacy controller still exists but was not remapped. `/shortstories_db/add_user.php`, `edit_user.php`, `get_users_db.php` are absent from repository public files/routes. They may be external/older deployment artifacts. This work neither adds nor removes them; deployment owners must verify their original hosting and consumers separately. No claim is made that this checkout can verify absent deployed code.

Existing inefficiencies include duplicated raw SQL projections, full catalog transfers for writer/related/text lookup, eager biographies/ratings/comments on summaries, repeated random/cached Explore data, favorite-state inconsistencies and per-record favorite story access. V2 uses joined compact projections, direct ID content/detail, bounded author/category/story directories, SQL EXISTS and distinct account counts. Query-count tests verify catalog requests use two database queries independent of page size. No model-wide eager loading or per-story database lookup is introduced; image inspection is request-local and reused across Explore sections. Large-scale search/popularity index tuning requires real dataset measurements; no speculative infrastructure/index migration was added.

## Requirements not safely supported

- Historical renamed-title/author aliases, title-author hashes (`local-v1`) and edition mapping: no alias history or authoritative hash normalization mapping exists. Exact current title/optional author resolution returns one ID, 404, or 409; it never picks the first ambiguous result. The verified account exposes its existing alias, but no alias-only account authentication is allowed.
- Per-story language: no source field; explicit null and client fallback. Stored text format is detected conservatively rather than guessed as universally plain text.
- Category descriptions and canonical genre/editorial classification: absent data; null descriptions and generic kind. Canonical key is the stored slug, which website editors can change; ID is the immutable client identity.
- Thumbnail variants: no existing pipeline. HTTPS covers/dimensions/revision are supported; thumbnail is null. Static media caching/TLS/redirect guarantees require deployment checks.
- Reactions: conditional product scope, not commissioned remote sync. No reaction tables/endpoints, UUID/conflict/tombstone protocol, public totals or conversion of historical stars were created. Verified legacy hash/alias mapping would be needed first.
- Remote progress/Library/audio sync, social token verification, coins/receipts/ads/push/profile editing and canonical story share links: outside observed required scope or lack agreed provider/destination. Local offline text/bookmarks/Apple speech remain independent.
- Content removal/replacement history: absent. Explicit 404/503 does not instruct local deletion or map editions.
- Absent historical PHP/rating-write deployment and successful existing reset/user-directory MySQL behavior: cannot be proved from this checkout. Preserve external artifacts during rollout and test staging/representative old clients.

## Verification

Run `php vendor/bin/phpunit`. Tests isolate **all** project connections in SQLite memory and execute project migrations without touching configured live databases. CONCAT/REGEXP are supplied solely in the test fixture to exercise preserved MySQL projections. The old harness had a missing test directory, did not configure the named VideoUploader connection, and never loaded project migrations. Its VideoUploader request fixture also expected removed piece_id validation; the fixture now tests the existing file/name/composer contract without modifying VideoUploader application code. Each test discards its in-memory database rather than executing historical rollback methods (an existing VideoUploader down() has a misspelling).

Coverage includes v2 success/error schemas, body validation, authenticated ownership, wrong-scope/expired/revoked credentials, reset lifecycle, duplicate favorites, page continuation/counts, literal search/time boundaries, missing content, Unicode hashes, conditional responses, artwork paths, request-independent query counts, the original route-table snapshot, representative legacy responses/authentication and public/admin website rendering. New migration up/down is tested separately. The OpenAPI operations and representative response bodies are checked against registered routes and every referenced schema. Schema/route conformance checks use the actual OpenAPI document. Legacy model/controller/route source files remain unmodified.

Local tests are not a live production/old-binary test, a MySQL Unicode-collation test, a mail-delivery test, a static-image/TLS test or a concurrent multi-connection database test. Run those in staging before switching the Swift app. No Swift project was edited.

An additional MySQL verification pass was attempted with a fresh, non-networked temporary server, separate from all production settings. The installed MySQL 8.4 binary crashed during initialization with SIGSEGV before a database could be created, so no MySQL pass is claimed. The test harness supports a future isolated run using `QUICKREADS_TEST_MYSQL_SOCKET=/private/tmp/quickreads-v2-mysql-<run>/mysql.sock`; it creates randomized `qr_v2_test_*` schemas only and drops them afterward. The socket path is restricted to that temporary prefix. SQLite remains the default, and production credentials are never used by the tests.

The document also passed the official [OpenAPI 3.0 JSON Schema](https://spec.openapis.org/oas/3.0/schema/2024-10-18) using the existing local AJV validator and parsed as YAML with Ruby's YAML parser. This validates contract syntax in addition to the route/response conformance tests.

## Changed-file inventory

Created:

- `routes/api/quickreads_v2.php`
- `app/Http/Controllers/Api/V2/AuthController.php`, `CatalogController.php`, `FavoritesController.php`
- `app/Http/Middleware/Api/V2/Authenticate.php`, `JsonRequests.php`
- `app/Projects/Quickreads/Api/V2/Catalog.php`, `Responses.php`, `User.php`, `PersonalAccessToken.php`
- `app/Notifications/Quickreads/ResetPassword.php`
- `config/quickreads_v2.php`
- `database/migrations/quickreads/2026_10_09_000001_create_api_v2_tokens_table.php`
- `resources/views/projects/quickreads/v2/password-reset.blade.php`
- `docs/openapi.yaml`, `docs/API_V2.md`, `docs/SWIFT_V2_MIGRATION.md`
- `tests/Feature/Quickreads/QuickreadsTestCase.php`, `ApiV2Test.php`, `LegacyCompatibilityTest.php`, `OpenApiContractTest.php`
- `tests/Fixtures/quickreads-legacy-routes.json`

Modified additively in application code: `routes/api.php`, `routes/web.php`, `app/Exceptions/Handler.php`, `config/auth.php`. Modified for test isolation/current fixtures: `phpunit.xml`, `tests/CreatesApplication.php`, `tests/TestCase.php`, `tests/Feature/VideoTest.php`. The pre-existing user-pasted requirements document was read without changes; the unrelated pre-existing `.DS_Store` change was left alone. No legacy QuickReads controller/model/route file or Swift source was edited.

Final local verification: **27 tests, 4,152 assertions, all passing** (`php vendor/bin/phpunit`); **138 PHP files linted without syntax errors**; valid YAML and official OpenAPI 3.0 schema validation; complete before/after route-list comparison confirms **all 105 original route definitions unchanged**, including other project routes. V2 adds 16 primary operations on 15 paths (GET paths also support HEAD) plus the separate web reset form. The MySQL initialization failure described above remains the staging verification limit.
