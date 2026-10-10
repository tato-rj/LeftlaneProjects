<?php

namespace App\Projects\Quickreads\Api\V2;

use App\Notifications\Quickreads\ResetPassword;
use Laravel\Sanctum\HasApiTokens;

// Separate model prevents changing legacy serialization, guards or token routing.
class User extends \App\Projects\Quickreads\User
{
    use HasApiTokens;

    public function tokens()
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }
}
