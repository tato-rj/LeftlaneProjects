<?php

namespace App\Http\Controllers\Projects\PianoLit;

use App\Projects\PianoLit\Tag;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TagsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('create');

        $types = Tag::byTypes();
        return view('projects/pianolit/tags/index', compact('types'));
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create');

        $request->validate([
            'name' => 'required|unique:pianolit.tags|max:255',
        ]);

        Tag::create([
            'name' => $request->name,
            'creator_id' => auth()->user()->id,
            'type' => $request->type
        ]);

        return redirect()->back()->with('success', "The tag has been successfully added!");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function show(Tag $tag)
    {
        //
    }

    public function pieces($tagName)
    {
        $pieces = Tag::where('name', $tagName)->first()->load('pieces')->pieces;

        return view('projects/pianolit/components/render/pieces', compact('pieces'))->render();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function edit(Tag $tag)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tag $tag)
    {
        $this->authorize('update', $tag);

        $request->validate([
            'name' => 'required|max:255',
        ]);

        $tag->update([
            'name' => $request->name,
            'type' => $request->type,
        ]);

        return redirect()->back()->with('success', "The tag has been successfully updated!");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tag $tag)
    {
        $this->authorize('update', $tag);

        $tag->delete();

        return redirect()->back()->with('success', "The tag has been successfully deleted!");
    }
}
