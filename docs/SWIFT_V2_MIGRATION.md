# Swift migration to QuickReads API v2

Use [openapi.yaml](openapi.yaml) as the authoritative wire contract and [API_V2.md](API_V2.md) for backend decisions/limitations. This handoff changes no Swift source, bundle identity, keychain group, StoreKit product, Core Data store, saved text, bookmarks or reaction data.

## Base URL and rollout

After backend deployment and the isolated v2-token migration, use `https://leftlaneapps.com/api/v2`. The contract's server is the origin and its paths include `/api/v2`; do not double-prefix a generated client. All existing `/quickreads/...` URLs remain unchanged for installed versions. No old URL was moved to `/api/v1`, redirected, renamed or removed.

Use one shared client/configuration for v2, JSON body encoding, typed DTOs and typed errors. Build query parameters with URL encoding; never interpolate credentials or titles into paths. Support paging **before** replacing full-catalog callers. Failed requests must not masquerade as empty results or overwrite valid cached/local data. Preserve the legacy client path for rollback; the backend does not force an upgrade.

## Authentication

`POST /auth/login` accepts `{email, password}` and returns `SessionResponse`: `data.user`, `access_token`, `token_type: Bearer`, `expires_at`. Existing password accounts authenticate with the same credentials. Store the token securely and send `Authorization: Bearer <token>` on `/me`, `/me/favorites` and `/auth/logout`. Neither `facebook_id`, supplied user IDs, HTTP Basic nor cookies authenticate v2. Public catalog requests are account-independent even when credentials/aliases are sent.

`POST /auth/register` accepts `email`, `first_name`, `last_name`, `password`, `password_confirmation`, and optional/null `locale`; success is **201**, with the same session envelope. Names are required (max 255 characters); locale max 35. New passwords require 8–72 characters and at most 72 UTF-8 bytes; spaces are preserved. Login permits existing longer passwords but registration/reset apply the v2 limit.

Account IDs are **strings**. `Account.id` is the authenticated canonical ID. `legacy_account_id` is the exact existing `facebook_id` alias, potentially null/nonunique; it is not a Facebook login token. Compare it with an existing local profile only after credential verification. Do not create/reassign accounts by an unverified alias or automatically associate old unscoped Library/reactions with the current account.

Use `GET /me` to validate/refresh identity without credential-in-URL login. Expiry defaults to 30 days; dates are RFC 3339 UTC. There is no refresh token: a `401` requires reauthentication. `POST /auth/logout` with no body or `{}` revokes the presented session and returns `{data: {revoked: true}}`. Other device tokens remain valid. Keep guest/account data isolated locally and do not purge offline copies on logout.

`POST /auth/password/email` accepts `{email}` and returns **202** `ResetAcceptedResponse` with a generic acknowledgement; it does not reveal account existence. The email links to a separate v2 web reset form (or the agreed configured reset destination). `POST /auth/password/reset` accepts email/token/password/password_confirmation and returns `{data: {reset: true}}`, revoking all v2 tokens for that account. Request a fresh login afterward. Reset tokens expire in 60 minutes.

All four credential/reset endpoints have a 5-attempt/minute/IP/operation limit, plus the API group's 60-request/minute limit. Respect `429` and `Retry-After`. Avoid repeated submissions; do not convert a reset-mail server failure into a successful UI acknowledgement.

Relevant schemas: `LoginRequest`, `RegisterRequest`, `Session`, `Account`, `ResetEmailRequest`, `ResetAccepted`, `ResetPasswordRequest`, `ResetComplete`, `Logout`, and their response envelopes.

## October 14 additions: Sign in with Apple

This checkout implements these operations but does **not** establish production availability. Deploy the two new migrations, configure Apple privately and verify staging before connecting production buttons. Apple challenge/exchange return `503` while disabled/unconfigured. Disabling new sign-ins leaves configured lifecycle notifications and account deletion operational. The native audience remains **`com.leftlaneapps.shortstoriesdot`**; no bundle/keychain identity change or web Services ID is introduced.

