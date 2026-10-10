# QuickReads iOS — API v2 Requirements

## 1. Current API Architecture

This is a requirements handoff based on a static audit of the Swift/iOS repository on October 9, 2026. It describes what the current app consumes, the integration problems visible in its source, and the client-facing capabilities a future API v2 should provide. It does not specify backend storage, framework, controllers, migrations, or internal implementation. Proposed field names are illustrative; final API architecture belongs to the backend project.

**Compatibility is mandatory: API v1 and the existing production URLs must continue working unchanged for legacy installed app versions.** A future `/api/v2/...` contract must coexist with them. See section 8 before changing any route or response.

### Audit coverage and evidence limits

The audit searched all first-party Swift sources, root managed-object classes, Objective-C reachability code, configuration, project membership, storyboard routes, tests, and existing technical documentation. It traced every identified first-party request through its parser and consuming screens, including uncalled and commented networking helpers. Relevant checked-in Alamofire and SwiftyJSON implementations were inspected to confirm request encoding and coercion behavior. Vendor SDK internals are not part of the QuickReads API contract.

No production requests, account operations, uploads, purchases, or server mutations were performed. The response shapes below are **client expectations**, supported by source and synthetic regression fixtures, not captured production responses. Unknown extra fields, actual response sizes, server authorization, cache headers, ordering guarantees, and deployed behavior must be verified in the backend project. Existing documents contain proposed reaction routes and backend implementation suggestions; those proposals are not deployed endpoint evidence, and their implementation prescriptions are outside this document's scope.

### Communication and storage

- First-party API calls use hardcoded HTTPS URLs on `leftlaneapps.com`. There is no central base-URL configuration, endpoint enum/router, shared authenticated API client, or v2 integration.
- The checked-in lockfile selects Alamofire 4.9.1. Calls use its default session and `URLEncoding.default`: GET and DELETE parameters go in the query string; POST parameters are form-encoded bodies. No current API caller explicitly requests JSON body encoding or supplies custom API headers.
- Alamofire supplies default compression, language, and user-agent headers. Login associates an HTTP Basic credential through `.authenticate(user:password:)`; it also places both credentials in the URL path. The Swift code does not establish an explicit reusable bearer-token, refresh-token, or authenticated-session lifecycle. Default session/cookie behavior may exist, but its server significance cannot be established here.
- Lists use `.validate().responseJSON`, with SwiftyJSON in `GetExplore`, `GetSearch`, and `CategoryData`, and manual Foundation casts in `DataCell`, login, registration, and text loading. The active HTTP responses are not decoded into Codable DTOs.
- Screen controllers construct requests directly or invoke small stateful helpers. Callbacks generally return arrays or a string, without a typed success/error result. Failed list calls return the helper's previous data; failed text calls return `""`.
- Images use `CustomImageView`, `URLSession.shared.dataTask`, and a global URL-keyed `NSCache`. Local Core Data stores saved story metadata/text, completion and legacy bookmarks; UserDefaults stores newer progress/preferences. These stores are not a remote Library or progress API.
- Private reactions are saved locally in versioned JSON. An injectable reaction sync protocol exists, but production supplies no HTTP transport. Read Aloud uses Apple speech synthesis and local audio files, not a QuickReads audio service.
- `DataUser` contains compiled but apparently uncalled legacy profile networking. `DataImage` and `StoreCell` contain entirely commented networking implementations.

Primary evidence: `QuickReads/DataCell.swift:61`, `GetExplore.swift:32`, `GetSearch.swift:21`, `CategoryData.swift:30`, `LoginVC.swift:41`, `CustomImageView.swift:18`, `StoryReactionSync.swift:3`, and `NativeSpeechReadAloudEngine.swift:67`. Source paths in this document are relative to the iOS repository root; filenames without a directory refer to `QuickReads/`.

## 2. Current Endpoints Used

All paths in the following tables are on **`https://leftlaneapps.com`**. None currently includes `/api/v1` or `/api/v2`. “Used” distinguishes active screen requests from legacy code or documentation-only contracts.

### Active first-party endpoints

| Method and current path | Input/encoding | Expected response and actual use | Call sites |
| --- | --- | --- | --- |
| GET `/quickreads/app/login/{encodedEmail}/{encodedPassword}` | Independently percent-encoded email/password path segments; an associated HTTP Basic credential | Object with String `email`, `first_name`, `facebook_id`. Email/ID must be nonempty. Creates a local profile and sets `LogIn`. No token is consumed. | `LoginVC.swift:41–85` |
| POST `/quickreads/users/register` | Form fields `first_name`, `last_name`, `email`, `locale`, `password`; locale is the device locale identifier | Object with the same three consumed profile fields as login. Submitted last name/locale are not persisted from a response by this flow. | `SignUpVC.swift:60–119` |
| POST `/quickreads/password/email` | Form field `email` | Object with String `response`, shown as the reset-request message. No reset completion endpoint appears in Swift. | `LoginVC.swift:119–175` |
| GET `/quickreads/app/explore` | Manually interpolated query `facebook_id` | Iterable sections with `title` and `collection`; collection rows become `Story`. Section order controls featured placement. | `ExploreVC.swift:54–69`, `GetExplore.swift:51–89` |
| GET `/quickreads/app/stories` | `DataCell` sends query `facebook_id`; text lookup calls the same path without that parameter | Flat array of story dictionaries. `DataCell` groups by String `category`, requires eight additional String metadata fields plus Bool `favorited`, and discards `id` from parsed entries. Text lookup instead searches the raw array by exact title and accepts `id` as String or NSNumber. | `DataCell.swift:61–115`; callers: `SearchTabVC.swift:91`, `SearchVC.swift:122`, `StoryDetailVC.swift:396`; separate lookup: `StoryDetailVC.swift:460` |
| GET `/quickreads/app/stories/text` | Encoded query `id` from the preceding catalog lookup | Array of objects; takes the **last String `text`** among the rows. Does not consume a story ID/version/format from the text response. | `StoryDetailVC.swift:449–481`; reused by Explore Read/save and Search save |
| GET `/quickreads/app/categories` | No explicit parameters | Iterable rows with `category` and `id`, converted to Strings by SwiftyJSON | `SearchTabVC.swift:81`, `CategoryData.swift:48–59` |
| GET `/quickreads/app/categories/show` | Manually interpolated queries `category_id`, `facebook_id` | Flat iterable story rows parsed by `GetSearch`; category and retained legacy Latest additions selections use this path | `SearchDetailVC.swift:53–77`, `GetSearch.swift:40–60` |
| GET `/quickreads/app/stories/time` | Manually interpolated queries `min`, `max`, `facebook_id` | Same story-row contract as category results | `SearchTabVC.swift:112–121`, `TimeVC.swift:54–70`, `SearchDetailVC.swift:53–77` |
| POST `/quickreads/app/records/purchase` | Form fields `facebook_id`, `title`; only when a local profile exists | Parseable JSON success is used as an acknowledgement/log output. Detail-based saving posts `refreshExplore` on success. This follows a **local story save**, not a StoreKit receipt submission. | `StoryDetailVC.swift:590–617`, `BookVC.swift:678–704`; Explore/Search saving reuse the detail method |
| DELETE `/quickreads/app/records/purchase` | Query fields `facebook_id`, `title`, using default DELETE encoding | Parseable JSON success posts `refreshExplore`. Local deletion already happened. | `HomeVC.swift:190–197` |

