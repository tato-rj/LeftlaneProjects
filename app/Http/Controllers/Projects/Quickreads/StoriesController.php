<?php

namespace App\Http\Controllers\Projects\Quickreads;

use App\Projects\Quickreads\{Story, User, UserPurchaseRecord, LatestAdditions, Explore};
use Illuminate\Support\Facades\DB;
use App\Projects\Quickreads\Admin\{Queries, Editor};
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class StoriesController extends QuickreadsController
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['app', 'text', 'incrementViews', 'explore', 'latest', 'test']]);
    }

    public function test()
    {
        return Story::withCount('favorites')->find(65);
    }

    public function explore(Request $request)
    {
        $collection = Explore::collections(Explore::read()['sections']);

        $alias = $request->query('facebook_id');
        if (is_string($alias) && $alias !== '' && ($user = User::where('facebook_id', $alias)->first())) {
            $ids = $collection->flatMap(fn ($section) => $section['collection']->pluck('id'))->unique()->values()->all();
            $favorites = UserPurchaseRecord::where('user_id', $user->id)->whereIn('story_id', $ids)->pluck('story_id')->flip();

            foreach ($collection as $item) {
                foreach($item['collection'] as $story) {
                    $story->favorited = $favorites->has($story->id);
                }
            }
        }

        return $collection;
    }

    public function latest()
    {
        return LatestAdditions::stories();
    }

    public function app()
    {
        $stories = DB::connection('quickreads')->table('stories')
                        ->join('categories', 'stories.category_id', '=', 'categories.id')
                        ->join('authors', 'stories.author_id', '=', 'authors.id')
                        ->selectRaw('CAST(stories.id as CHAR(100)) as id, authors.name AS author, CONCAT("(",authors.born_in," - ",authors.died_in,")") AS dates, authors.life AS life, stories.title AS title, stories.summary AS summary, stories.time AS time, stories.cost AS cost, CONCAT("https://leftlaneapps.com/storage/stories/",stories.slug,"/",stories.slug,".jpeg") AS story_filename, categories.category AS category, categories.sorting_order')
                        ->orderBy('sorting_order')
                        ->get();

        $alias = request()->facebook_id;
        if (is_string($alias) && $alias !== '' && ($user = User::where('facebook_id', $alias)->first())) {
            $favorites = UserPurchaseRecord::where('user_id', $user->id)->get();
            
            foreach ($stories as $story) {
                $story->favorited = $favorites->contains($story->id);
            }
        }

        return $stories;
    }

    public function text(Request $request)
    {
        $story = DB::connection('quickreads')->table('stories')
                        ->selectRaw('text')
                        ->where('id', '=', $request->id)
                        ->get();

        return $story;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $criteria = Queries::criteria($request);
        $query = Queries::stories()->select('s.id', 's.slug', 's.title', 's.time', 's.is_classic', 's.views', 's.created_at', 'a.name as author', 'c.category');
        Queries::search($query, ['s.title', 'a.name'], $criteria['q'] ?? null);
        foreach (['author_id', 'category_id'] as $field) if (!empty($criteria[$field])) $query->where('s.'.$field, $criteria[$field]);
        if (isset($criteria['classic'])) $query->where('s.is_classic', $criteria['classic']);
        if (($criteria['issue'] ?? '') === 'relationships') $query->where(fn ($q) => $q->whereNull('a.id')->orWhereNull('c.id'));
        if (($criteria['issue'] ?? '') === 'time') $query->where(fn ($q) => Queries::invalidTime($q));
        $sort = $criteria['sort'] ?? 'newest';
        $sort === 'name' ? $query->orderBy('s.title') : $query->orderBy('s.created_at', $sort === 'oldest' ? 'asc' : 'desc');
        $stories = Queries::page($query->orderByDesc('s.id'), $criteria);
        $selectedAuthor = !empty($criteria['author_id']) ? Queries::db()->table('authors')->find($criteria['author_id']) : null;
        $selectedCategory = !empty($criteria['category_id']) ? Queries::db()->table('categories')->find($criteria['category_id']) : null;
        return view('projects.quickreads.stories.index', compact('stories', 'selectedAuthor', 'selectedCategory'));
    }

    public function create()
    {
        $selectedCategory = $this->selected('categories', old('category_id'));
        $automaticCategory = LatestAdditions::isCategory($selectedCategory);
        if ($automaticCategory) $selectedCategory = null;
        return view('projects.quickreads.stories.form', ['story' => null, 'selectedAuthor' => $this->selected('authors', old('author_id')), 'selectedCategory' => $selectedCategory, 'automaticCategory' => $automaticCategory]);
    }

    private function selected($table, $id)
    {
        return $id ? Queries::db()->table($table)->find($id) : null;
    }

    public function store(Request $request)
    {
        $data = Editor::story($request);
        $data['slug'] = Editor::slug('stories', $data['title']);
        $story = Queries::db()->transaction(function () use ($data, $request) {
            $story = Story::create($data);
            $this->saveCover($request, $story);
            return $story;
        });
        Editor::changed();
        return redirect('/quickreads/stories/edit/'.$story->slug)->with('success', 'Story added. It is now available in the catalogue.');
    }

    public function incrementViews(Request $request)
    {
        $id = Story::where('title', '=',$request->title)->pluck('id')[0];
        Story::find($id)->increment('views');
    }

    private function saveCover(Request $request, Story $story): void
    {
        if (!$request->hasFile('image')) return;
        // Retain the legacy cover URL used by older clients. The v2 API detects the actual MIME type.
        $saved = $request->file('image')->storeAs('stories/'.$story->slug, $story->slug.'.jpeg', 'public');
        if (!$saved) {
            throw \Illuminate\Validation\ValidationException::withMessages(['image' => 'The cover could not be saved. Please try again.']);
        }
    }

    public function select()
    {
        return redirect('/quickreads/stories');
    }

    public function edit(Story $story)
    {
        $selectedAuthor = $this->selected('authors', old('author_id', $story->author_id));
        $selectedCategory = $this->selected('categories', old('category_id', $story->category_id));
        $automaticCategory = LatestAdditions::isCategory($selectedCategory);
        if ($automaticCategory) $selectedCategory = null;
        $artwork = (new \App\Projects\Quickreads\Api\V2\Catalog)->image($story);
        return view('projects.quickreads.stories.form', compact('story', 'selectedAuthor', 'selectedCategory', 'automaticCategory', 'artwork'));
    }

    public function update(Request $request, Story $story)
    {
        // Keep the slug stable when renaming, so existing cover URLs and links remain valid.
        $data = Editor::story($request, $story);
        Queries::db()->transaction(function () use ($story, $data, $request) {
            $story->update($data);
            $this->saveCover($request, $story);
        });
        Editor::changed();
        return redirect('/quickreads/stories/edit/'.$story->slug)->with('success', 'Story updated.');
    }

    public function delete()
    {
        return redirect('/quickreads/stories');
    }

    public function destroy(Request $request, Story $story)
    {
        $request->validate(['confirm' => 'required|accepted']);
        Queries::db()->transaction(function () use ($story) {
            // Historical tables have no foreign keys. Clean up their dependent rows explicitly.
            Queries::db()->table('stories')->where('id', $story->id)->lockForUpdate()->first();
            foreach (['user_purchase_records', 'ratings', 'comments'] as $table) Queries::db()->table($table)->where('story_id', $story->id)->delete();
            $story->delete();
        });
        if (preg_match('/^[a-zA-Z0-9_-]+$/D', $story->slug)) Storage::disk('public')->deleteDirectory('stories/'.$story->slug);
        Editor::changed();
        return redirect('/quickreads/stories')->with('success', 'Story and its saved-story records, ratings and comments removed.');
    }
}
