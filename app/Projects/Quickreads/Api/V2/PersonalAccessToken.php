<?php

namespace App\Projects\Quickreads\Api\V2;

class PersonalAccessToken extends \Laravel\Sanctum\PersonalAccessToken
{
    protected $connection = 'quickreads';
    protected $table = 'api_v2_tokens';
}
