<?php

namespace App\Policies;

use App\Projects\PianoLit\{Admin, Piece};
use Illuminate\Auth\Access\HandlesAuthorization;

class PiecePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the piece.
     *
     * @param  \  $user
     * @param  \App\Piece  $piece
     * @return mixed
     */
    public function view(Admin $user, Piece $piece)
    {
        //
    }

    /**
     * Determine whether the user can create pieces.
     *
     * @param  \  $user
     * @return mixed
     */
    public function create(Admin $user)
    {
        //
    }

    /**
     * Determine whether the user can update the piece.
     *
     * @param  \  $user
     * @param  \App\Piece  $piece
     * @return mixed
     */
    public function update(Admin $user, Piece $piece)
    {
        return $piece->creator_id == $user->id;
    }

    /**
     * Determine whether the user can delete the piece.
     *
     * @param  \  $user
     * @param  \App\Piece  $piece
     * @return mixed
     */
    public function delete(Admin $user, Piece $piece)
    {
        //
    }
}
