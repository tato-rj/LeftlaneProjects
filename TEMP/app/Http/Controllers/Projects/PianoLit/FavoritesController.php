<?php

namespace App\Http\Controllers\Projects\PianoLit;

use App\Projects\PianoLit\{User, Piece};
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return User::find($request->user_id)->favorites()->attach($request->piece_id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        return User::find($request->user_id)->favorites()->detach($request->piece_id);
    }
}
