<?php

namespace App\Http\Controllers\Projects\Quickreads;

use App\Projects\Quickreads\{Story, User, Author, Category, UserPurchaseRecord};
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Upload;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class StoriesController extends QuickreadsController
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['app', 'text', 'incrementViews', 'explore', 'test']]);
    }

    public function test()
    {
        return Story::withCount('favorites')->find(65);
    }

    public function explore(Request $request)
    {
        $collection = \Cache::remember('quickreads-explore-5', 1440, function() {
            $pick = collect(['title' => 'Today\'s pick', 'collection' => collect([Story::formatted()->inRandomOrder()->first()])]);
            $latest = collect(['title' => 'Latest stories', 'collection' => Story::formatted()->latest()->take(5)->get()]);
            $popular = collect(['title' => 'Most popular', 'collection' => Story::withCount('favorites')->formatted()->orderBy('favorites_count', 'desc')->take(8)->get()]);
            $under10 = collect(['title' => 'Under 10 minutes', 'collection' => Story::formatted()->where('time', '<=', 10)->take(8)->get()]);
            $classics = collect(['title' => 'Classics', 'collection' => Story::formatted()->where('is_classic', true)->inRandomOrder()->take(6)->get()]);
            $suggestions = collect(['title' => 'Not sure what to read?', 'collection' => Story::formatted()->inRandomOrder()->take(6)->get()]);

            return collect([$pick, $latest, $popular, $under10, $classics, $suggestions]);
        });

        if ($user = User::where('facebook_id', $request->facebook_id)->first()) {
            $favorites = UserPurchaseRecord::where('user_id', $user->id)->get();

            foreach ($collection as $item) {
                foreach($item['collection'] as $story) {
                    $story->favorited = ! $favorites->where('story_id', $story->id)->isEmpty();
                }
            }
        }

        return $collection;
    }

    public function app()
    {
        $stories = DB::connection('quickreads')->table('stories')
                        ->join('categories', 'stories.category_id', '=', 'categories.id')
                        ->join('authors', 'stories.author_id', '=', 'authors.id')
                        ->selectRaw('CAST(stories.id as CHAR(100)) as id, authors.name AS author, CONCAT("(",authors.born_in," - ",authors.died_in,")") AS dates, authors.life AS life, stories.title AS title, stories.summary AS summary, stories.time AS time, stories.cost AS cost, CONCAT("https://leftlaneapps.com/storage/stories/",stories.slug,"/",stories.slug,".jpeg") AS story_filename, categories.category AS category, categories.sorting_order')
                        ->orderBy('sorting_order')
                        ->get();

        if ($user = User::where('facebook_id', request()->facebook_id)->first()) {
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
    public function create()
    {
        $authors = Author::orderBy('name')->get();
        $categories = Category::orderBy('category')->get();
        return view('projects/quickreads/stories/add', compact(['authors', 'categories']));
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
            'title' => 'required|unique:quickreads.stories|max:255',
            'summary' => 'required',
            'text' => 'required',
            'author_id' => 'required',
            'category_id' => 'required',
            'time' => 'required',
            'cost' => 'required',
        ]);

        $story = Story::create([
            'slug' => str_slug($request->title),
            'title' => $request->title,
            'summary' => $request->summary,
            'text' => $request->text,
            'author_id' => $request->author_id,
            'category_id' => $request->category_id,
            'time' => $request->time,
            'cost' => $request->cost,
            'is_classic' => $request->is_classic ? 1 : 0
        ]);

        $this->saveFile($request);

        return redirect()->back()->with('success', "$request->title has been successfully added!");
    }

    public function incrementViews(Request $request)
    {
        $id = Story::where('title', '=',$request->title)->pluck('id')[0];
        Story::find($id)->increment('views');
    }

    public function saveFile($request)
    {
        if ($request->file('image')) {
            $slug = str_slug($request->title);
            (new Upload($request->file('image')))->name($slug)->path("/public/stories/$slug/")->save();            
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Story  $story
     * @return \Illuminate\Http\Response
     */
    public function show(Story $story)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Story  $story
     * @return \Illuminate\Http\Response
     */
    public function select()
    {
        $stories = Story::orderBy('title')->get();

        return view('projects/quickreads/stories/edit', compact(['stories']));
    }

    public function edit(Story $story)
    {
        $authors = Author::orderBy('name')->get();
        $categories = Category::orderBy('category')->get();
        $stories = Story::orderBy('title')->get();

        return view('projects/quickreads/stories/edit', compact(['stories', 'story', 'authors', 'categories']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Story  $story
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Story $story)
    {
        $slug = str_slug($request->title);

        $request->validate([
            'title' => 'required|max:255',
            'summary' => 'required',
            'text' => 'required',
            'author_id' => 'required',
            'category_id' => 'required',
            'time' => 'required',
            'cost' => 'required',
        ]);

        $story->update([
            'slug' => $slug,
            'title' => $request->title,
            'summary' => $request->summary,
            'text' => $request->text,
            'author_id' => $request->author_id,
            'category_id' => $request->category_id,
            'time' => $request->time,
            'cost' => $request->cost,
            'is_classic' => $request->is_classic ? 1 : 0
        ]);

        $this->saveFile($request);

        return redirect("/quickreads/stories/edit/$slug")->with('success', "$request->title has been updated");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Story  $story
     * @return \Illuminate\Http\Response
     */
    public function delete()
    {
        $stories = Story::orderBy('title')->get();
        return view('projects/quickreads/stories/delete', compact(['stories']));       
    }

    public function destroy(Story $story)
    {
        File::deleteDirectory("storage/stories/$story->slug");
        $story->delete();
        return redirect()->back()->with('success', "$story->title has been successfully removed!");
    }
}
