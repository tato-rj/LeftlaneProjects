<?php

namespace App\Http\Controllers\Projects\Quickreads;

use App\Projects\Quickreads\{Category, Story};
use App\Projects\Quickreads\Admin\{Queries, Editor};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriesController extends QuickreadsController
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['app', 'time', 'show']]);
    }

    public function index(Request $request)
    {
        $criteria = Queries::criteria($request);
        $query = Queries::db()->table('categories as r')->select('r.*')
            ->selectSub(Queries::db()->table('stories as s')->selectRaw('COUNT(*)')->whereColumn('s.category_id', 'r.id'), 'stories_count');
        Queries::search($query, ['r.category'], $criteria['q'] ?? null);
        $query->orderByRaw('r.sorting_order IS NULL')->orderBy('r.sorting_order');
        $categories = Queries::page($query->orderBy('r.category')->orderBy('r.id'), $criteria);
        return view('projects.quickreads.categories.index', compact('categories'));
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


    public function create()
    {
        return view('projects.quickreads.categories.form', ['category' => null]);
    }

    private function validated(Request $request, $category = null): array
    {
        return $request->validate([
            'category' => ['required', 'string', 'max:120', Rule::unique('quickreads.categories', 'category')->ignore($category->id ?? null)],
            'sorting_order' => 'nullable|integer|between:-128,127',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Editor::slug('categories', $data['category']);
        $category = Category::create($data);
        Editor::changed();
        return redirect('/quickreads/categories/edit/'.$category->slug)->with('success', 'Category added.');
    }

    public function select()
    {
        return redirect('/quickreads/categories');
    }

    public function edit(Category $category)
    {
        return view('projects.quickreads.categories.form', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request, $category));
        Editor::changed();
        return redirect('/quickreads/categories/edit/'.$category->slug)->with('success', 'Category updated.');
    }

    public function delete()
    {
        return redirect('/quickreads/categories');
    }

    public function destroy(Request $request, Category $category)
    {
        $request->validate(['confirm' => 'required|accepted']);
        $removed = Queries::db()->transaction(function () use ($category) {
            Queries::db()->table('categories')->where('id', $category->id)->lockForUpdate()->first();
            if (Queries::db()->table('stories')->where('category_id', $category->id)->exists()) return false;
            $category->delete();
            return true;
        });
        if (!$removed) return back()->withErrors(['delete' => 'Reassign this category’s stories before deleting it.']);
        Editor::changed();
        return redirect('/quickreads/categories')->with('success', 'Category removed.');
    }
}
