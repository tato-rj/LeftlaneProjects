<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Projects\Quickreads\Api\V2\{ApiException, AppleProvider, Responses, StrictInput};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppleLifecycleController extends Controller
{
    public function notification(Request $request, AppleProvider $provider)
    {
        $input = StrictInput::read($request, ['payload' => 'required|string|max:16384']);
        // Disabling new sign-ins must not disable provider revocation events.
        if (!config('quickreads_v2.apple.team_id')) {
            throw new ApiException(503, 'Apple verification is temporarily unavailable.');
        }
        $claims = $provider->verify($input['payload'], null, true);
        $events = $claims['events'] ?? null;
        if (is_string($events)) {
            try { $events = json_decode($events, false, 16, JSON_THROW_ON_ERROR); }
            catch (\JsonException $e) { abort(401); }
        }
        abort_unless(is_object($events) && is_string($events->sub ?? null) && $events->sub !== '' &&
            strlen($events->sub) <= 255 && is_int($events->event_time ?? null) &&
            $events->event_time <= now()->utc()->addSeconds(60)->timestamp &&
            in_array($events->type ?? null, ['email-enabled', 'email-disabled', 'consent-revoked', 'account-deleted'], true) &&
            is_string($claims['jti'] ?? null) && strlen($claims['jti']) > 0 && strlen($claims['jti']) <= 255, 401);
        $db = DB::connection('quickreads');
        $db->transaction(function () use ($db, $provider, $events, $claims) {
            $at = Carbon::createFromTimestampUTC($events->event_time);
            $scope = $provider->scope();
            $subject = hash('sha256', $events->sub);
            if (!$db->table('apple_notifications')->insertOrIgnore(['event_id' => hash('sha256', $scope."\0".$claims['jti']),
                'scope' => $scope, 'subject_hash' => $subject, 'type' => $events->type,
                'event_at' => $at, 'created_at' => now()->utc()])) return;
            $query = $db->table('apple_identities')->where('scope', $scope)->where('subject_hash', $subject);
            $identity = (clone $query)->lockForUpdate()->first();
            if (!$identity) return;
            if (in_array($events->type, ['consent-revoked', 'account-deleted'])) {
                $db->table('api_v2_tokens')->where('apple_identity_id', $identity->id)
                    ->where('apple_authorized_at', '<=', $at)->delete();
                if (Carbon::parse($identity->authorized_at, 'UTC')->lte($at)) {
                    $query->update(['revoked_at' => $at, 'refresh_token' => null, 'last_event_at' => $at, 'updated_at' => now()->utc()]);
                }
            } else {
                if ($identity->last_event_at && Carbon::parse($identity->last_event_at, 'UTC')->gt($at)) return;
                $query->update(['forwarding_enabled' => $events->type === 'email-enabled', 'last_event_at' => $at, 'updated_at' => now()->utc()]);
            }
        }, 3);
        return Responses::json($request, ['data' => ['accepted' => true]], false);
    }

    public function deleteAccount(Request $request, AppleProvider $provider)
    {
        $input = StrictInput::read($request, ['delete_account' => 'required']);
        if ($input['delete_account'] !== true) {
            throw ValidationException::withMessages(['delete_account' => ['Explicit confirmation must be true.']]);
        }
        if ($request->user()->currentAccessToken()->created_at->lt(now()->utc()->subMinutes(10))) {
            throw new ApiException(401, 'Sign in again before deleting your account.');
        }
        app(\App\Projects\Quickreads\Api\V2\AccountDeletion::class)->delete($request->user()->id, $provider);
        return Responses::json($request, ['data' => ['deleted' => true]], false);
    }
}
