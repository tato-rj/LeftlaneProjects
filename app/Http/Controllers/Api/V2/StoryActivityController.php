<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Middleware\Api\V2\Authenticate;
use App\Projects\Quickreads\Api\V2\{ApiException, Responses, StrictInput, User};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoryActivityController extends Controller
{
    public static function readerKey(string $kind, string $id): string
    {
        return hash_hmac('sha256', $kind."\0".$id, config('app.key'));
    }

    public function store(Request $request)
    {
        $input = StrictInput::read($request, [
            'story_id' => ['required', 'string', 'regex:/^[1-9][0-9]{0,9}$/D'],
            'mutation_id' => 'required|string|uuid', 'guest_id' => 'nullable|string|uuid',
            'reading_status' => 'present|nullable|string|in:not_started,reading,finished',
            'saved' => 'present|nullable',
            'observed_at' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,3})?(?:Z|[+-]\d{2}:\d{2})$/D', 'date'],
        ]);
        if (($input['saved'] !== null && !is_bool($input['saved'])) ||
            ($input['saved'] === null && $input['reading_status'] === null)) {
            throw ValidationException::withMessages(['saved' => ['Use a Boolean or null; at least one state must be known.']]);
        }
        $observed = Carbon::parse($input['observed_at'])->utc();
        if ($observed->gt(now()->utc()->addMinutes(5))) {
            throw ValidationException::withMessages(['observed_at' => ['The observation is more than five minutes in the future.']]);
        }
        $account = null;
        // A supplied invalid/expired credential must never fall back to guest.
        if ($request->headers->has('Authorization')) {
            (new Authenticate())->handle($request, fn () => null);
            $account = $request->user();
            if (($input['guest_id'] ?? null) !== null) {
                throw ValidationException::withMessages(['guest_id' => ['Do not send a guest ID with account credentials.']]);
            }
        } elseif (empty($input['guest_id'])) {
            throw ValidationException::withMessages(['guest_id' => ['A reporting UUID is required for a guest.']]);
        }
        if (!$account && $input['saved'] === true) abort(403);
        $kind = $account ? 'account' : 'guest';
        $guest = $account ? null : strtolower($input['guest_id']);
        $readerKey = self::readerKey($kind, $account ? (string) $account->id : $guest);
        $mutation = strtolower($input['mutation_id']);
        $timestamp = $observed->format('Y-m-d H:i:s.v');
        $hash = hash('sha256', json_encode([$input['story_id'], $input['reading_status'], $input['saved'], $timestamp], JSON_THROW_ON_ERROR));
        $db = DB::connection('quickreads');
        $applied = $db->transaction(function () use ($db, $account, $kind, $readerKey, $mutation, $hash, $input, $observed, $timestamp) {
            if ($account) abort_unless(User::whereKey($account->id)->lockForUpdate()->exists(), 401);
            $receipts = $db->table('story_activity_mutations');
            $inserted = $receipts->insertOrIgnore(['reader_key' => $readerKey, 'mutation_id' => $mutation,
                'payload_hash' => $hash, 'applied' => false, 'created_at' => now()->utc()]);
            $receipt = (clone $receipts)->where('reader_key', $readerKey)->where('mutation_id', $mutation)->lockForUpdate()->first();
            if (!hash_equals($receipt->payload_hash, $hash)) throw new ApiException(409, 'This mutation ID was used with a different observation.');
            if (!$inserted) return (bool) $receipt->applied;
            abort_unless($db->table('stories')->where('id', $input['story_id'])->exists(), 404);
            // Offline observations older than retention are acknowledged but do
            // not recreate expired state. Keep only current state, not events.
            if ($observed->lt(now()->utc()->subDays(config('quickreads_v2.activity_retention_days')))) return false;
            $states = $db->table('story_activity');
            $states->insertOrIgnore(['reader_key' => $readerKey, 'reader_kind' => $kind,
                'user_id' => $account?->id, 'story_id' => $input['story_id'], 'created_at' => now()->utc(), 'updated_at' => now()->utc()]);
            $state = (clone $states)->where('reader_key', $readerKey)->where('story_id', $input['story_id'])->lockForUpdate()->first();
            $changes = [];
            foreach (['reading_status' => 'reading', 'saved' => 'saved'] as $field => $prefix) {
                if ($input[$field] === null) continue;
                $previous = $state->{$prefix.'_observed_at'};
                $comparison = $previous === null ? 1 : $observed->getTimestampMs() <=> Carbon::parse($previous, 'UTC')->getTimestampMs();
                if ($comparison > 0 || ($comparison === 0 && strcmp($mutation, $state->{$prefix.'_mutation_id'}) > 0)) {
                    $changes[$field] = $input[$field];
                    $changes[$prefix.'_observed_at'] = $timestamp;
                    $changes[$prefix.'_mutation_id'] = $mutation;
                }
            }
            if ($changes) {
                (clone $states)->where('id', $state->id)->update($changes + ['updated_at' => now()->utc()]);
                (clone $receipts)->where('id', $receipt->id)->update(['applied' => true]);
            }
            return (bool) $changes;
        }, 3);
        return Responses::json($request, ['data' => ['mutation_id' => $mutation,
            'story_id' => $input['story_id'], 'reader' => ['kind' => $kind,
                'account_id' => $account ? (string) $account->id : null, 'guest_id' => $guest],
            'accepted' => true, 'applied' => $applied]], false);
    }
}