The catalog parser's required String metadata keys are `story_filename`, `title`, `author`, `cost`, `summary`, `dates`, `time`, and `life`, plus the separately required String `category` used for grouping. `favorited` is a required Bool in that parser. Other list parsers are more permissive; see section 4.

### Legacy or inactive integrations

| Status | Method/path | Contract visible in the repository | Evidence |
| --- | --- | --- | --- |
| Compiled helper; no current production caller found | Multipart POST `/shortstories_db/add_user.php` | `full_name`, `user_name`, `email`; optional JPEG part named `file`, compression 0.6, MIME `image/jpg`, filename derived from locally stored email. Only prints response text. | `DataUser.swift:20–58` |
| Compiled helper; no current production caller found | Multipart POST `/shortstories_db/edit_user.php` | Same fields plus `id`; same optional image conventions. No typed response, validation, or UI completion contract. | `DataUser.swift:61–103` |
| Compiled helper; no current production caller found | GET `/shortstories_db/get_users_db.php` | Blocking `Data(contentsOf:)`; expects an `NSArray`, stores raw values without a consuming response model. No filtering or authentication is visible. Actual returned records/access controls are unknown. | `DataUser.swift:107–117` |
| Removed from current client; preserve for older clients | POST `/quickreads/stories/ratings` | `facebook_id`, `title`, numeric `score`; documented historical contract | `STORY_REACTIONS.md`, Historical compatibility and removal |
| Removed from current client; preserve for older clients | GET `/quickreads/app/stories/{percentEncodedTitle}/rating` | Legacy average-rating read; exact production shape is not established by current active code | Same document |
| Entirely commented | Arbitrary image URL through AlamofireImage | `DataImage.getNetworkImage`, image decode/cache helper; its consumer `StoreCell` is also commented out | `DataImage.swift:8–51`, `StoreCell.swift:8–56` |
| Undeployed interface | Reaction fetch/upsert/delete | `StoryReactionSyncTransport` has no URL, HTTP implementation, or production injection | `StoryReactionSync.swift:3–12`, `StoryReactionStore.swift:39` |

### Other networking and hardcoded external destinations

`story_filename` is passed directly as an image URL across Explore, Search/Library rows, related stories, Story Detail and Read Aloud artwork. Image hostnames/redirects are not defined by a central allowlist. `CustomImageView.swift:18–60` is the active downloader; no explicit first-party background/resumable download service exists.

`CoinsStoreVC` requests products and payments from Apple's StoreKit. Banner startup/loading uses Google Mobile Ads in `AppDelegate`, `ReadingDesign`, and `Helper`; these are external SDK services, not QuickReads endpoints. Push capability/background mode and legacy Facebook plist metadata do not establish a current device-registration or Facebook-login API: no matching Swift request flow was found.

Settings and sharing hardcode the developer homepage, contact email, an App Store review URL, and an external privacy-policy page. They open/share external destinations and do not decode QuickReads JSON. Reader sharing currently sends title/author/branding without a story deep link. These links are not undocumented API endpoints.

## 3. Feature-by-Feature Requirements

Request counts below count first-party HTTP requests, excluding image downloads, advertisements and StoreKit. Counts assume a successful normal flow, one user action, and no usable local text unless stated. Failed lookup can stop before the second text request. Counts are based on call sites, not live traffic measurements.

### 3.1 Authentication and user account

**Current behavior:** Email/password login, registration with names/email/locale/password, reset-email request, and local logout. Login/registration create Core Data `Profile` records. `first_name` is stored as `fullName`; `facebook_id` is stored as `userName`. Launch trusts the persistent `LogIn` flag. Logout removes local profile records and switches to Login; it sends no logout/revocation request.

**Current API usage:** One request per login, registration, or password-reset submission. Later discovery and analytics calls pass the stored ID. No current account-fetch, profile-refresh, account-edit screen, token refresh, or remote logout request is implemented. The old `DataUser` methods are a separate inactive contract.

**Problems/inefficiencies:** Passwords appear in the login URL even though segments are encoded. A locally stored user ID is not proof of authentication. The client neither consumes token expiry nor distinguishes session expiration from other failures. Registration submits last name/locale but only consumes first name/email/ID. Repeated taps are not protected by a shared authentication request state.

**API v2 requirements:** Accept login credentials in an HTTPS request body; return verified, stable account identity and the profile fields needed by the existing app. Document an authenticated token/session contract, expiry, renewal or reauthentication, and logout/revocation behavior. Derive ownership of private reads/writes from validated credentials. Define registration field constraints and structured validation errors, and an unambiguous reset-request acknowledgement. Allow the future client to validate/refresh its existing account identity without repeating credential-in-path login. Preserve an authoritative relationship to legacy `facebook_id`; do not infer Facebook authentication from that name.

### 3.2 Explore and editorial story lists

**Current behavior:** One ordered response supplies featured and other story collections. The first section is rendered as Today’s pick; other titles come from the response. Initially one featured story and up to four stories per other section are visible. See all/Show less expands already-downloaded collections. Continue reading is inserted from local Library state.

**Current API usage:** One Explore request on initial load, pull-to-refresh, or `refreshExplore` notification. Expansion makes zero further calls. Direct Read of an unsaved story adds two requests through the shared text loader. Saving also loads text and may send analytics/refresh requests described in 3.7.

**Problems/inefficiencies:** All section members arrive even when initially hidden. Every parsed row carries detail metadata, including author biography/dates and cost, although compact tiles only need artwork/title/author/time. This metadata is not globally unused: it is passed to detail/offline saving. Section identity/type is inferred from array index and display title; there are no parsed section IDs, totals, continuation tokens, or freshness metadata. Refresh can overlap because `loadResults` has no shared in-flight guard.

**API v2 requirements:** Return ordered editorial sections with stable section identity, a documented role for featured content, display heading, and story summaries with stable story IDs. Provide sufficient featured summary text and row metadata for the current layout. For bounded initial collections, expose total/has-more information and a continuation contract so existing See all can eventually load the rest. Keep story ordering explicit and stable across pagination. Continue reading does not require a backend response; preserve local derivation.

### 3.3 Search landing and categories

**Current behavior:** Search loads categories plus a writer catalog. Category names are transformed by English substring matching into local display names/descriptions/SF Symbols/colors. Names containing `latest` are filtered out of the current landing page, but legacy Latest additions category results remain supported.

**Current API usage:** Two requests per initial landing load or refresh, in parallel: categories and the complete stories catalog. A DispatchGroup stops the spinner after both completions. Results retained inside each helper survive network failure, but callers cannot identify which request failed.

**Problems/inefficiencies:** Category rows expose only a name/ID. The app reconstructs descriptions and category meaning from names, making spelling, localization, and newly added categories fragile. It loads the full catalog just to build writers. A real empty result and failed first load receive similar UI treatment. Categories and writer data have no shared response revision/freshness information.

**API v2 requirements:** Supply stable category identity, a canonical category key, display name and optional description. Preserve category ordering and define which entries are genres versus editorial collections such as Latest additions. Client-owned colors/SF Symbols can remain in UIKit; the API need not deliver UI styling. A compact Search discovery response containing categories and featured writer summaries would reduce calls; equivalent independently cacheable resources are also acceptable. It should not require downloading every story to display writer names/counts.

