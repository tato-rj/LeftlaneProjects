<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AcceptApiRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        abort(404);
        if (! \DB::connection('videouploader')->table('personal_access_tokens')->where('name', $request->secret)->exists())
            throw new \Illuminate\Auth\Access\AuthorizationException('Token mismatch, you are not authorized to do this.');

        return $next($request);
    }
}
