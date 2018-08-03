<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Projects\PianoLit\Piece' => 'App\Policies\PiecePolicy',
        'App\Projects\PianoLit\Composer' => 'App\Policies\ComposerPolicy',
        'App\Projects\PianoLit\Tag' => 'App\Policies\TagPolicy',
        'App\Projects\PianoLit\Admin' => 'App\Policies\AdminPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function($user) {
            if ($user->role == 'manager')
                return true;
        });
    }
}
