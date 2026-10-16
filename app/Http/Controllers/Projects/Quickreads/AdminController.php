<?php

namespace App\Http\Controllers\Projects\Quickreads;

use App\Projects\Quickreads\Admin\{ActivityReport, Queries};
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
        return response()->view('projects.quickreads.statistics.activity', ActivityReport::read($request))
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
