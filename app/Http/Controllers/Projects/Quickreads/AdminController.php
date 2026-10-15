<?php

namespace App\Http\Controllers\Projects\Quickreads;

use App\Projects\Quickreads\Admin\Queries;
use App\Projects\Quickreads\LatestAdditions;
use Illuminate\Http\Request;

class AdminController extends QuickreadsController
{
    public function index()
    {
        $summary = Queries::dashboard();
        $recentStories = Queries::stories()->select('s.id', 's.slug', 's.title', 's.time', 's.created_at', 'a.name as author', 'c.category')
            ->orderByDesc('s.created_at')->orderByDesc('s.id')->limit(6)->get();
        $recentUsers = Queries::db()->table('users')->select('id', 'first_name', 'last_name', 'email', 'created_at')
            ->orderByDesc('created_at')->orderByDesc('id')->limit(5)->get();
        return view('projects.quickreads.dashboard', compact('summary', 'recentStories', 'recentUsers'));
    }

    public function statistics(Request $request)
    {
        return view('projects.quickreads.statistics.index', Queries::statistics($request));
    }

    public function activity(Request $request)
    {
        $input = $request->validate(['reader_kind' => 'nullable|in:guest,account', 'page' => 'nullable|integer|min:1']);
        $kind = $input['reader_kind'] ?? null;
        $query = Queries::db()->table('story_activity as activity')
            ->leftJoin('stories as story', 'story.id', '=', 'activity.story_id')
            ->where('activity.updated_at', '>=', now()->utc()->subDays(config('quickreads_v2.activity_retention_days')))
            ->when($kind, fn ($query) => $query->where('activity.reader_kind', $kind))
            ->select('activity.story_id', 'story.title', 'activity.reader_kind')
            ->selectRaw("SUM(CASE WHEN activity.reading_status = 'reading' THEN 1 ELSE 0 END) as reading")
            ->selectRaw("SUM(CASE WHEN activity.reading_status = 'finished' THEN 1 ELSE 0 END) as finished")
            ->selectRaw('SUM(CASE WHEN activity.saved = 1 THEN 1 ELSE 0 END) as saved')
            ->groupBy('activity.story_id', 'story.title', 'activity.reader_kind')
            ->orderBy('activity.story_id')->orderBy('activity.reader_kind');
        $rows = $query->paginate(50)->withQueryString();
        return response()->view('projects.quickreads.statistics.activity', compact('rows', 'kind'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function lookups(Request $request)
    {
        $input = $request->validate(['type' => 'required|in:authors,categories', 'q' => 'nullable|string|max:150']);
        $column = $input['type'] === 'authors' ? 'name' : 'category';
        $query = Queries::db()->table($input['type'])->select('id', $column.' as label');
        if ($input['type'] === 'categories') LatestAdditions::categories($query, '', true);
        Queries::search($query, [$column], $input['q'] ?? null);
        return response()->json($query->orderBy($column)->orderBy('id')->limit(20)->get());
    }
}
