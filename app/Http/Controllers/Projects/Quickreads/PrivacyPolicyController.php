<?php

namespace App\Http\Controllers\Projects\Quickreads;

use App\Http\Controllers\Controller;

class PrivacyPolicyController extends Controller
{
    public function __invoke()
    {
        $published = config('quickreads_privacy.published') === true;

        abort_unless($published || app()->environment('local', 'testing'), 404);

        $response = response()->view('projects.quickreads.privacy', ['preview' => !$published])
            ->header('Cache-Control', $published ? 'public, max-age=3600' : 'no-store')
            ->header('Content-Security-Policy', "default-src 'none'; style-src 'self'; img-src 'self'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'")
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Content-Type-Options', 'nosniff');

        if (!$published) {
            $response->header('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
