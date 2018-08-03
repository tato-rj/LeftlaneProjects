<?php

namespace App\Policies;

use App\Projects\PianoLit\{Tag, Admin};
use Illuminate\Auth\Access\HandlesAuthorization;

class TagPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the tag.
     *
     * @param  \  $user
     * @param  \App\Tag  $tag
     * @return mixed
     */
    public function view(Admin $user, Tag $tag)
    {
        //
    }

    /**
     * Determine whether the user can create tags.
     *
     * @param  \  $user
     * @return mixed
     */
    public function create(Admin $user)
    {
        return $user->role == 'manager';
    }

    /**
     * Determine whether the user can update the tag.
     *
     * @param  \  $user
     * @param  \App\Tag  $tag
     * @return mixed
     */
    public function update(Admin $user, Tag $tag)
    {
        return $user->role == 'manager';
    }

    /**
     * Determine whether the user can delete the tag.
     *
     * @param  \  $user
     * @param  \App\Tag  $tag
     * @return mixed
     */
    public function delete(Admin $user, Tag $tag)
    {
        //
    }
}
