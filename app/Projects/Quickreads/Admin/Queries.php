<?php

namespace App\Projects\Quickreads\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, DB};
use Illuminate\Validation\ValidationException;
use App\Projects\Quickreads\LatestAdditions;

class Queries
{
    public static function db()
    {
        return DB::connection('quickreads');
    }

    public static function criteria(Request $request): array
    {
        return $request->validate([
            'q' => 'nullable|string|max:150', 'per_page' => 'nullable|integer|in:25,50,100',
            'page' => 'nullable|integer|min:1|max:100000',
            'sort' => 'nullable|in:newest,oldest,name',
            'author_id' => 'nullable|integer|min:1', 'category_id' => 'nullable|integer|min:1',
            'classic' => 'nullable|in:0,1', 'issue' => 'nullable|in:relationships,time',
            'activity' => 'nullable|in:saved,none',
        ]);
    }

    public static function search($query, array $columns, ?string $term): void
    {
        if ($term === null || trim($term) === '') return;
        $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($term)).'%';
        $query->where(function ($query) use ($columns, $term) {
            foreach ($columns as $column) $query->orWhereRaw("$column LIKE ? ESCAPE '!'", [$term]);
        });
    }

    public static function page($query, array $criteria)
    {
        return $query->paginate($criteria['per_page'] ?? 25)->withQueryString();
    }

    public static function stories()
    {
        return self::db()->table('stories as s')
            ->leftJoin('authors as a', 'a.id', '=', 's.author_id')
            ->leftJoin('categories as c', 'c.id', '=', 's.category_id');
    }

    public static function invalidTime($query): void
    {
        // The legacy duration column is text. Validate it without treating text as zero.
        $driver = self::db()->getDriverName();
        if ($driver === 'sqlite') {
            $query->whereRaw("s.time IS NULL OR s.time = '' OR s.time GLOB '*[^0-9.]*' OR s.time GLOB '*.*.*' OR CAST(s.time AS REAL) <= 0");
        } else {
            $query->whereRaw("s.time IS NULL OR s.time NOT REGEXP '^[0-9]+([.][0-9]+)?$' OR CAST(s.time AS DECIMAL(12,4)) <= 0");
        }
    }

    public static function dashboard(): array
    {
        return Cache::remember('quickreads.admin.dashboard.v1', 300, function () {
            $db = self::db();
            $since = now()->subDays(29)->startOfDay();
            return [
                'generated_at' => now(),
                'stories' => $db->table('stories')->count(),
                'users' => $db->table('users')->count(),
                'authors' => $db->table('authors')->count(),
                'categories' => LatestAdditions::categories($db->table('categories'), '', true)->count(),
                'new_users' => $db->table('users')->where('created_at', '>=', $since)->count(),
                'saves' => $db->table('user_purchase_records')->where('created_at', '>=', $since)->count(),
                'relationships' => self::stories()->where(fn ($q) => $q->whereNull('a.id')->orWhereNull('c.id'))->count(),
                'time' => self::stories()->where(fn ($q) => self::invalidTime($q))->count(),
            ];
        });
    }

    public static function statistics(Request $request): array
    {
        $input = $request->validate([
            'range' => 'nullable|in:7,30,90,365,custom',
            'from' => 'required_if:range,custom|nullable|date_format:Y-m-d',
            'to' => 'required_if:range,custom|nullable|date_format:Y-m-d|after_or_equal:from',
            'group' => 'nullable|in:day,month',
        ]);
        $range = $input['range'] ?? '30';
        $end = $range === 'custom' ? CarbonImmutable::parse($input['to'])->startOfDay() : CarbonImmutable::today();
        $start = $range === 'custom' ? CarbonImmutable::parse($input['from'])->startOfDay() : $end->subDays((int) $range - 1);
        if ($start->diffInDays($end) > 365 || $end->isFuture()) {
            throw ValidationException::withMessages(['from' => 'Choose up to 366 days, ending today or earlier.']);
        }
        $group = $input['group'] ?? ($start->diffInDays($end) > 90 ? 'month' : 'day');
        $version = Cache::get('quickreads.admin.report_version', '1');
        $key = 'quickreads.admin.stats.v1.'.$version.'.'.$start->format('Ymd').'.'.$end->format('Ymd').'.'.$group;
        $report = Cache::remember($key, 300, function () use ($start, $end, $group) {
            $db = self::db();
            $until = $end->addDay();
            $format = $group === 'month' ? '%Y-%m' : '%Y-%m-%d';
            $bucket = $db->getDriverName() === 'sqlite' ? "strftime('$format', created_at)" : "DATE_FORMAT(created_at, '$format')";
            $signups = $db->table('users')->where('created_at', '>=', $start)->where('created_at', '<', $until);
            $saves = $db->table('user_purchase_records')->where('created_at', '>=', $start)->where('created_at', '<', $until);
            $signupBuckets = (clone $signups)->selectRaw("$bucket as bucket, COUNT(*) as total")->groupBy('bucket')->pluck('total', 'bucket');
            $saveBuckets = (clone $saves)->selectRaw("$bucket as bucket, COUNT(*) as total")->groupBy('bucket')->pluck('total', 'bucket');
            $series = [];
            for ($date = $group === 'month' ? $start->startOfMonth() : $start; $date <= $end; $date = $group === 'month' ? $date->addMonth() : $date->addDay()) {
                $key = $date->format($group === 'month' ? 'Y-m' : 'Y-m-d');
                $series[] = ['label' => $date->format($group === 'month' ? 'M Y' : 'M j'), 'date' => $key,
                    'signups' => (int) ($signupBuckets[$key] ?? 0), 'saves' => (int) ($saveBuckets[$key] ?? 0)];
            }
            $records = $db->table('user_purchase_records as p')->where('p.created_at', '>=', $start)->where('p.created_at', '<', $until);
            // Aggregate the indexed date window before joining metadata. This
            // prevents MySQL from driving a report from every story/user and
            // scanning their complete save history before applying the dates.
            $storyTotals = (clone $records)->leftJoin('users as u', 'u.id', '=', 'p.user_id')
                ->select('p.story_id')->selectRaw('COUNT(*) as saves, COUNT(DISTINCT u.id) as readers')->groupBy('p.story_id');
            $userTotals = (clone $records)->select('p.user_id')->selectRaw('COUNT(*) as saves')->groupBy('p.user_id');
            return [
                'generated_at' => now(), 'series' => $series,
                'signups' => (clone $signups)->count(), 'saves' => (clone $saves)->count(),
                'active_users' => (clone $records)->leftJoin('users as u', 'u.id', '=', 'p.user_id')->distinct()->count('u.id'),
                'saved_stories' => (clone $records)->leftJoin('stories as s', 's.id', '=', 'p.story_id')->distinct()->count('s.id'),
                'top_stories' => $db->table('stories as s')->joinSub($storyTotals, 'totals', 'totals.story_id', '=', 's.id')
                    ->select('s.id', 's.slug', 's.title', 'totals.saves', 'totals.readers')
                    ->orderByDesc('totals.saves')->orderBy('s.id')->limit(10)->get(),
                'top_users' => $db->table('users as u')->joinSub($userTotals, 'totals', 'totals.user_id', '=', 'u.id')
                    ->select('u.id', 'u.first_name', 'u.last_name', 'u.email', 'totals.saves')
                    ->orderByDesc('totals.saves')->orderBy('u.id')->limit(10)->get(),
            ];
        });
        return compact('report', 'start', 'end', 'range', 'group');
    }
}
