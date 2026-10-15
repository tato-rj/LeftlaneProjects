# QuickReads — Sign in with Apple backend handoff

Prepared October 14, 2026. Copy this document into the Backend project as the
implementation brief for enabling **Continue with Apple** in the iOS app.

## Status and scope

The owner now wants Apple login implemented. The native Apple buttons already
exist in the account welcome screen and guest My Library screen, but have no
action. The current app uses email/password authentication and stores a verified
QuickReads API v2 bearer session in Keychain.

The specification refreshed from
<https://leftlaneapps.com/docs/openapi.yaml> on this date contains 15 v2 paths,
including email login/register/logout, but no Apple authentication operation.
The changes below are **proposed requirements, not deployed endpoints**. The
Backend project owns the authoritative specification and all server changes.
Do not manually edit the downloaded `docs/openapi.yaml` in the Swift repository.
Implement and test the server behavior, update the Backend's specification, and
publish both before connecting the production iOS buttons.

Keep existing email login, registration, password reset, bearer sessions, public
browsing and favorites working. Preserve production legacy routes for installed
app versions. This feature does not authorize a new API migration, automatic
Library upload, or changes to reading progress, guest access or subscriptions.

## Intended behavior

- An explicit tap opens Apple's native authorization UI. Opening an account
  screen or launching the app must not start Apple authorization.
- A first-time Apple user can create a free QuickReads account; a returning Apple
  user signs into the same canonical account, even when Apple omits their name.
- Both outcomes return the existing QuickReads session format. Apple's token is
  evidence for the server, never the bearer credential for QuickReads endpoints.
- Apple cancellation leaves the reader a guest, consumes no free story and
  resumes no pending Read/save action. Network or verification failures are
  retryable and establish no local authenticated session.
- After the app receives and persists a verified QuickReads session, its existing
  registration coordinator dismisses the account flow and resumes the single
  pending action. Apple login must not automatically bookmark a story.

## Proposed HTTP contract

Use the existing origin, `/api/v2` prefix, JSON snake_case naming and `data`/`error`
envelopes. The two paths below are recommendations for the Backend to adopt or
replace with explicitly documented equivalents. The iOS implementation will use
the final published contract rather than infer a route.

### 1. Begin an Apple authentication attempt

**Proposed:** `POST /api/v2/auth/apple/challenge`, public (`security: []`).

Request:

```json
{
  "attempt_id": "00000000-0000-4000-8000-000000000001"
}
```

`attempt_id` is a fresh client-generated UUID for this sign-in attempt. It is not
a device identifier, guest identity or account ID.

Successful `200` response:

```json
{
  "data": {
    "attempt_id": "00000000-0000-4000-8000-000000000001",
    "challenge_id": "example-opaque-challenge-id",
    "nonce": "k9Fq64y7w_Cs3r8N6MByV2pQYjHhZ0uAPd5x1bERtOg",
    "expires_at": "2026-10-14T20:05:00Z"
  }
}
```

All example identifiers, nonces and tokens in this document are illustrative.
Never hardcode them as production values.

Requirements:

- Generate an unpredictable opaque challenge ID and a nonce with at least
  256 bits of cryptographic randomness. Return the nonce as unpadded base64url.
- Bind the challenge to its attempt, the permitted native Apple client ID and
  its expiration. Recommended lifetime: five minutes; publish the final value.
- A repeat of the same challenge request while valid returns the same challenge,
  rather than creating conflicting attempts. A new authorization attempt uses
  a new UUID. Rate-limit challenge creation without requiring an account.
- For this proposed protocol, iOS passes the returned `nonce` **unchanged** to
  `ASAuthorizationAppleIDRequest.nonce`. The server expects that exact string in
  the verified identity token. Do not hash it on just one side. If a different
  encoding/hashing protocol is chosen, specify the exact bytes and transformation
  in OpenAPI and the Swift migration notes.
- iOS also sets request `state` to `attempt_id` and checks the returned state
  before submitting credentials. State and the server nonce have separate roles.