1. On an explicit button tap, generate a fresh UUID and `POST /auth/apple/challenge` with `{attempt_id}`. Receive `data: {attempt_id, challenge_id, nonce, expires_at}`. Challenge lifetime is **300 seconds**. Repeating begin returns the same valid pending challenge. An expired/consumed attempt requires a fresh UUID.
2. Request Apple's native `.fullName` and `.email` scopes. Pass the returned **nonce unchanged, without hashing**, as `ASAuthorizationAppleIDRequest.nonce`. Set `state` to the attempt UUID and verify returned state. Preserve first-consent names locally while exchange is pending.
3. `POST /auth/apple` with required `attempt_id`, `challenge_id`, `identity_token`, `authorization_code`; optional/null `profile: {first_name, last_name}` and `locale`. Credentials are UTF-8 strings. Limits: identity JWT 16,384 characters; code 4,096; each name 255; locale 35; complete JSON body 32,768 bytes. Unknown fields are rejected. No email/password or client Apple user ID is required/accepted.
4. New and returning users both receive **200 `SessionResponse`**. Validate/persist the QuickReads bearer using the existing coordinator, then resume its single pending action. Cancellation/failure establishes no local session and triggers no automatic bookmark, Library upload or pending-action continuation.

Synthetic exchange example:

```json
{"attempt_id":"00000000-0000-4000-8000-000000000001","challenge_id":"AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA","identity_token":"EXAMPLE_APPLE_JWT","authorization_code":"EXAMPLE_SINGLE_USE_CODE","profile":{"first_name":"Example","last_name":"Reader"},"locale":"en-US"}
```