### 3.4 Writers, directory and author-specific browsing

**Current behavior:** `SearchWriter.directory` flattens `DataCell` groups, normalizes Unicode/case/whitespace in author/title, deduplicates titles within each author, counts retained entries, and places five named writers first. The directory sorts names alphabetically. Author selections use already-loaded entries.

**Current API usage:** The landing catalog request supplies all writer data. Opening See all or writer results makes zero extra list requests. Opening a story from those results still follows detail/text requests. No author endpoint is present.

**Problems/inefficiencies:** The app downloads every story's biography, summary and other metadata to calculate a name/count list. Counts depend on parser completeness and the assumption that the catalog is complete. Display-name equality substitutes for author identity. Normalization/deduplication can merge distinct authors or distinct editions with identical normalized titles. The local featured-name ranking is a presentation choice, not evidence of a backend selection contract.

**API v2 requirements:** Provide author summaries with immutable author ID, display name and count of distinct available stories, with documented count/availability semantics. Support the existing alphabetical directory and story selection by author ID without a full-catalog download. Story detail should receive that author's optional biography and date label in the same response. Initial-circle avatars can still be derived locally; author portraits are not required. Preserve existing featured ordering in the client unless an explicit editorial ordering contract is agreed.

### 3.5 Text search, category results and reading-time filters

**Current behavior:** Each submitted text search refetches the complete catalog and locally filters title, then author, case-insensitively. A story matching both appears once through that title/author match logic. The placeholder mentions themes, but the implementation does **not** search summary/category/theme fields. Blank search returns the downloaded entries. Category and time selections use server-filtered results.

**Current API usage:** One full-catalog request per text-search submission; one category/time request per results screen. No per-keystroke API calls or server text-search request exists. Search result cells need artwork/title/author/time and local save state; additional metadata is carried forward to detail/save.

**Problems/inefficiencies:** Repeated queries download the same catalog. Grouping by category and immediately flattening it changes a flat response into a client grouping representation that search does not need. Cross-category duplicate catalog rows can remain in generic search; the title/author match logic does not deduplicate distinct rows by story identity. There is no total, pagination, selected-filter echo, or relevance contract.

The main time controls send `0–9`, `10–19`, `20–29`, and `30–10000` minutes. The separate More picker shows 5/10/15/20/30/45/60 and sends `(1,5)`, `(5,10)`, `(10,15)`, `(15,20)`, `(20,30)`, `(30,45)`, `(45,60)`. Its labels say “Under N minutes”, but inputs are adjacent bands with shared boundary values. Whether server bounds are inclusive, exclusive, or rounded cannot be determined here; these are not proven exact-equality filters.

**API v2 requirements:** Support submitted text search over existing title/author fields with documented case/Unicode handling, result ordering, blank-query semantics, and unique story identity. Provide filters for current category, author and reading-time browsing, with compact consistent story summaries and pagination metadata. Define reading-duration units, estimate/rounding behavior, lower/upper inclusivity, and an unbounded upper range without a `10000` sentinel. Echo applied criteria where useful for request/result matching. Theme/full-text search is not a required existing behavior; resolve the placeholder mismatch as a separate scope decision. Preserve v1 range semantics; clarify any future More-picker behavior before changing it.

### 3.6 Story detail, author information and related stories

**Current behavior:** The selected list item supplies title, image URL, author, summary, `dates`, `time`, and `life`. Detail already has author information from that item; it does not issue a separate author lookup. It then fetches the complete catalog, filters exact author-name matches, removes the current title, deduplicates by title, and appends locally saved entries as offline fallback. Summary/biography use plain attributed text views. Related selections open another detail controller.

**Current API usage:** One full-catalog request when each detail screen loads, even when the selected story is saved and its metadata/text are available locally. Read on a cold detail adds a second catalog request plus text request: **three API requests across opening detail and reading**, before counting the originating list. Usable passed/saved text eliminates those two text-loader requests, but normal detail presentation still attempts its related catalog refresh.

**Problems/inefficiencies:** Two independent catalog reads serve related filtering and ID lookup. Author matching here is exact, while writer grouping is normalized; the same catalog can yield different author groups. A title collision affects current-story exclusion, deduplication and content selection. Full-catalog biographies/dates are repeated on story rows. Detail depends on list endpoints carrying enough metadata rather than a dedicated detail contract. `dates` is displayed beside author name, but its meaning/format is not explicit.

**API v2 requirements:** Given the story ID supplied by any list, return the story's detail metadata, author identity/name/optional biography/date label, and an ordered related-story summary collection in one reasonable response. Define related-story ordering, exclude the current ID, and prevent duplicate identities; support a continuation if the list is bounded. Body text may remain a separate on-demand resource to keep initial detail lightweight. Detail should not require a complete catalog, an author-name reconstruction, or information omitted by a compact list DTO.

### 3.7 Offline Library, favorites/bookmarks and save/remove analytics

**Current behavior:** Saving commits full text and metadata to Core Data. Search/Library saved indicators use the local Library; detail checks local saved text/state. Explore ORs server `favorited` with local membership and later updates bookmark callbacks by artwork filename. Thus “remote favorited” and “available offline here” can disagree. Read does not require saving. Library renders local Saved/Reading/Finished lists and sorts newly inserted AddCell copies by local creation date.

**Current API usage:** Listing the Library requires zero API calls, apart from uncached artwork requests. Saving from Explore/Search/detail requires up to two text-loader requests, plus one POST analytics call if a profile exists. Successful detail-based analytics triggers one Explore refresh if that controller is observing: **up to four additional API calls for a cold save**, excluding the originating list/detail load. Saving from an already open Reader uses its supplied text: zero text requests and at most one analytics POST; that method does not itself trigger Explore refresh.

Library removal first commits locally, and `LibraryPresentation.remove` posts an immediate Explore refresh. `HomeVC` then sends one DELETE when a profile exists; success posts another Explore refresh. With an observing Explore controller, a successful Library removal can therefore cause **two Explore GETs plus one DELETE**. Reader bookmark removal uses the same local removal/refresh but does **not** call `HomeVC.recordDelete`. These are client-path differences, not proof of server intent.

**Problems/inefficiencies:** An endpoint named purchase is used for saves/removals without amount, cost, product ID or receipt. Its relationship to server `favorited` is undocumented. There is no remote favorites-list retrieval/reconciliation flow. Successful local changes persist even if analytics fails, and no durable retry queue is implemented for these events. Whole-feed refreshes follow writes, sometimes twice. Local historical Library copies are not partitioned by account and must not be automatically adopted by a newly authenticated account.

**API v2 requirements:** Document whether these operations are account favorites, an event log, or both. If v2 retains account favorites, expose an authenticated current saved/favorite state and idempotent add/remove acknowledgement keyed by stable story ID; provide reconciliation/read capability only to support that existing state, not as a claim that Library sync already exists. Clearly distinguish remote favorite state from local offline-download availability. If these are analytics only, give events explicit save/remove semantics and a retry/deduplication contract. Return enough acknowledged state to update the affected item without refetching all of Explore. Offline save success must remain independent of event delivery; downloads/read access must not depend on a purchase-record acknowledgement.

### 3.8 Reader content, reading progress and completion

**Current behavior:** Reader receives full text from its caller; the text loader prefers passed text and saved AddCell text. It displays the original string, while speech cleans a separate representation. Reading positions, normalized fractions, speech offsets and Finished state are persisted locally. Read Aloud completion can mark Finished; opening a finished story preserves that state unless explicitly changed.

