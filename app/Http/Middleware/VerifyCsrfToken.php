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
        'piano-lit/api/search',
        'piano-lit/api/discover',
        'quickreads/password/email',
        'quickreads/password/reset',
        'quickreads/app/records/purchase',
        'quickreads/app/stories/views',
        'quickreads/users/register',
        'quickreads/users/facebook'
    ];
}
