<?php

namespace App\Projects\Quickreads;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class QuickreadsAdmin extends Authenticatable
{
    use Notifiable;

    protected $guard = 'quickreads-admin';
    protected $connection = 'quickreads';
    protected $table = 'admins';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
}