**Current API usage:** Unsaved content loading uses the two-request catalog-title-ID-text chain described above. Saved text is readable with zero content requests. Scrolling, listening progress, moving between Library sections and Mark as Read send zero progress/completion requests.

**Problems/inefficiencies:** The text response is an array although the caller needs one story body; selecting the last text row can hide duplicates/ambiguous ordering. It carries no consumed identity or revision. The client computes SHA-256 over original UTF-8 text to protect position/cache reuse and creates original-to-clean UTF-16 mappings for speech. `ReadAloudStory.storyID` is currently the title. Language defaults to `en-US` because no catalog language field is consumed. Content changes, newline normalization, HTML/entity changes or identical titles can invalidate/misapply positions if handled carelessly.

**API v2 requirements:** Fetch a single, explicitly identified story body directly from the list/detail ID, with original text, declared content format, language and content revision or documented hash. Preserve paragraph breaks and Unicode. Body availability/removal/restriction must have explicit outcomes, rather than an empty string for every failure. Support durable local storage and reasonable revalidation; return enough identity to ensure the downloaded text matches the requested story. Define `dates` separately from content revision timestamps.

Remote progress synchronization is **not a required capability demonstrated by current networking or a pending transport**. The required improvement is reliable content identity/versioning for existing local restoration. If progress sync is separately approved later, its payload must distinguish original-text UTF-16 from clean speech UTF-16, include content revision, normalized fraction and reading state, and define ownership/conflicts. Legacy pixel offsets are layout-dependent and cannot be treated as a portable cross-device position.

### 3.9 Private story reactions — implemented locally, remote interface prepared

**Current behavior:** Reader supports Loved it (`loved`) and Not for me (`not_for_me`), with one optional reason: `not_my_style`, `too_long`, `didnt_connect`, `not_in_the_mood`. Selecting the same reaction again removes it. A reason is only valid for `not_for_me`. Feedback is private and independent of saving, completion and narration.

**Current API usage:** Zero production reaction requests. Account changes retain a durable pending mutation UUID; removal retains a pending deletion. Anonymous reactions have a separate persistent local scope and are never uploaded/adopted automatically. The sync seam defines fetch/upsert/delete and validates returned story/account identity. Current reaction identity is `local-v1:` plus a SHA-256 of normalized title/author; the app does not retain the catalog's server ID in this model.

**Problems/inefficiencies:** Authentication, authoritative story/account mappings and transport are absent. The local seam has mutation IDs/timestamps, but no deployed server-version/conflict envelope. Local Codable Date encoding is not a specified HTTP wire date format. Client wall-clock ordering alone is insufficient for a future multi-device conflict contract.

**API v2 requirements:** If enabling this already-prepared account sync, provide authenticated private fetch/upsert/delete, one current reaction per account/story, explicit absence/deletion semantics, allowed-value validation, and optional reason handling. Return verified story/account identity and documented timestamps/version metadata. Repeated mutation UUIDs must be safely acknowledged without duplicating or resurrecting state; conflicts must preserve retryable local edits/deletions. Resolve authoritative aliases from legacy account IDs and `local-v1` story identities before enabling upload, including ambiguous/renamed records. Anonymous feedback remains local unless an explicit future product decision changes that.

Do not expose public reaction totals, create a recommendation service, translate historical stars/notes into reactions, or remove legacy rating support as part of this contract. An older proposal in `STORY_REACTIONS.md` uses a different illustrative route prefix; it is not an active v1 endpoint. Final v2 paths remain a backend decision.

### 3.10 Images/media and sharing

**Current behavior:** List/detail/Library/Read Aloud artwork uses the same server-provided image URL. `CustomImageView` clears reused images, checks 2xx/image decoding, caches by URL, and ignores assignment when the view now represents a different URL. It does not expose completion/error state, retain/cancel its task, coalesce in-flight requests, or explicitly persist artwork with offline stories.

**Current API usage:** A cache miss can issue one GET per configured image view. Simultaneous views requesting the same uncached URL can each start a request. UIKit reuse guards correct image assignment but does not cancel transfer. URLSession defaults may apply HTTP caching; actual server headers and persistent-cache behavior are unverified.

**Problems/inefficiencies:** One unqualified `story_filename` field serves 76 pt thumbnails and larger artwork. No dimensions, variants, revision, expiry or missing-artwork semantics are consumed. Artwork filename is also used as an identity in Explore callback updates. `Info.plist` broadly allows arbitrary loads; returned HTTP URLs/redirects may be a compatibility dependency that needs investigation.

**API v2 requirements:** Return explicit HTTPS artwork URL(s), predictable content type and missing-artwork/null semantics. Offer a small thumbnail and larger cover variant if available, with dimensions/aspect ratio and revision/cache validators, so small rows avoid transferring oversized artwork. URLs should be stable for a given asset revision, or document expiry/refresh behavior, and should work with offline caching. Artwork must never identify a story. An optional canonical story share URL would support existing sharing; it needs a separately agreed destination/app-link contract and is not a mandatory new navigation feature.

### 3.11 Read Aloud and optional future audio provider

**Current behavior:** Apple speech voices are enumerated locally. Native speech writes per-chunk CAF files into a 200 MiB managed cache; playback, seek, voice previews and progress run locally. Codable audio documents contain cleaned text, chunk ranges, estimated/actual durations and generation state. Artwork may still use network images.

**Current API usage:** Zero QuickReads audio-generation/download/voice-list calls. `ReadAloudEngine` and `ReadAloudChunkGenerating` are replaceable provider seams, not HTTP clients. “Remote commands” in the engine refer to iOS media controls, not a backend service.

**Problems/inefficiencies:** A missing story language/immutable ID/content revision complicates voice selection and cache/restoration. HTML cleanup and timing estimates are performed locally. Existing provider seams make a future service possible but do not establish that one is required.

**API v2 requirements:** For current functionality, reliable story text/language/identity/revision is sufficient. If a remote narration provider is explicitly commissioned, its client contract would need provider/voice/language identity, content revision, playable chunk URLs, declared duration/estimated status, preparation status and retryable failures, plus documented text-range units and mapping to original text. Such a contract is conditional and should not be counted as a required launch endpoint. Do not upload locally generated Apple speech or require server audio for existing offline listening.

### 3.12 Legacy profile media, coins, ads and historical feedback

**Current behavior/API usage:** The uncalled `DataUser` helper can upload a photo/profile or synchronously fetch a raw user array; no active profile editing screen/caller was found. Coins use Apple's products/payments and a local keychain balance. The purchase-record endpoint is separate from those payments. Ads use Google's SDK/configuration; no first-party ad-management or Firebase API is configured. Historical Core Data Rating/Comment records remain for compatibility, but the current app does not read/write them through a feedback UI.

**Problems/inefficiencies:** The legacy profile helper has no defined response/error/UI callback contract, uses an email-derived filename, and potentially blocks its calling thread. Its user-list payload/access rules are unknown. StoreKit receipt validation, remote balances/entitlements and account-level ad behavior cannot be inferred from the purchase-record route.