The backend verifies Apple's RS256 signature from trusted keys, issuer, exact native audience, expiry, issue time and nonce, with **60 seconds clock skew**. It redeems the single-use code using an ES256 server client secret and verifies the returned identity JWT, including matching subject/audience/nonce. Apple refresh tokens and server secrets never reach iOS. See [Apple's token validation contract](https://developer.apple.com/documentation/signinwithapplerestapi/generate-and-validate-tokens).

Canonical IDs stay decimal strings. New Apple accounts have `legacy_account_id: null`; absent names are empty strings. Returning users may omit email/names; stored metadata remains. New identities require a usable Apple-verified email (`422` otherwise); private-relay email is accepted. Apple-only accounts have no password method: reset-email requests keep the generic `202` response without implicitly adding a password.

An existing email collision returns **409**, `error.reason: apple_account_link_required`, message “Please sign in with email to continue.”, and `fields: {}`. It returns no account details/token and creates no Apple binding. Changed attempt reuse returns `apple_attempt_conflict`. `Error.reason` is an additive optional shared property, required for these Apple conflicts. Unknown/absent reasons get generic conflict handling. There is **no account-linking operation**; never infer an action from message prose.

Lost-response recovery:

- Keep the exact credentials, attempt, names and locale for retries. Key order and omitted/null optional values are equivalent. Exact retries recover the **same session** for **300 seconds after completion**, without code redemption or another bearer. Logout/revocation/deletion also ends recovery; changed payloads return `409 apple_attempt_conflict`.
- `503 apple_exchange_pending` with `Retry-After: 2` permits a brief exact retry while the first request finishes. If still unavailable, obtain fresh native authorization; never reuse the code under a new attempt.
- `503 apple_restart_required` means redemption timed out or had an ambiguous response: obtain a fresh challenge/native authorization. Failed/expired attempts subsequently return `401`.
- A database failure after a durable verified grant returns sanitized `500`; an exact retry can finish until the **original challenge expiry**. A crash before that durable receipt cannot recover and needs new authorization. Do not promise unlimited retries of a redeemed code.

HTTPS is required. All responses/errors are `private, no-store`. Challenge/exchange each have 10 requests/minute/IP, plus the API's 60/minute bucket; honor `429 Retry-After`. The owner must enable the Apple capability, authorize the server key privately, refresh provisioning, add the `Default` Apple entitlement, register the server notification URL and configure private-relay mail senders. Client/server/proxy diagnostics must redact credentials.

## Account lifecycle

Logout only revokes the QuickReads session. Apple refresh tokens are encrypted on the server. `POST /auth/apple/notifications` receives **Apple-signed server notifications**, not iOS bearer requests. Consent/account revocation invalidates affected Apple-derived sessions; forwarding changes update delivery state without deleting accounts. Separate email-derived sessions remain valid.

`DELETE /me` with `{delete_account: true}` is the implemented user-initiated deletion operation. Require deliberate UI confirmation and a bearer issued within **10 minutes**; reauthenticate on `401`. Success is `{data: {deleted: true}}`. The server revokes retained Apple tokens before deleting account data; Apple failure returns `503` and keeps the account for retry. Account reports, favorites, comments, ratings, resets and the matching newsletter subscription are removed. A hashed provider/event-time security tombstone remains for up to 90 days to reject pre-deletion proofs. Guest reporting and local offline data stay independently scoped. Coordinate the iOS deletion UI before production Apple release; logout is not deletion.

## October 14 additions: story activity reporting

The addendum references `STORY_ACTIVITY_API_PROPOSAL.yaml`, which was **not supplied**. The finalized backend operation is **`POST /story-activity`**, using OpenAPI `StoryActivityRequest`/`StoryActivityResponse`. Refresh the published spec and implement/test the matching adapter before enabling the production transport. This backend change modifies no Swift source or transport.

```json
{"story_id":"101","mutation_id":"00000000-0000-4000-8000-000000000001","guest_id":"00000000-0000-4000-8000-000000000002","reading_status":"reading","saved":null,"observed_at":"2026-10-14T20:00:00Z"}
```

Both state keys are required and independently nullable; at least one must be known. `reading_status` is `not_started`, `reading` or `finished`. Null means unknown/no update. Saved is independent of Reading/Finished; reporting never changes favorites or syncs Library/progress. Guests can report saved null/false, never true (`403`). Completion still comes from successful narration/explicit completion, never scrolling.

Accounts send the captured reader's valid bearer and omit/null `guest_id`. Ownership comes solely from the bearer. Invalid/expired Authorization returns `401` and never falls back to guest. Guests omit Authorization and use a dedicated random reporting UUID, separate from guest-access Keychain, reaction and device identities. Signup/logout never merges or reassigns queues.

Success is **200**:

```json
{"data":{"mutation_id":"00000000-0000-4000-8000-000000000001","story_id":"101","reader":{"kind":"guest","account_id":null,"guest_id":"00000000-0000-4000-8000-000000000002"},"accepted":true,"applied":true}}
```

Validate **all** returned identity fields and mutation UUID against the captured queue entry before acknowledging. `applied: false` still acknowledges an accepted stale report. Exact reader/mutation/payload retries recover the original acknowledgement for 90 days; changed reuse returns `409`. Never clear a newer local edit with an old acknowledgement.

Each known field is ordered independently by RFC3339 `observed_at` (up to milliseconds), then lexicographically larger lowercase mutation UUID on ties. A later unknown field cannot hide an older known observation of the other field. More than five minutes of future skew is `422`; reports older than 90 days are acknowledged without applying. Inactive latest state/minimal receipts expire after 90 days and are removed at the next scheduled prune. There is no event/session log.

Bodies are strict, maximum 32,768 bytes. Only canonical story IDs are accepted (`404` if unknown). No email, name, story text, reaction, position or fingerprint fields are accepted. HTTPS and `private, no-store` apply. Limit: 30/minute/IP plus the API 60/minute bucket. Retry the **same mutation** with bounded backoff after network errors, `429` (`Retry-After`), `500` or `503`. `400/403/404/409/413/415/422` require correction and are terminal for an unchanged payload. On `401`, reauthenticate the captured account and retain inactive queues separately. Reporting failure never undoes a valid read/save.

Only `/quickreads/story-activity`, behind the existing owner/admin guard, shows aggregate counts by story and guest/account without reader identifiers. There is no public history API. Account deletion erases its reports; owner support can erase guest reports using the dedicated reporting UUID and `quickreads:activity-forget-guest`. Update privacy disclosures before collection. Use staging/disposable accounts to verify the full HTTP acknowledgement contract before shipping a client transport.

## Endpoint mapping

Paths below are relative to the v2 base URL.

| Existing need | V2 request | Schema / migration change |
| --- | --- | --- |
| Explore | GET `/explore` | `ExploreResponse`; stable section ID/role, heading, paged summaries |
| Search submission | GET `/stories?q=...` | `StoryPage`; server title/author substring filtering |
| Category results | GET `/stories?category_id=...` | `StoryPage`; canonical category ID |
| Time results | GET `/stories?min_minutes=...&max_minutes=...` | Inclusive numeric minutes; omit upper bound for unbounded |
| Writer directory | GET `/authors` | `AuthorPage`; IDs/names/story_count, no whole catalog |
| Writer results | GET `/stories?author_id=...` | `StoryPage`; counts refer to distinct extant story IDs |
| Category directory | GET `/categories` | `CategoryPage`; ID/key/name/kind/description/count |
| Detail/related | GET `/stories/{id}` | `StoryDetailResponse`; author biography/date label and first related page |
| Read or offline save | GET `/stories/{id}/content` | `ContentResponse`; one original body with matching story_id/revision |
| Account state | GET `/me` | `AccountResponse`; verified identity |
| Favorite reconciliation | GET `/me/favorites` | `FavoritePage`; own remote membership, separate from local files |
| Save acknowledgement | PUT `/me/favorites/{id}` | `FavoriteStateResponse`; idempotent desired state |
| Remove acknowledgement | DELETE `/me/favorites/{id}` | Same schema, favorited false; succeeds after editorial deletion |
| Safe current legacy title lookup | GET `/stories/resolve?title=...&author=...` | `StorySummaryResponse`; 404 missing, 409 ambiguous; exact current values only |

Registration/login/reset/logout use the auth endpoints above. V2 does not require the old views counter or numeric rating endpoint for current client flows. Legacy numerical-rating behavior remains independent; do not map it to private reactions.

## DTOs and identity

Replace artwork/title identity with `StorySummary.id` everywhere in new network flows. Persist that ID through tiles, detail, Read, offline saving, related navigation and callbacks. Carry `author.id` rather than regrouping by normalized author names. Same title/author strings may represent distinct editions; never deduplicate by title.

A summary contains:

- String `id`, `title`; nullable String `summary`.
- Nullable numeric `reading_minutes`, Bool `is_classic`.
- Nullable `author: {id, name}` and `categories: [{id, key, name}]` (currently zero/one association).
- Nullable `artwork: {cover_url, thumbnail_url, width, height, content_type, revision}`.
- String `metadata_revision` (summary projection hash, **not** content revision).

It has no `story_filename`, `cost`, `time` String, `life`, `dates`, body or `favorited` field. Detail adds nullable biography/date_label inside author, `language: null`, and `related: StoryPage`. Fetch detail after selecting a compact list row; no full-catalog request is needed. A detail metadata hash does not replace the response ETag for detecting biography/related changes.

Categories use stored slug `key`, canonical ID and display `name`; `description` is null and `kind` is currently generic `category`. The source has no authoritative genre/editorial classification or descriptions. Category keys can change through website editing, so identify by ID. Keep UI colors/SF Symbols and any agreed legacy display mapping locally until richer editorial data exists. Preserve the existing featured-writer ranking in Swift; the backend author directory is alphabetical.

## Pagination, search and Explore

Every story/author/category/favorite list is bounded. Send `page` (default 1) and `per_page` (default 20, max 100). Read `meta.total`, `has_more`, nullable `next_page`, normalized `criteria` and nullable `links.next`. Resolve next links against `https://leftlaneapps.com`, **not** by appending them to `/api/v2`. A successful empty page is distinct from error. Unknown author/category/excluded-story IDs yield `404`; page past the end is empty `200`.

All orders have story/author/category ID tie-breaks. Offset pages are not a snapshot of ongoing editorial/favorite changes: deduplicate by canonical ID, keep filters stable while paging and restart when refreshing/filtering. Author counts do not mean the first story page is complete. Directory pages also require continuation.

Story default `sort=editorial` preserves stored category priority (null last), then ID. Other sorts are `title`, `latest` (creation descending) and `popular` (distinct favoriting accounts descending), with ascending ID ties. Text search is literal title/author substring, case/accent insensitive according to production MySQL collation; it has no theme/full-text/relevance ranking. Blank q means browsing. Reserved LIKE characters are literal, and parameters must still be URL-encoded. No extra Unicode normalization is guaranteed.

Both reading-duration bounds are inclusive. Existing editor estimates may be decimal; invalid stored strings become null and are excluded from duration filters. Omit max_minutes rather than sending `10000`. Adjacent `(5,10)` and `(10,15)` ranges overlap at 10; preserve that or deliberately revise client labels/bands. Do not silently treat "Under N" as a different exclusive contract.

Explore `data` follows the admin-curated section array, including custom IDs, headings and ordering. It defaults to six sections. The first has `id=featured`, `role=featured` and one daily UTC or hand-picked story. Other sections have role `collection`; default IDs are `latest`, `popular`, `under_10_minutes`, `classics`, `suggestions`. Treat IDs as strings, rather than a closed enum, and render the server’s sections. Use roles/IDs instead of heading text for identity. Initial nonfeatured pages contain up to eight summaries and their own totals/next links; shelves are bounded at 24 stories and automatic latest additions contains ten. See all follows section continuation, or `/stories?section=<id>`. Section ordering cannot be overridden with sort. A story may appear in different sections. Continue Reading stays local.

Related summaries use the same author ID, exclude the current story ID and provide an initial eight-item `StoryPage`; follow its supplied continuation rather than reloading the entire catalog.

## Content and offline safety

`Content` is a **single object**, not an array: `story_id`, exact `text`, `format`, nullable `language`, `revision`, `hash_algorithm: sha256`, `hash_encoding: hex`, `hash_input: original_utf8`. Verify the returned story ID before saving/reading. The revision hashes the exact original UTF-8 text bytes, before JSON escaping, with no NFC/NFD, newline/entity/HTML/whitespace normalization. Preserve original text and derive a separate cleaned speech representation; original and clean UTF-16 offsets are not interchangeable.

There is no stored language; null means unknown. Retain local voice/language fallback. Format is conservative detection: html if PHP strip_tags would change the stored text, otherwise plain_text. Do not render arbitrary HTML unsafely or change stored original bytes to reuse old offsets. Check representative actual bodies before migration.

`404` means missing identity; `503 unavailable` means an extant story with no nonempty body. No replacement ID/tombstone history exists. These errors do not require deleting valid saved copies, bookmarks or completion. Remote progress sync, cloud Library text sync and server audio are not implemented or needed for the current local workflows.

The current resolver can help fill missing IDs only when exact current title/optional author has exactly one match. It has no historical rename/edition aliases and cannot verify `local-v1` reaction hashes. Keep unresolved/ambiguous records and pending mutations locally. Do not guess first matches or rewrite every legacy local key using one string substitution. Private reaction transport remains disabled until separately commissioned and authoritative account/story mappings plus conflict/deletion protocols exist. Anonymous reactions remain local.

## Favorites and private cache handling

The legacy purchase-record name represented current favorite/saved membership, not payment or receipts. V2 uses canonical story IDs and authenticated ownership. PUT/DELETE are idempotent desired-state operations and acknowledge account/story/favorited. Update that item's remote UI state from the response; a complete Explore refresh is unnecessary.

GET favorites collapses duplicate legacy records and omits deleted stories. It is remote membership, **not** proof that text/artwork exists offline. Persist local text first; failure to acknowledge remote state must not undo a local save. Historical unscoped Library data must not automatically become remote account favorites. Serialize pending favorite operations and reconcile after reconnect; mutation UUID ordering/conflict resolution is not provided. A delayed PUT after DELETE may re-add state. Local reaction pending mutations/tombstones must not be sent through favorite endpoints.

Public catalog responses omit favorite flags so they can be shared safely. Merge authenticated favorite IDs with local offline state in the UI as separate concepts. Profile/favorites/auth responses use private, no-store. Keep app-side private state/account scopes separate and clear active credentials on logout without deleting another account's or guest's saved data.

## Cache, media and errors

Public GET/HEAD responses provide an ETag and `public, max-age=60, must-revalidate`. Store the response and validator keyed by the full URL; send `If-None-Match`. `304` has no JSON body and means reuse the cached representation. Detail/author/category/Explore and content all support it. All errors and auth/profile/favorites are private, no-store.

Artwork may be null. Covers have HTTPS URLs with a byte revision query, dimensions and MIME type; they have no expiry. `thumbnail_url` is currently null because no variants exist. Use the cover where available and a local placeholder otherwise. Use story IDs for callbacks, never artwork URLs. Durable offline artwork caching is still Swift work; static image headers/TLS/redirect behavior must be checked at deployment. Do not assume API JSON cache policy also configures image hosting.

Errors use `Error`: `{error: {code, message, fields}}`. Validation is `422` with `{field: [messages]}`. Handle `400 malformed_request`, `401 unauthenticated`, `404 not_found`, `405 method_not_allowed`, `409 conflict`, `413 payload_too_large`, `415 unsupported_media_type`, `429 rate_limited`, `500 internal_error`, and `503 unavailable`. A 401 is not an empty catalog; a 409 resolver result is not a missing story. Retry only when safe, respect Retry-After and preserve usable cached/local data. Numeric timestamps are not used for HTTP dates.

## Integration verification still needed

Backend tests cover local contracts and preserve the registered legacy route table; they do not replace staging checks against MySQL, real mail/media hosting or installed app binaries. Verify sanitized catalog/body fixtures, nullable fields, duplicate titles, Unicode search, time boundaries, pagination, token expiry/logout/reset, account switching and repeated favorite operations.

The client document names `/shortstories_db/*.php` and a historical rating-write POST that are absent from this checkout. Backend changes did not remove/move them; verify external deployment artifacts separately. The existing legacy reset broker is missing in this snapshot, and the legacy public user-list query has a SQLite ambiguity; neither was silently rewritten. Do not infer that every client-audit expectation matches the deployed legacy backend.
