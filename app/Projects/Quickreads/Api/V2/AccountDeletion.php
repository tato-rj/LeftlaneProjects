<?php

namespace App\Projects\Quickreads\Api\V2;

use Illuminate\Support\Facades\{Crypt, DB};

class AccountDeletion
{
    public function delete(int $userId, AppleProvider $provider, int $missingStatus = 401): void
    {
        $db = DB::connection('quickreads');
        $db->transaction(function () use ($db, $userId, $provider, $missingStatus) {
            $identities = $db->table('apple_identities')->where('user_id', $userId)->lockForUpdate()->get();
            $user = User::whereKey($userId)->lockForUpdate()->first();
            abort_unless($user, $missingStatus);
            foreach ($identities as $identity) {
                if ($identity->refresh_token) $provider->revoke(Crypt::decryptString($identity->refresh_token));
            }
            $reader = \App\Http\Controllers\Api\V2\StoryActivityController::readerKey('account', (string) $user->id);
            $db->table('story_activity')->where('reader_key', $reader)->delete();
            $db->table('story_activity_mutations')->where('reader_key', $reader)->delete();
            $tokenIds = $user->tokens()->pluck('id');
            $db->table('apple_auth_challenges')->whereIn('session_token_id', $tokenIds)->delete();
            foreach ($identities as $identity) {
                // A short security tombstone prevents an authorization issued
                // before deletion from recreating the deleted account.
                $db->table('apple_notifications')->insert(['event_id' => hash('sha256', random_bytes(32)),
                    'scope' => $identity->scope, 'subject_hash' => $identity->subject_hash,
                    'type' => 'account-deleted', 'event_at' => now()->utc(), 'created_at' => now()->utc()]);
            }
            $user->tokens()->delete();
            $db->table('apple_identities')->where('user_id', $user->id)->delete();
            foreach (['user_purchase_records', 'ratings', 'comments'] as $table) $db->table($table)->where('user_id', $user->id)->delete();
            $db->table('password_resets')->where('email', $user->email)->delete();
            $db->table('subscriptions')->where('email', $user->email)->delete();
            $user->delete();
        });
    }
}
