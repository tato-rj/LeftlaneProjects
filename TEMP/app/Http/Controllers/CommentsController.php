<?php

namespace App\Http\Controllers;

use App\Comment;
use App\Story;
use App\User;
use Illuminate\Http\Request;

class CommentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        // $request["story_id"] = Story::where('title', '=',$request["title"])->pluck('id')[0];

        $request->validate([
            'facebook_id' => 'required',
            'title' => 'required',
            'body' => 'required|max:248|unique:comments'
        ]);

        $comment = Comment::create([
            'user_id' => User::where('facebook_id', '=',$request["facebook_id"])->pluck('id')[0],
            'story_id' => Story::where('title', '=',$request["title"])->pluck('id')[0],
            'body' => $request["body"]
        ]);

        return $comment;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
