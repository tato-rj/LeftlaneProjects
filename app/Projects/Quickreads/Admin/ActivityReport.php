<?php

namespace App\Projects\Quickreads\Admin;

use Illuminate\Http\Request;

class ActivityReport
{
    public static function read(Request $request): array
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:150', 'reader_kind' => 'nullable|in:guest,account',
            'status' => 'nullable|in:saved,reading,finished', 'view' => 'nullable|in:readers,stories',
            'story_id' => 'nullable|integer|min:1|max:4294967295',
            'page' => 'nullable|integer|min:1|max:100000',
        ]);
        $view = $filters['view'] ?? 'readers';
        $base = Queries::db()->table('story_activity as activity')
            ->leftJoin('stories as story', 'story.id', '=', 'activity.story_id')
            ->leftJoin('authors as author', 'author.id', '=', 'story.author_id')
            ->leftJoin('users as reader', function ($join) {
                $join->on('reader.id', '=', 'activity.user_id')->where('activity.reader_kind', '=', 'account');
            })
            ->where('activity.updated_at', '>=', now()->utc()->subDays(config('quickreads_v2.activity_retention_days')))
            ->when($filters['reader_kind'] ?? null, fn ($query, $kind) => $query->where('activity.reader_kind', $kind))
            ->when($filters['story_id'] ?? null, fn ($query, $id) => $query->where('activity.story_id', $id));
        $fullName = Queries::db()->getDriverName() === 'sqlite'
            ? "(reader.first_name || ' ' || reader.last_name)" : "CONCAT_WS(' ', reader.first_name, reader.last_name)";
        Queries::search($base, ['story.title', 'author.name', 'reader.first_name', 'reader.last_name', $fullName], $filters['q'] ?? null);

        // Uncached so refreshing after an app action reads its latest committed
        // report. Saved membership remains independent of reading status.
        $summary = (clone $base)->selectRaw('COUNT(*) as reports, MAX(activity.updated_at) as last_received_at')
            ->selectRaw('COALESCE(SUM(CASE WHEN activity.saved = 1 THEN 1 ELSE 0 END), 0) as saved')
            ->selectRaw("COALESCE(SUM(CASE WHEN activity.reading_status = 'reading' THEN 1 ELSE 0 END), 0) as reading")
            ->selectRaw("COALESCE(SUM(CASE WHEN activity.reading_status = 'finished' THEN 1 ELSE 0 END), 0) as finished")
            ->first();
        $query = clone $base;
        if (($filters['status'] ?? null) === 'saved') $query->where('activity.saved', true);
        elseif (!empty($filters['status'])) $query->where('activity.reading_status', $filters['status']);

        if ($view === 'stories') {
            $query->select('activity.story_id', 'story.title', 'story.slug', 'author.name as author', 'activity.reader_kind')
                ->selectRaw('COUNT(*) as reports, MAX(activity.updated_at) as last_received_at')
                ->selectRaw('SUM(CASE WHEN activity.saved = 1 THEN 1 ELSE 0 END) as saved')
                ->selectRaw("SUM(CASE WHEN activity.reading_status = 'reading' THEN 1 ELSE 0 END) as reading")
                ->selectRaw("SUM(CASE WHEN activity.reading_status = 'finished' THEN 1 ELSE 0 END) as finished")
                ->groupBy('activity.story_id', 'story.title', 'story.slug', 'author.name', 'activity.reader_kind')
                ->orderByDesc('last_received_at')->orderBy('activity.story_id')->orderBy('activity.reader_kind');
        } else {
            // No story text, email, guest UUID/hash, credentials or mutation
            // payloads are fetched or serialized into this private report.
            $query->select('activity.id', 'activity.story_id', 'story.title', 'story.slug', 'author.name as author',
                'activity.reader_kind', 'reader.id as account_id', 'reader.first_name', 'reader.last_name',
                'activity.reading_status', 'activity.saved', 'activity.reading_observed_at',
                'activity.saved_observed_at', 'activity.updated_at')
                ->orderByDesc('activity.updated_at')->orderByDesc('activity.id');
        }
        $rows = $query->paginate(25)->withQueryString();
        return compact('filters', 'view', 'summary', 'rows');
    }
}
