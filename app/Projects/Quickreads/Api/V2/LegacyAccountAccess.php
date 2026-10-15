<?php

namespace App\Projects\Quickreads\Api\V2;

use Illuminate\Support\Facades\{DB, Schema};

// Installed legacy clients cannot authenticate Apple-only identities. Preserve
// their original account data/response shapes without exposing new Apple users
// through the old public directory or ID-only favorite-history operation.
class LegacyAccountAccess
{
    public static function excludeApple($query)
    {
        if (Schema::connection('quickreads')->hasTable('apple_identities')) {
            $query->whereNotExists(fn ($subquery) => $subquery->selectRaw('1')->from('apple_identities as apple')
                ->whereColumn('apple.user_id', 'users.id'));
        }
        return $query;
    }

    public static function isAppleAccount($userId): bool
    {
        return $userId && Schema::connection('quickreads')->hasTable('apple_identities') &&
            DB::connection('quickreads')->table('apple_identities')->where('user_id', $userId)->exists();
    }
}
