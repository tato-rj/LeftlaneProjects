<?php

namespace App\Projects\Quickreads\Api\V2;

use App\Http\Controllers\Api\V2\AuthController;

class Sessions
{
    public static function issue(User $user, ?int $appleIdentityId = null, ?int $appleAuthorizedAt = null): array
    {
        $expires = now()->addDays(config('quickreads_v2.token_lifetime_days'));
        $token = $user->createToken('quickreads-ios-v2', ['quickreads:v2'], $expires);
        if ($appleIdentityId !== null) $token->accessToken->forceFill(['apple_identity_id' => $appleIdentityId,
            'apple_authorized_at' => \Carbon\Carbon::createFromTimestampUTC($appleAuthorizedAt)])->save();
        return ['token_id' => $token->accessToken->id, 'response' => ['data' => [
            'user' => AuthController::profile($user), 'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer', 'expires_at' => $expires->utc()->toIso8601String(),
        ]]];
    }
}
