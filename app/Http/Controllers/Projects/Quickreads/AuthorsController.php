<?php

namespace App\Http\Controllers\Projects\Quickreads;

use App\Projects\Quickreads\Author;
use Illuminate\Http\Request;

class AuthorsController extends QuickreadsController
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['app']]);
    }
    
    public function index()
    {
        //
    }

    public function app()
    {
        return Author::with('stories')->get();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('projects/quickreads/authors/add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:quickreads.authors|max:140',
            'life' => 'required'
        ]);

        $author = Author::create([
            'slug' => str_slug($request->name),
            'name' => $request->name,
            'born_in' => $request->born_in,
            'died_in' => $request->died_in,
            'life' => $request->life
        ]);

        return redirect()->back()->with('success', "$request->name has been successfully added!");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Author  $author
     * @return \Illuminate\Http\Response
     */
    public function show(Author $author)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Author  $author
     * @return \Illuminate\Http\Response
     */
    public function select()
    {
        $authors = Author::orderBy('name')->get();
        return view('projects/quickreads/authors/edit', compact(['authors']));
    }

    public function edit(Author $author)
    {
        $authors = Author::orderBy('name')->get();
        return view('projects/quickreads/authors/edit', compact(['author', 'authors']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Author  $author
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Author $author)
    {
        $slug = str_slug($request->name);

        $request->validate([
            'name' => 'required|max:140',
            'life' => 'required'
        ]);

        $author->update([
            'slug' => $slug,
            'name' => $request->name,
            'born_in' => $request->born_in,
            'died_in' => $request->died_in,
            'life' => $request->life
        ]);

        return redirect("/quickreads/authors/edit/$slug")->with('success', "$request->name has been updated");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Author  $author
     * @return \Illuminate\Http\Response
     */
    public function delete()
    {
        $authors = Author::orderBy('name')->get();
        return view('projects/quickreads/authors/delete', compact(['authors']));        
    }
    
    public function destroy(Author $author)
    {
        $author->delete();
        return redirect()->back()->with('success', "$author->name has been successfully removed!");
    }
}
