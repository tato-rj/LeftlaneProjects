<?php

namespace App\Http\Controllers\Projects\Quickreads;

use App\Http\Controllers\Controller;

class PrivacyPolicyController extends Controller
{
    public function __invoke()
    {
        return response()->view('projects.quickreads.privacy')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Content-Security-Policy', "default-src 'none'; style-src 'self'; img-src 'self'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'")
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
