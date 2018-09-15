<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'piano-lit/api/pieces/find',
        'piano-lit/api/users/set-favorites',
        'piano-lit/api/users/get-favorites',
        'piano-lit/api/users/get-suggestions',
        'piano-lit/api/users/subscription',
        'piano-lit/api/users',
        'piano-lit/api/users/login',
        'piano-lit/api/search',
        'piano-lit/api/tour',
        'piano-lit/api/discover',
        'quickreads/password/email',
        'quickreads/password/reset',
        'quickreads/app/records/purchase',
        'quickreads/app/stories/views',
        'quickreads/users/register',
        'quickreads/users/facebook'
    ];
}
