<?php

return [
    'token_lifetime_days' => 30,
    // No language is recorded on stories. Null means unknown, not English.
    'password_reset_url' => env('QUICKREADS_V2_RESET_URL', 'https://leftlaneapps.com/quickreads/v2/password/reset'),
    'apple' => [
        'enabled' => env('QUICKREADS_APPLE_ENABLED', false),
        'client_id' => 'com.leftlaneapps.shortstoriesdot',
        'team_id' => env('QUICKREADS_APPLE_TEAM_ID'),
        'key_id' => env('QUICKREADS_APPLE_KEY_ID'),
        'private_key_path' => env('QUICKREADS_APPLE_PRIVATE_KEY_PATH'),
        'challenge_seconds' => 300,
        'recovery_seconds' => 300,
        'clock_skew_seconds' => 60,
    ],
    'activity_retention_days' => 90,
];