**API v2 requirements:** Preserve deployed legacy routes for old clients after backend verification. Do not make inactive profile uploads, a new coins ledger, receipt service, ad service, push service, public user directory or revived numerical ratings a required v2 capability without a separately confirmed current consumer. If profile editing is explicitly retained later, require an authenticated own-account contract, documented image constraints, and a typed resulting profile/image response rather than downloading all users. Existing StoreKit identifiers/keychain compatibility must be preserved independently of API naming.

## 4. Current Swift Models

### 4.1 Active HTTP response models and parsers

| Model/parser | Fields consumed | Important limitations |
| --- | --- | --- |
| `Story` (`Story.swift:11`) | String `filename`, `title`, `author`, `cost`, `summary`, `dates`, `time`, `life`; mutable Bool `favorited` | Not Codable. `filename` comes from `story_filename`. No server story ID, author ID, categories, language, content revision or body. Used by Explore and category/time/writer results. |
| `DataCell.Entry` (`DataCell.swift:21`) | Same metadata and Bool `favorited`, plus String `txt` | Duplicates `Story` and doubles as a local saved-story model. Catalog parsing always assigns `txt: ""`; local AddCell/UpdatedCell construction supplies real text and `favorited: true`. No server ID. |
| `DataCell` raw catalog (`DataCell.swift:81`) | Above fields plus String `category` | Groups into `[[Entry]]` and parallel `categories`. A missing/wrong-type field silently skips a row. Category grouping happens before metadata validation, so a malformed row can create an empty category group. |
| `Explore` (`GetExplore.swift:14`) | String section `title`, `[Story] collection` | No section ID/type/pagination/total. `GetExplore.counts` reconstructs collection lengths; active screen uses collections directly. |
| `Category` (`CategoryData.swift:14`) | String `category`, String `id` | No description/key/type; categories need separate presentation mappings. |
| Login/registration dictionaries | String `email`, `first_name`, `facebook_id` | No DTO or consumed session metadata. `first_name` maps to local `fullName`, `facebook_id` to local `userName`. No inferred meaning should depend on those local names. |
| Reset dictionary | String `response` | Message-only acknowledgement; no consumed machine-readable result code. |
| Text-loader raw dictionaries | Catalog `title` and `id`; response row `text` | Catalog ID accepts String/NSNumber. Text array chooses last String text. No typed single-content response or response-identity validation. |
| Analytics response | Any parseable successful JSON | Values only logged or used to trigger notification; no consumed event/favorite record fields. |
| Legacy `DataUser.userValues` | Raw `NSArray` | No field decoding/consumer found; cannot enumerate actual user fields from this repository. |

### 4.2 Field usage, optionality and type inconsistencies

| Backend key | Current use | Requirement/observation for v2 |
| --- | --- | --- |
| `id` on catalog rows | Needed transiently for text lookup, discarded by `Story`/`Entry` and discovery parsers | Stable, consistently typed story ID must survive every list/detail/content flow. Its actual production type is unverified; the text loader deliberately accepts two types. |
| `story_filename` | Remote artwork loading, local persisted URL, Explore callback matching | Rename/document as artwork metadata in v2; optional/null when absent. Never use the URL as identity. |
| `title` | Display, local Library/bookmark/completion keys, analytics identity, exact catalog lookup | Required display text plus an immutable independent ID. Renames/collisions need authoritative compatibility mapping. |
| `author` | Display, generic search, writer grouping, related filtering | Provide author identity/name; exact comparison and normalized grouping currently disagree. No evidence justifies forcing an author profile to be complete for every story. |
| `category` | Required for `DataCell` grouping, discarded from each `Entry`; not searched by generic text search | Return typed category identity/association where needed. Clarify whether a story can appear in multiple categories and how duplicate rows represent it. |
| `summary` | Featured summary, detail display, persisted offline; carried invisibly in ordinary rows | Optional if unavailable; detail should not vanish from a catalog just because summary is null. Need not accompany every compact row if detail is independently fetchable. |
| `dates` | Displayed beside author name and persisted | Apparently an author-related display label; precise meaning is unknown. Optional, explicitly named/documented. Do not assume it is an ISO date or a story publication timestamp. |
| `time` | Displayed as minutes, passed through saved metadata; server time-filter inputs are Strings | v2 should provide numeric estimated reading duration with documented units and rounding. v1 must retain its String contract. Reading time is distinct from narration duration. |
| `life` | Author biography in detail, copied into offline story metadata | Optional author biography with clear plain-text/content-format rules. Repeated in current story rows; not needed for a writer name/count or compact result row. |
| `cost` | Required by catalog parser, forwarded/persisted; no active story cost calculation/display/access decision found | Apparently unused for active purchasing behavior, but **not safe to remove from v1**. Confirm business meaning. If access rules are needed in v2, specify availability/access information; do not invent paid-story behavior or infer coin price. |
| `favorited` | Explore card state/detail input; ignored for Search's actual local saved indicator | Native JSON Bool in a defined account context if supported. Remote favorite and offline availability need distinct meanings. |
| `text` | Reader/offline original string | Single identified body, format/language/revision; not interchangeable with Swift's local `txt` key or speech's `fullCleanText`. |
| `facebook_id` | Legacy account identity parameter/response | Explicit stable account ID/alias in v2; never a substitute for authentication. |

Required core identifiers/title should not silently default when malformed. Summary, biography, date labels and missing artwork are reasonable v2 optional fields. Favorite state should be explicitly absent/not applicable for an unauthenticated response if that differs from false. Reading-duration absence, if allowed, needs a documented null/display/filter rule. These optionality suggestions apply to new DTOs, not to v1 responses or current Swift casts.

`GetExplore`, `GetSearch`, and `CategoryData` use SwiftyJSON `.stringValue`/`.boolValue`. In the checked-in version, strings can be coerced from numbers/booleans and default to empty for null/missing values; booleans accept numbers and several String representations and otherwise become false. `DataCell` instead requires actual Strings and a Bool cast. Thus identical malformed/type-shifted rows can appear on one screen and disappear on another. Login/registration require String account IDs while text lookup accepts numeric story IDs. No proof that production currently returns every inconsistent type is implied; these are differences in the client contract.

**Apparently unused versus unknown:** `cost` has no active visible behavioral use beyond copying/storage; catalog `id` is unused by normal parsed list models but essential to raw text lookup; detail-only fields are unused on compact lists but used after navigation. Unknown response fields are ignored by these parsers and cannot be inventoried without backend/sample-response access. Do not assume registration returns last name/locale, or that catalog text/categories/extra timestamps are returned simply because such fields would be useful. Synthetic tests do not establish additional production fields.

### 4.3 Codable models and local-only representations

