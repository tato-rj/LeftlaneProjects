<?php

namespace App\Http\Middleware\Api\V2;

use App\Projects\Quickreads\Api\V2\{PersonalAccessToken, User};
use Closure;
use Illuminate\Http\Request;

// Use existing Sanctum token hashing/abilities without changing its global model
// or accepting sessions and VideoUploader tokens from unrelated guards/databases.
class Authenticate
{
    public function handle(Request $request, Closure $next)
    {
        $bearer = $request->bearerToken();
        abort_unless($bearer && strlen($bearer) <= 200, 401);
        $token = PersonalAccessToken::findToken($bearer);
        abort_unless($token && $token->expires_at && $token->expires_at->isFuture(), 401);
        abort_unless($token->tokenable_type === User::class && $token->can('quickreads:v2'), 401);
        $user = $token->tokenable;
        abort_unless($user instanceof User, 401);
        $user->withAccessToken($token);
        $request->setUserResolver(fn () => $user);
        // Avoid a database write for every request.
        if (!$token->last_used_at || $token->last_used_at->lt(now()->subMinutes(5))) {
            $token->forceFill(['last_used_at' => now()])->save();
        }
        return $next($request);
    }
}
