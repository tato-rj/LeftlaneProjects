<?php

namespace App\Http\Controllers\Projects\Quickreads;

use App\Projects\Quickreads\{Category, Story};
use Illuminate\Http\Request;

class CategoriesController extends QuickreadsController
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['app', 'time', 'show']]);
    }
    
    public function index()
    {
        //
    }

    public function app()
    {
        return Category::all();
    }

    public function time(Request $request)
    {
        return Story::formatted()->whereBetween('time', [(int)$request->min, (int)$request->max])->get();//->favoritedBy($request->facebook_id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        return Story::formatted()->where('category_id', $request->category_id)->get();//->favoritedBy($request->facebook_id);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('projects/quickreads/categories/add');
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
            'category' => 'required|unique:quickreads.categories|max:120'
        ]);

        $category = Category::create([
            'slug' => str_slug($request->category),
            'category' => $request->category
        ]);

        return redirect()->back()->with('success', "$request->category has been successfully added!");
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function select()
    {

        $categories = Category::orderBy('category')->get();
        return view('projects/quickreads/categories/edit', compact(['categories']));
    }

    public function edit(Category $category)
    {
        $categories = Category::orderBy('category')->get();
        return view('projects/quickreads/categories/edit', compact(['category', 'categories']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        $slug = str_slug($request->category);

        $request->validate([
            'category' => 'required|max:120'
        ]);

        $category->update([
            'slug' => $slug,
            'category' => $request->category,
            'sorting_order' => $request->sorting_order
        ]);

        return redirect("/quickreads/categories/edit/$slug")->with('success', "$request->category has been updated");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function delete()
    {
        $categories = Category::orderBy('category')->get();
        return view('projects/quickreads/categories/delete', compact(['categories']));        
    }
    
    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->back()->with('success', "$category->category has been successfully removed!");
    }
}