- `StoryReactionRemoteRecord` is the only explicit future remote record DTO found. Its coding keys are `story_id`, `user_id`, `reaction`, `reason`, `updated_at`. It is not sent by production. Mutation UUIDs are separate transport arguments; server versions/acknowledgement envelopes are not represented yet.
- `StoryReaction`, `StoryReactionReader`, `StoryReactionPendingChange`, and the store's `Storage` encode local identity/scope, reaction/reason, date and queued edits/deletions. Their local JSON/date encoding is not an existing HTTP schema.
- `ReadAloudStory`, `ReadAloudVoice`, `ReadAloudSavedProgress`, `ReadAloudSavedSession`, `ReadAloudState`, `StorySpeechDocument`, `SpeechChunk`, `SpeechTextRange`, `SpeechGenerationStatus`, `ReadAloudCacheKey`, `ReadAloudCacheEntry`, and `ReadAloudCacheManifest` are local story/speech/cache/session models, not backend responses. Some encode local file URLs or Apple voice quality; do not export those blindly as an API contract. `SpeechChunk.actualDuration` and `cachedAudioURL` are optional; preparation states are local.
- `ReaderContentPosition` encodes text hash, original UTF-16 offset and update date for local restoration. Clean speech offsets belong to a different representation.
- `SearchWriter` is derived author/count/story grouping created because there is no consumed author-directory contract. `CategoryPresentation` is local display mapping. `LibrarySnapshot` reconstructs state across saved entries, completion, bookmarks and overrides; it is not decoded from a server response.
- Core Data `AddCell`, `UpdatedCell`, `Page`, `RangeEntity`, `Completed`, `Profile`, archived `Rating`, and `Comment` are persistence models. Their original entity/attribute hashes are compatibility constraints, not instructions for backend schema. `Story`/`Entry` duplication and grouped-array wrappers are client integration debt; resolving them will require a separate future Swift migration, not this audit.

## 5. Missing or Awkward API Capabilities

The source justifies these improvements to the client contract:

1. **Stable identity across resources:** parsed story IDs, author IDs, section IDs and canonical category keys; authoritative mappings from legacy identities. Existing titles, artwork and normalized hashes cannot safely substitute for this.
2. **Direct single-story content lookup:** remove the need for a catalog scan before every cold Read/save. Return one identified/versioned body instead of ambiguous text rows.
3. **Self-sufficient detail:** metadata plus author information and related summaries, avoiding whole-catalog retrieval. Existing author metadata must not be lost when adopting smaller list payloads.
4. **Compact authors/counts and server query/filter support:** preserve existing writer/title/author/category/time behavior without repeated full-catalog downloads and name-based reconstruction.
5. **Explicit favorite/event semantics:** distinguish account favorite from offline download, and acknowledge one mutation without feed refresh. Existing save/delete events do not prove a remote Library sync feature.
6. **Typed errors and documented empty/absence states:** distinguish unavailable service, expired authentication, unavailable story, malformed data and legitimate zero results.
7. **Pagination, freshness and resource metadata:** list continuation/order, content language/revision, image variants and validators. The client currently has no consumed metadata for these.
8. **Conditional private reaction sync:** an authenticated, idempotent/conflict-aware contract matching the visible undeployed seam. Enable only after identity/authentication prerequisites are resolved.

Remote Library/progress/preferences synchronization, account photo editing, server narration, recommendations, public reactions, social/community features, new payments and push delivery are not required merely because local models, commented code, plist entries or external SDKs exist. Conditional requirements in section 3 must be explicitly commissioned before being treated as launch scope.

## 6. Error Handling and Authentication Requirements

### Current error behavior

| Area | What Swift currently does | Migration implication |
| --- | --- | --- |
| Explore/category/time/category catalog helpers | Network/validation failure calls back with previous arrays; prints the error. Fresh malformed JSON can coerce blanks or skip rows. | Spinner completion is mostly implemented; the missing piece is failure/staleness information, not a new server-controlled loading spinner. |
| Full catalog | Wrong top-level type returns previous grouped entries; malformed rows are skipped without a parsing error. | A 2xx JSON error object or partial malformed response may masquerade as old/empty data. |
| Text | Any request/cast/missing-ID/missing-text failure becomes `""`. | UI usually reports a connection issue even for not-found, access or decoding problems. |
| Login | Every failure presents invalid email/password. Credentials are intentionally not logged by this flow. | Transport/service/contract failures cannot be distinguished from rejected credentials. |
| Registration | Generic error alert, some malformed top-level responses only log/return | Field-specific failures and retryability are unavailable. |
| Password reset | Requires object/String `response`; failures mostly print and may show no user-visible outcome | A successful request and an unrecognized response are not clearly distinguished. |
| Save/remove analytics | Fire after committed local edits; no common retry/pending-state handling. Some response bodies are printed. | Failure cannot roll back a valid offline save/removal; success currently expects JSON rather than a bodyless 204. |
| Image/legacy uploads | Images often fail silently; legacy uploads print strings without status validation/completion | Their failure contracts are separate from API JSON errors. |

### Required v2 contract

- HTTPS for API and media, including documented redirects. Credentials must not appear in path/query parameters or error messages. Provide a secure authenticated token/session lifecycle and explicit 401/403 handling; private ownership must be checked against authenticated identity.
- Choose and document consistent JSON field types, casing, response shapes, null/omitted semantics and date/time units. List resources need an identifiable item collection and metadata; single resources need a single identifiable object. The contract need not impose one particular envelope design.
- Use meaningful HTTP success/error status codes and a stable JSON error representation with machine-readable code, safe message, optional field errors and diagnostic request/correlation identifier. Distinguish invalid input, rejected/expired authentication, forbidden access, not-found/unavailable content, conflict, rate limiting and temporary service failure. Return retry guidance such as `Retry-After` where applicable.
- Define response bodies for mutations: either return JSON acknowledged state or explicitly document bodyless success so a future client can decode it appropriately. Preserve legacy JSON success expectations in v1.
- Define private reaction absence separately from failure and deletion acknowledgement. Favoriting/event retries and reaction mutations need deduplication/idempotency semantics; conflicts must provide enough current state/version information to reconcile.
- Public catalog access, guest behavior and personalized fields must be explicit. A local `LogIn` flag, `facebook_id`, `user_id`, or anonymous UUID alone must not authorize private account operations. Do not auto-merge anonymous feedback or historical unscoped Library data.
- Document which profile fields are returned from login/register/account validation. Existing reset-request functionality needs safe consistent acknowledgement without leaking private account details.

A future Swift client still must add typed results, cancellation, loading/error UI, safe logging and credential storage. Server contracts make those possible; the API cannot fix silent casts or controller races by itself. No such refactor is included in this audit.

## 7. Pagination, Caching, and Performance Requirements

### Current request and payload costs

| Flow | Current first-party request cost | Avoidable work |
| --- | --- | --- |
| Explore initial/refresh | 1; section expansion adds 0 | Downloads initially hidden entries and detail metadata for all parsed stories |
| Search landing initial/refresh | 2 in parallel | Full catalog to compute writer grouping/counts |
| Submitted text query | 1 full-catalog GET every submission | Repeated transfer plus local title/author scan |
| Category/time results | 1 | No consumed continuation/total; detail fields carried in every row |
| Writer directory/results | 0 additional after landing | Zero-call navigation depends on predownloading the whole catalog |
| Detail opening then cold Read | 3 total | Catalog once for related stories, again for title-to-ID, then text |
| Direct cold Read from Explore | 2 additional after feed | Catalog lookup plus text instead of direct content by ID |
| Cold save through Explore/Search/detail | Up to 4 additional after originating screen load | Catalog lookup, text, profile-gated event, Explore refresh |
| Open saved Library story | 0 content/list calls if text is present | Artwork may still need networking; missing text falls back to detail |
| Successful removal through Library | Up to 3 with active Explore observer/profile | Immediate feed refresh + DELETE + success feed refresh |

An Explore → detail → cold Read journey uses four API requests including the initial feed, before images/ads. These are call-count findings, not latency or byte-size measurements. Global full-catalog and image request coalescing are absent. The Search landing guards its own refresh; Explore does not, and separate helpers do not share results. A pending Search save guards duplicates within its saver; that does not deduplicate text loading across all controllers.

