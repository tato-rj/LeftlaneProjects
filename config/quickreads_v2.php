<?php

return [
    'token_lifetime_days' => 30,
    // No language is recorded on stories. Null means unknown, not English.
    'password_reset_url' => env('QUICKREADS_V2_RESET_URL', 'https://leftlaneapps.com/quickreads/v2/password/reset'),
];
