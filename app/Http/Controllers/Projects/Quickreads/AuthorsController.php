<?php

namespace App\Http\Controllers\Projects\Quickreads;

use App\Projects\Quickreads\{Author, Story};
use App\Projects\Quickreads\Admin\{Queries, Editor};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthorsController extends QuickreadsController
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['app']]);
    }

    public function index(Request $request)
    {
        $criteria = Queries::criteria($request);
        $query = Queries::db()->table('authors as r')->select('r.id', 'r.slug', 'r.name', 'r.born_in', 'r.died_in')
            ->selectSub(Queries::db()->table('stories as s')->selectRaw('COUNT(*)')->whereColumn('s.author_id', 'r.id'), 'stories_count');
        Queries::search($query, ['r.name'], $criteria['q'] ?? null);

        $authors = Queries::page($query->orderBy('r.name')->orderBy('r.id'), $criteria);
        return view('projects.quickreads.authors.index', compact('authors'));
    }

    public function app()
    {
        return Author::with('stories')->get();
    }

    public function create()
    {
        return view('projects.quickreads.authors.form', ['author' => null]);
    }

    private function validated(Request $request, $author = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:140', Rule::unique('quickreads.authors', 'name')->ignore($author->id ?? null)],
            'born_in' => 'nullable|string|max:30', 'died_in' => 'nullable|string|max:30', 'life' => 'required|string|max:100000',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Editor::slug('authors', $data['name']);
        $author = Author::create($data);
        Editor::changed();
        return redirect('/quickreads/authors/edit/'.$author->slug)->with('success', 'Author added.');
    }

    public function select()
    {
        return redirect('/quickreads/authors');
    }

    public function edit(Author $author)
    {
        return view('projects.quickreads.authors.form', compact('author'));
    }

    public function update(Request $request, Author $author)
    {
        $author->update($this->validated($request, $author));
        Editor::changed();
        return redirect('/quickreads/authors/edit/'.$author->slug)->with('success', 'Author updated.');
    }

    public function delete()
    {
        return redirect('/quickreads/authors');
    }

    public function destroy(Request $request, Author $author)
    {
        $request->validate(['confirm' => 'required|accepted']);
        $removed = Queries::db()->transaction(function () use ($author) {
            Queries::db()->table('authors')->where('id', $author->id)->lockForUpdate()->first();
            if (Queries::db()->table('stories')->where('author_id', $author->id)->exists()) return false;
            $author->delete();
            return true;
        });
        if (!$removed) return back()->withErrors(['delete' => 'Reassign this author’s stories before deleting it.']);
        Editor::changed();
        return redirect('/quickreads/authors')->with('success', 'Author removed.');
    }
}
