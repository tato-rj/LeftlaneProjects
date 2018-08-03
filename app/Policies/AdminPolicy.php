<?php

namespace App\Policies;

use App\Projects\PianoLit\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the admin.
     *
     * @param  \  $user
     * @param  \App\Admin  $admin
     * @return mixed
     */
    public function view( $user, Admin $admin)
    {
        //
    }

    /**
     * Determine whether the user can create admins.
     *
     * @param  \  $user
     * @return mixed
     */
    public function create()
    {
        return false;
    }

    /**
     * Determine whether the user can update the admin.
     *
     * @param  \  $user
     * @param  \App\Admin  $admin
     * @return mixed
     */
    public function update( $user, Admin $admin)
    {
        return $user->email == $admin->email && $user->password == $admin->password;
    }

    /**
     * Determine whether the user can delete the admin.
     *
     * @param  \  $user
     * @param  \App\Admin  $admin
     * @return mixed
     */
    public function delete( $user, Admin $admin)
    {
        //
    }
}