### Required list/pagination behavior

- Bound v2 list payloads and document continuation token/page behavior, default/maximum size, deterministic sort/order, end-of-list state and filter binding. Choose the mechanism in the backend project; the client needs reliable traversal, not a particular pagination algorithm.
- Include total/count metadata where the UI actually uses it: author story counts and section expansion benefit from counts. A truncated story page must not be mistaken for the complete author catalog.
- Avoid duplicate story identities across pages/categories and document multi-category membership. Preserve existing editorial ordering and explain any deliberately different search ordering. Empty valid results must be distinguishable from request failure.
- A future paginated client must be introduced before relying on continuation. **Do not paginate or wrap current v1 arrays in place**: old clients would read an incomplete catalog or fail decoding.

### Required cache/media behavior

- Document cache lifetime/validators for categories, editorial collections, author summaries, story metadata, body revisions and images. ETag/Last-Modified or equivalent revalidation information should let the future client avoid identical transfers. State which resources are public versus personalized/private so account state is not reused across users.
- Return stable story/text/artwork revision information. If a content hash is supplied, define encoding and normalization: the current client hashes the exact original text's UTF-8 bytes. Do not silently equate a differently normalized server hash with that value.
- Smaller list projections must still include identity and essential row fields. Fetch body and long biography only when needed; an optional discovery/detail bundle may reduce round trips without mandating backend internals.
- Provide image variants/HTTPS/cache-friendly URLs and explicit expiry if applicable. Saved text currently persists offline, but the app has no explicit durable artwork-download layer. A backend response alone will not implement one.
- Define content-unavailable/replaced outcomes without requiring the app to delete previously saved copies or reset bookmarks. Actual policy for retained offline copies must be confirmed by the owner/backend.

Current helpers retain previous parsed responses only in memory within their own instances. No first-party disk catalog cache, explicit conditional GET logic, shared cache policy, custom retry/backoff, background text download, or response-size instrumentation was found. Default Alamofire/URLSession caching is not proof of a server cache guarantee. Local audio cache files are a separate concern and are not network downloads.

## 8. Backward Compatibility Requirements

**API v1 must continue working unchanged for legacy installed QuickReads app versions.** Users update at different times; a new app/API contract does not retire older consumers.

1. The eventual `/api/v1/...` namespace can expose the legacy contract, but **existing hardcoded `/quickreads/...` and applicable `/shortstories_db/...` URLs must also remain functional**. Adding only new v1 paths or redirecting old clients to a changed v2 response is insufficient. Backend routing/alias mechanism is an implementation decision; observable compatibility is the requirement.
2. Preserve legacy methods, parameter names and encodings, login path-segment escaping/credential expectations, and response top-level shapes. Preserve String metadata/account IDs, Bool `favorited`, category/Explore structure, text-row semantics, successful JSON bodies and legacy filtering behavior where older clients depend on them. Do not assume even additive fields are safe across every old client without checking.
3. Maintain the numerical-rating write/average-read endpoints for old clients, independently of the new private reaction feature. Do not repurpose numeric score fields, infer reactions from historical scores, or delete historical feedback. Verify any other old-version consumers in the backend; this snapshot is not the complete historical endpoint inventory.
4. New clients can adopt `/api/v2/...` only after its identity/authentication/response contracts are verified. Do not force old apps to send tokens they cannot obtain or new parameters they do not know. v2 must not be a prerequisite for v1 discovery/Read/login/saving analytics.
5. Preserve authoritative legacy-account aliases and story mappings. Catalog IDs, titles, title/author hashes and image filenames are different keys. Renames and ambiguous duplicates need explicit resolution; they cannot be repaired by guessing or discarding local state.
6. Local saved stories, original text, reading/speech bookmarks, completion, archived feedback, reactions and pending tombstones must survive the eventual client migration. Historical unscoped Library data must not be assigned to the current account automatically. Preserve guest/account reaction isolation and do not purge data on logout.
7. Bundle identity, legacy keychain access group and StoreKit product IDs remain compatibility contracts; an API rename does not justify changing them. No Core Data entity/hash/store rename or reset is required by this document.
8. Maintain v1 while v2 is staged/rolled back, and verify representative old clients against preserved routes before rollout. Retirement requires a separate owner-approved decision based on actual old-client usage; no sunset date or destructive cleanup is proposed here.

## 9. Migration Risks

| Risk and evidence | Why it matters before v2 adoption |
| --- | --- |
| Hardcoded host/paths distributed through controllers/helpers | There is no single switch point. Auth, catalog, content, categories, analytics and inactive PHP routes can accidentally be moved inconsistently. |
| Hardcoded JSON keys and divergent parsers (`DataCell:81`, `GetExplore:51`, `GetSearch:40`) | Numeric duration/cost or nullable detail fields are reasonable in v2 but break strict v1 casts; permissive parsers can hide wrong shapes instead of reporting an error. |
| Parsed catalog IDs discarded; title-to-ID lookup chooses first matching title (`StoryDetailVC:462`) | Duplicate titles, renamed titles or pagination can select the wrong body or make it unreachable. Server IDs already anticipated by raw lookup must be carried explicitly in future DTOs. |
| Artwork filename used for Explore callback identity (`ExploreVC:275`) | Shared/revised image URLs can update the wrong card or miss an update. |
| Several incompatible local story keys | Library/reading/audio use title; writers use normalized author/title; reactions use hashed normalized title/author. A single naive string replacement cannot migrate all of them safely. |
| Different author equality/order rules | Related stories use exact author equality; writers normalize; generic search is case-insensitive; catalog groups preserve category order. A new canonical author contract can change visible results unless checked. |
| Credential-in-path login and unverified subsequent authentication | Must preserve v1 compatibility while eliminating the pattern in v2. No evidence proves the backend trusts IDs alone, but no explicit subsequent credentials are supplied by these calls. Backend ownership/session checks need inspection. |
| Raw query interpolation in Explore/category/time URLs | Reserved characters in IDs or future value formats may alter requests. Future request builders must encode fields; v1 encoded URL assumptions require verification. |
| Implicitly unwrapped controller metadata and force unwraps | `StoryDetailVC`/`BookVC` pass String! fields into nonoptional save methods; null/missing v2 metadata could become a crash without adapter changes. Registration/reset inputs and the uncalled `DataUser` URL/data conversions also use forced unwraps. Current active server dictionaries mostly use guards rather than direct forced unwraps; do not mistake older warnings for present unguarded JSON indexing. |
| Silent defaults/skipped rows/stale-data fallback | A malformed response can appear successful/empty/stale. Current tests explicitly allow malformed catalog rows to be skipped and failed list calls to retain old data. New errors must be exposed without dropping usable cached results. |
| Text array chooses last String row (`StoryDetailVC:472`) | Current behavior depends on undocumented row multiplicity/order. Converting to one body needs verified semantics and identity/revision checking. |
| Full catalog shared semantically across writers/search/related/text lookup | Changing completeness, category duplication, sort order or availability affects several screens simultaneously. v1 pagination would be particularly dangerous. |
| Time boundary/sentinel and label assumptions | Main ranges differ from the More picker. Backend inclusive/exclusive behavior and interpretation of `time` are not established. Preserve legacy behavior while specifying v2. |
| Server favorite versus local offline state | Search ignores server flag for actual saved UI; Explore combines it with local state. Reader removal omits the remote DELETE. v2 must clarify meaning before introducing account reconciliation. |
| Save/remove already committed before network acknowledgement | An event failure must not undo valid local data; blind retries may duplicate events unless semantics/idempotency are agreed. Duplicate Explore refreshes also amplify traffic. |
| Concurrent requests and inconsistent completion outcomes | Independent catalog reads and uncancelled refresh/search/image tasks can overlap. A new response envelope alone does not prevent stale UI updates or wasted transfers. |
| Content changes and original/clean UTF-16 mismatch | Hashes, offsets, paragraph boundaries, audio cache identity and restoration depend on exact source text. A cleaned body cannot reuse old original offsets without explicit mapping. |
| Local reaction seam mistaken for working sync | No transport/session/version reconciliation is deployed. Mutation IDs and account/story mapping must be verified before queued account records or deletion tombstones are sent. |
| Broad ATS exception and returned media redirects | Tightening transport while moving APIs may break legacy artwork hosts. Actual URLs/TLS/redirects need separate verification; do not infer that every returned asset is HTTPS. |
| Compiled uncalled helper versus entirely commented code | `DataUser` is real code without callers; `DataImage`/`StoreCell` are comments. Neither is justification to revive a product flow, but deployed older clients may still depend on related routes. |
| Unknown production/server behavior | Synthetic fixtures establish parser tests, not server schema, authorization, response extras, data uniqueness or performance. Existing proposal documents must not be mistaken for deployed capabilities. |

