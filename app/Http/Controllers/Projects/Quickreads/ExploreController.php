<?php

namespace App\Http\Controllers\Projects\Quickreads;

use App\Projects\Quickreads\Explore;
use App\Projects\Quickreads\Admin\{Editor, Queries};
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExploreController extends QuickreadsController
{
    public function index()
    {
        $layout = Explore::read();
        $labels = [];
        foreach (['category' => ['categories', 'category'], 'author' => ['authors', 'name']] as $kind => [$table, $column]) {
            $ids = array_filter(array_column($layout['sections'], $kind.'_id'));
            if (!$ids) continue;
            foreach (Queries::db()->table($table)->whereIn('id', $ids)->pluck($column, 'id') as $id => $label) $labels[$kind.':'.$id] = $label;
        }
        return view('projects.quickreads.explore.index', ['initial' => $layout + ['preview' => Explore::preview($layout['sections']), 'labels' => $labels]]);
    }

    public function preview(Request $request)
    {
        $sections = Explore::validate($request, false);
        return response()->json(['preview' => Explore::preview($sections)]);
    }

    public function update(Request $request)
    {
        $request->validate(['revision' => 'required|string|max:64']);
        $sections = Explore::validate($request, true);
        $revision = Explore::db()->transaction(function () use ($request, $sections) {
            Explore::db()->table('explore_layouts')->insertOrIgnore(['id' => 1, 'sections' => json_encode(Explore::defaults()),
                'revision' => 'initial', 'created_at' => now(), 'updated_at' => now()]);
            $layout = Explore::db()->table('explore_layouts')->where('id', 1)->lockForUpdate()->first();
            abort_if($layout->revision !== $request->input('revision'), 409, 'Explore changed in another window. Reload the page before saving.');
            $revision = (string) Str::uuid();
            Explore::db()->table('explore_layouts')->where('id', 1)->update(['sections' => json_encode($sections), 'revision' => $revision, 'updated_at' => now()]);
            return $revision;
        }, 3);
        Editor::changed();
        return response()->json(['revision' => $revision, 'sections' => $sections, 'preview' => Explore::preview($sections)]);
    }

    public function stories(Request $request)
    {
        $input = $request->validate(['q' => 'nullable|string|max:150']);
        $query = Queries::stories()->whereNotNull('a.id')->whereNotNull('c.id')
            ->select('s.id', 's.title', 's.slug', 's.time', 's.summary', 'a.name as author');
        Queries::search($query, ['s.title', 'a.name'], $input['q'] ?? null);
        return response()->json($query->orderByDesc('s.created_at')->orderByDesc('s.id')->limit(20)->get()->map(function ($story) {
            $story->story_filename = asset('storage/stories/'.$story->slug.'/'.$story->slug.'.jpeg');
            return Explore::card($story);
        }));
    }
}
