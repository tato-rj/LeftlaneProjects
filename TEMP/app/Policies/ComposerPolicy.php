<?php

namespace App\Policies;

use App\Projects\PianoLit\{Admin, Composer};
use Illuminate\Auth\Access\HandlesAuthorization;

class ComposerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the composer.
     *
     * @param  \  $user
     * @param  \App\Composer  $composer
     * @return mixed
     */
    public function view(Admin $user, Composer $composer)
    {
        //
    }

    /**
     * Determine whether the user can create composers.
     *
     * @param  \  $user
     * @return mixed
     */
    public function create(Admin $user)
    {
        //
    }

    /**
     * Determine whether the user can update the composer.
     *
     * @param  \  $user
     * @param  \App\Composer  $composer
     * @return mixed
     */
    public function update(Admin $user, Composer $composer)
    {
        return $composer->creator_id == $user->id;
    }

    /**
     * Determine whether the user can delete the composer.
     *
     * @param  \  $user
     * @param  \App\Composer  $composer
     * @return mixed
     */
    public function delete(Admin $user, Composer $composer)
    {
        //
    }
}