These are risks to plan around, not code changes made by this audit. Native tests cover parser refresh/order, stale-result callbacks, malformed-row skipping, local saving, routes, account-scoped reactions and UTF-16 restoration; they are not live v1/v2 integration tests. Representative sanitized real response fixtures and both-version contract checks are needed before a separate migration.

## 10. Recommended API v2 Requirements

### Critical

1. Preserve unchanged v1 behavior and existing production URL access for installed legacy apps, including historical numerical ratings. Deploy v2 alongside them.
2. Establish body-based HTTPS authentication, verified account identity/session lifecycle, ownership checks and authoritative legacy-account aliases.
3. Return immutable consistently typed story IDs in every relevant list/detail/content resource; resolve legacy title/title-author identities safely before client migration.
4. Provide direct single-story content access with matching identity, declared format/language and revision, preserving Unicode/paragraphs and offline readability.
5. Document types/nullability, success bodies, meaningful status codes, structured errors, absence states and existing category/time semantics. Prevent ambiguous data from becoming silent empty results.

### Important

1. Supply story detail with author metadata and related summaries, avoiding complete-catalog reads and preserving information after smaller list DTOs are adopted.
2. Provide compact writer summaries with real distinct-story counts, author-ID browsing, and bounded title/author/category/time results with documented ordering/pagination.
3. Give Explore stable section identity/role and count/continuation metadata; allow compact Search discovery data without a whole-catalog download.
4. Clarify favorites versus analytics versus local offline state; acknowledge idempotent save/remove operations/events without requiring whole-feed refresh.
5. Provide content/list/media validators and freshness/privacy rules, HTTPS artwork with missing-asset semantics, and thumbnail/cover variants where available.
6. If enabling the existing private-reaction sync seam, provide authenticated scoped fetch/upsert/delete, mutation acknowledgements/conflict/deletion semantics and verified legacy identity mappings. This is conditional on commissioned sync and completed prerequisites, not a claim of current remote functionality.

### Nice to have

1. Canonical story share URLs for existing sharing, once destination/app-link behavior is agreed.
2. Optional category display descriptions/canonical editorial collection metadata beyond the essential IDs/names, reducing English name inference while leaving UIKit styling local.
3. Optional bundles/projections for discovery/detail that further reduce transfer and round trips, without forcing one endpoint topology.
4. A remote audio-provider contract only if commissioned using the existing provider seams. Local speech must remain viable; no server voice-generation implementation is required here.

Remote progress/Library synchronization and new account-editing/payment/social features are intentionally not prioritized: current code does not establish them as required v2 work.

## 11. Questions for the Backend

1. Which routes are deployed, and which app versions still call each current `/quickreads/...`, `/shortstories_db/...`, ratings or other historical route? Are there consumers absent from this repository snapshot? How will original URLs remain compatible when `/api/v1/...` is exposed?
2. Does login use HTTP Basic challenges, cookies or another server session, and are subsequent ID-bearing calls authenticated/authorized? Is `facebook_id` an immutable account ID, an external identity, or a legacy alias? Can authoritative aliases support new credentials without reassignment?
3. What are the actual sanitized response shapes/types/extra fields and error status/bodies for every endpoint? In particular: numeric versus String IDs, Bool versus integer/String `favorited`, null summaries/biographies/dates, and success JSON after DELETE. Which returned fields are unused by this client but needed by older clients?
4. Are story IDs immutable and unique across catalog/category/Explore/text results? Can duplicate titles or identical title/author editions exist? Are there historical aliases for renamed stories/authors and enough information to resolve local title keys and `local-v1` reaction hashes without guessing?
5. Is `/stories` guaranteed complete? Can a story appear once per category? What determines catalog order, Explore section order/selection, category order and related-story order? Does the personalized catalog differ from the text lookup's unpersonalized catalog?
6. What does `favorited` mean: current account saved list, purchase/event history, or another flag? How does POST/DELETE purchase-record affect it? Are requests idempotent/events/state mutations? How are account switches, anonymous calls, offline failures and repeated saves currently handled?
7. Why is `/stories/text` modeled as an array, and can it contain multiple rows? Is taking the last text correct, and which identity/edition/revision does it represent? Does text contain HTML/entities or only plain text? What offline retention/access policy applies to removed or revised stories?
8. What precisely do `time`, `dates`, `life` and `cost` represent? Are reading-time bounds inclusive/exclusive, how are estimates rounded, and what does the More picker's adjacent-band request mean? Is cost still meaningful to any active or older client?
9. Are author/category IDs or descriptions already available but omitted from current responses? Can author counts refer to the same distinct available story set shown by browsing, without counting duplicate category memberships or inaccessible entries?
10. What cache/compression headers, validators, response limits and media URL lifetimes/redirects exist? Are all image destinations HTTPS? Are small/large image variants available? No actual payload-byte or latency measurements were obtained by this audit.
11. Are private reactions part of commissioned v2 scope? If so, what authenticated identity mapping, timestamp format, acknowledgement/version/conflict and deletion semantics will the client implement? No reaction endpoint in an existing proposal should be assumed deployed.
12. Do the uncalled profile PHP endpoints have active legacy consumers? What records and access controls does `get_users_db.php` actually expose? Should any own-profile media feature be included in a later client scope, or simply kept compatible for old versions?
13. Are StoreKit receipts, coin balances, ads, push registration or account profile updates handled by anything outside this Swift snapshot? Their absence from first-party networking is not proof that no external integration exists; none is required by the current observed client flows.
14. Can the backend provide sanitized representative responses and a nonproduction verification environment for old/new contract checks, including missing content, malformed/nullable metadata, duplicate identities, failed login/reset, rejected private access, repeat mutations and time boundaries?

Audit deliverable: this document only. No application networking, endpoints, dependency versions, persisted models, API behavior or production app behavior was changed. Production contracts remain unverified until checked in the separate backend project.