The server-issued challenge is a proposed QuickReads replay defense. Apple's
native [nonce property](https://developer.apple.com/documentation/authenticationservices/asauthorizationopenidrequest/nonce)
provides the value carried by the authorization request; Apple does not define
the QuickReads challenge endpoint or its lifetime.

### 2. Exchange verified Apple credentials for a QuickReads session

**Proposed:** `POST /api/v2/auth/apple`, public (`security: []`).

```json
{
  "attempt_id": "00000000-0000-4000-8000-000000000001",
  "challenge_id": "example-opaque-challenge-id",
  "identity_token": "EXAMPLE_APPLE_IDENTITY_JWT",
  "authorization_code": "EXAMPLE_SINGLE_USE_APPLE_CODE",
  "profile": {
    "first_name": "Example",
    "last_name": "Reader"
  },
  "locale": "en-US"
}
```

| Field | JSON type | Requirement |
| --- | --- | --- |
| `attempt_id` | string, UUID | Required; must match the issued challenge. |
| `challenge_id` | opaque string | Required; do not use the decimal database `Id` schema. |
| `identity_token` | nonempty string | Required; UTF-8 Apple identity JWT. |
| `authorization_code` | nonempty string | Required; UTF-8 single-use Apple authorization code. |
| `profile` | object or null | Optional; display metadata Apple may provide only once. |
| `profile.first_name` | string or null | Optional; accept absence for returning users. |
| `profile.last_name` | string or null | Optional; accept absence for returning users. |
| `locale` | string or null | Optional; use the existing account locale convention. |

Do not require an email/password or an Apple user ID supplied in the request.
Derive the provider identity and email from verified Apple claims. Client profile
names are editable display metadata, not proof of identity. Put reasonable,
documented size limits on credential strings and profile fields.

Return **`200` with the existing `SessionResponse`** for both new and returning
accounts:

```json
{
  "data": {
    "user": {
      "id": "101",
      "legacy_account_id": null,
      "email": "example@privaterelay.appleid.com",
      "first_name": "Example",
      "last_name": "Reader",
      "locale": "en-US"
    },
    "access_token": "EXAMPLE_QUICKREADS_BEARER_TOKEN",
    "token_type": "Bearer",
    "expires_at": "2026-11-13T20:00:00Z"
  }
}
```

Retain the published `Account` types: canonical `id` is a positive decimal string;
`email`, `first_name` and `last_name` are strings; `legacy_account_id` and `locale`
are required keys whose values may be null. Do not substitute Apple's `sub` for
the canonical ID, invent a legacy alias, or change required account fields to
nullable without a separately coordinated client update.

Use the existing QuickReads opaque bearer issuer, expiry rules and session
revocation behavior. The current contract specifies a default 30-day lifetime
with reauthentication on expiry; this feature does not introduce an iOS-facing
refresh-token API. Do not return Apple's refresh token or private client secret
in the response.

## Server verification and retry behavior

No QuickReads account access may be granted from a decoded-but-unverified JWT,
a submitted email, a device-provided Apple identifier or a local login flag.

Before issuing a session:

1. Validate the challenge, attempt binding and expiration. Enforce single-use
   completion with the narrowly defined retry exception below.
2. Verify the identity JWT using Apple's trusted public keys and an approved
   signature algorithm. Reject unsigned/algorithm-substituted tokens. Handle key
   rotation through Apple's trusted key source, not token-supplied URLs.
3. Validate issuer `https://appleid.apple.com`, the configured native audience,
   expiry and nonce against the server's challenge. Reject absent/mismatched
   values. Apply a documented, small clock-skew allowance.
4. Validate the authorization code server-to-server with Apple, using the correct
   native client ID and server-generated client secret. Verify the returned
   identity token too; its verified subject/audience must agree with this attempt.
5. Only then resolve/create the canonical account and issue its QuickReads
   session. Concurrent exchanges must not create duplicate provider identities.

See Apple's [user verification](https://developer.apple.com/documentation/signinwithapple/verifying-a-user)
and [token validation](https://developer.apple.com/documentation/SigninwithAppleRESTAPI/Generate-and-validate-tokens)
documentation. The developer client-secret signing key and Apple's identity-token
verification keys serve different purposes; do not confuse their algorithms.

Define lost-response recovery in the final contract. Recommended behavior:
`attempt_id` identifies an idempotent exchange. An exact retry with the same
challenge and credential/profile payload can recover the original successful
session during a documented short window. It must not redeem the single-use
Apple code again, create another account or issue another session. Bind recovery
to the original payload; conflicting reuse returns `409`. A consumed challenge
with different credentials, or a different attempt, is rejected as a replay.
Protect any retained recovery response as credential material and expire it.

A rejected attempt returns no session. An unresolved Apple timeout must not
silently create an account; document whether that attempt is retryable or the app
must obtain a fresh challenge and authorization. A failure after Apple code
redemption also needs explicit recovery semantics. Do not promise unrestricted
retries of a redeemed code.

## Account identity, names and hidden email

Map the verified Apple subject within its configured app/team identity scope to
one existing canonical QuickReads account. Never look up the account by an
untrusted request field. A repeated login returns the same account ID, preserving
favorites and account-scoped app data.

Apple can omit name details after the first consent. Do not require profile
names on every login or overwrite existing names with absent/null/empty values.
On account creation, store supplied names as sanitized display metadata; if no
name is available, the existing schema can return empty strings. The later iOS
implementation must protect first-consent name metadata locally while its initial
exchange is pending, so a network failure does not lose it. These details follow
Apple's [authentication guidance](https://developer.apple.com/documentation/signinwithapple/authenticating-users-with-sign-in-with-apple).

Accept a verified Apple private-relay address as an account email. Do not demand
the real address or reject the relay domain. Existing provider mappings can sign
in even if a later credential lacks email; use their stored verified account
email. If a new identity genuinely supplies no usable verified email, the current
non-null `Account.email` contract needs an explicit verified-email collection
flow or a documented failure. Do not invent an email to satisfy decoding.

**Do not automatically merge with an existing email account solely because email
addresses match.** Safe initial behavior is a `409 conflict` instructing the user
to sign in with email. Return no existing account details or bearer token. Do not
attach the Apple identity as a side effect of that conflict.

If account linking is included, it requires a separate, documented flow with
fresh proof of the existing account, verified Apple authorization, explicit user
consent and conflict handling. It must retain the existing canonical account ID.
Never overwrite an email password or silently move favorites/reactions/history.
Publish the linking operation before the iOS app builds a corresponding screen;
the current app has no account-linking UI. Hidden-email accounts cannot be linked
by guessing their real email.

Specify password-reset behavior for Apple-only accounts without disclosing
account existence. Keep the existing generic reset-email response. A password
or another login method must not be added implicitly during Apple sign-in.

## Errors, caching and confidentiality

Reuse the existing v2 `Error` schema and enum values. Keep `fields` an object, not
null or an array; use an empty object for non-validation errors.

| Status | Existing `error.code` | Expected case |
| --- | --- | --- |
| `400` | `malformed_request` | Invalid JSON/request structure. |
| `401` | `unauthenticated` | Invalid Apple proof, expired/mismatched challenge, unauthorized replay. |
| `409` | `conflict` | Conflicting attempt reuse or an account requiring explicit linking. |
| `413` / `415` | `payload_too_large` / `unsupported_media_type` | Oversized request or unsupported content type. |
| `422` | `validation_failed` | Missing/wrong-type fields or documented profile/email requirements. |
| `429` | `rate_limited` | Throttled request; document `Retry-After`. |
| `503` | `unavailable` | Apple verification temporarily unavailable. |
| `500` | `internal_error` | Unexpected server failure. |

For Apple `409` responses, propose an additive `error.reason` string to distinguish
`apple_account_link_required` from `apple_attempt_conflict`. Add that optional
property to the shared `Error` schema (which currently forbids additional
properties), require/document its presence for Apple conflicts, and coordinate
Swift decoding. Existing operations can omit it. For example:

```json
{
  "error": {
    "code": "conflict",
    "reason": "apple_account_link_required",
    "message": "Please sign in with email to continue.",
    "fields": {}
  }
}
```

The app must not parse arbitrary message prose to choose an account-linking
action. An absent or unknown reason gets a safe generic conflict response, never
automatic linking. If the Backend chooses another structured discriminator,
publish its schema and operation-specific behavior before Swift integration.

Both endpoints and all their errors use `Cache-Control: private, no-store`.
Require HTTPS. Public authentication operations need no existing bearer session;
all subsequent account/favorite requests use the returned QuickReads bearer.

Do not log request bodies, identity JWTs, authorization codes, QuickReads bearer
tokens, Apple refresh/access tokens, private keys or client secrets. Redact them
from traces, proxy logs, errors and test output. Use synthetic examples in docs.
Keep Apple credentials in protected server configuration/storage, outside the
Swift app, repository and published OpenAPI file. Backend storage/framework
choices remain the Backend project's responsibility.

## Apple configuration and account lifecycle

The registered native app bundle ID is **`com.leftlaneapps.shortstoriesdot`**.
Keep it, the existing keychain group and installed-app identity unchanged. The
native token audience must match the configured App ID; do not substitute a new
QuickReads-branded bundle ID or blindly use a web Services ID. A web Services ID
and redirect flow are separate if website Apple login is later commissioned.

The owner must enable Sign in with Apple for that App ID, authorize the appropriate
server key and supply the team/key configuration privately. iOS will add the
`com.apple.developer.applesignin` entitlement with `Default`, refresh provisioning
and use `AuthenticationServices`. The current project does not yet include that
entitlement. Do not send the private `.p8` key to the iOS project or place it in
this document.

Configure approved mail senders for private-relay delivery if the backend emails
these accounts. See [Apple configuration and relay setup](https://developer.apple.com/documentation/xcode/configuring-sign-in-with-apple).

Retain Apple's validated refresh token securely on the server for provider
lifecycle checks/revocation; it is separate from the QuickReads bearer session.
Keep ordinary QuickReads logout as session logout, rather than revoking Apple
consent each time. Process authenticated Apple server-to-server notifications
idempotently: consent revocation/account deletion must invalidate affected
Apple-derived QuickReads sessions; email-forwarding changes update delivery
state. Do not erase an independently linked email account merely because mail
forwarding was disabled. See [processing Apple account changes](https://developer.apple.com/documentation/signinwithapple/processing-changes-for-sign-in-with-apple-accounts).

The present v2 contract has no account-deletion operation. Before production
release, publish the supported user-initiated deletion contract and coordinate
its iOS UI; logout is not account deletion. Deletion must revoke applicable Apple
tokens and invalidate QuickReads sessions. See Apple's [account-deletion guidance](https://developer.apple.com/support/offering-account-deletion-in-your-app/)
and [token-revocation technical note](https://developer.apple.com/documentation/technotes/tn3194-handling-account-deletions-and-revoking-tokens-for-sign-in-with-apple).

## Required Backend OpenAPI updates

Update the **Backend project's** authoritative `docs/openapi.yaml` to include:

1. Public challenge/exchange paths, operation IDs and explicit `security: []`.
2. Strict request/response schemas for the fields above, with required keys,
   nullable versus omitted behavior, size limits, UUID/RFC3339 formats and
   examples. Use `nullable: true` for the existing OpenAPI 3.0.3 convention.
3. The current `SessionResponse` reference for successful exchange. Preserve
   `Account`, canonical decimal-string IDs and the existing session requirements.
4. Existing error-envelope references for each supported status, including `503`
   if a reusable response component must be added, plus the proposed optional
   `error.reason` property for typed Apple conflicts. Document nonce
   transformation, challenge TTL, consumption and retry/recovery rules.
5. No-store headers, rate limits and `Retry-After` behavior.
6. Any implemented authenticated linking/deletion operations as distinct,
   explicitly secured contracts. Do not list unimplemented operations as deployed.
7. Native-only client audience/configuration assumptions without private values.

Also update the Backend's `docs/SWIFT_V2_MIGRATION.md` with the final routes,
minimal native request sequence, scopes, exact nonce/state handling, success and
error examples, linking policy, returning-user behavior and required App ID setup.
Publish it beside the specification under <https://leftlaneapps.com/docs/>.

Updating YAML alone does not implement authentication. Before the iOS work
resumes, confirm that the deployed endpoints match the published specification
and provide a safe staging/disposable-account test process privately. The Swift
repository will then run `./scripts/update-api-spec.sh` and integrate the actual
contract into `APIClient`, `APIModels`, `APISession`, `RegistrationSheetVC` and
the two native Apple-button entry points. It will preserve guest choice,
reading preferences, pending-action cancellation and local Library data.

## Acceptance checks for the Backend

- New Apple identity produces one canonical account and a usable QuickReads
  session; `GET /api/v2/me` and favorite operations accept that bearer.
- Repeat authorization returns the same account, including missing name/profile
  details and private-relay email. Existing metadata is retained.
- Forged signature, wrong issuer/audience, expired JWT, absent/wrong nonce,
  expired challenge, mismatched attempt and mismatched Apple code identity all
  fail without issuing a session or binding an account.
- Two concurrent valid exchanges create no duplicate identity/account. Exact
  permitted retries recover the original result; altered/consumed attempts fail.
- Apple timeout, rejected code, failure after code redemption and a lost success
  response follow the documented recovery behavior without accidental accounts.
- Existing email collision follows the published linking policy and cannot take
  over an account. Hidden email never triggers an inferred merge.
- Validation/throttling/availability errors match the v2 envelope and no-store
  headers. Credentials are redacted from all logging layers.
- Logout invalidates the QuickReads session; provider revocation/deletion and
  forwarding changes follow their separate documented lifecycle rules.
- Existing email/reset/favorite flows and legacy production routes still work.
- Published OpenAPI validates, its examples match actual responses, and no key,
  secret, real token or private test-account credential appears in the docs.

The iOS owner will separately validate native authorization/cancellation,
returning-user behavior, Keychain persistence, stale callbacks and pending
Read/save continuation on a correctly provisioned device. Server tests alone do
not establish that the Apple capability and native presentation are configured.
