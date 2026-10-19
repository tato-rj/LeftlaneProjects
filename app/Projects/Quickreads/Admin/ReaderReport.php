<?php

namespace App\Projects\Quickreads\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReaderReport
{
    public static function appleIdentity(string $userColumn)
    {
        return Queries::db()->table('apple_identities as apple')->whereColumn('apple.user_id', $userColumn);
    }

    public static function withProvider($query, string $userColumn)
    {
        // Provider membership comes from the verified identity, never an email
        // suffix, a legacy alias, or the presence/absence of a password.
        return $query->selectSub(self::appleIdentity($userColumn)->selectRaw('1')->limit(1), 'is_apple');
    }

    public static function filterProvider($query, ?string $provider, string $userColumn): void
    {
        if ($provider) $query->addWhereExistsQuery(self::appleIdentity($userColumn)->selectRaw('1'), 'and', $provider === 'other');
    }

    public static function summary(): array
    {
        $query = Queries::db()->table('users as u');
        $counts = fn ($query) => $query->selectRaw('COUNT(*) as total')
            ->selectRaw('COALESCE(SUM(CASE WHEN u.created_at >= ? THEN 1 ELSE 0 END), 0) as recent', [now()->subDays(29)->startOfDay()])->first();
        $all = $counts(clone $query);
        self::filterProvider($query, 'apple', 'u.id');
        $apple = $counts($query);
        return ['users' => (int) $all->total, 'new_users' => (int) $all->recent,
            'apple_users' => (int) $apple->total, 'new_apple_users' => (int) $apple->recent];
    }

    public static function decorate(Collection $users): Collection
    {
        // Aggregate only the visible page's IDs in one indexed query. No
        // per-reader SQL, story bodies, or provider credentials are fetched.
        $activity = $users->isEmpty() ? collect() : ActivityReport::active()
            ->where('activity.reader_kind', 'account')->whereIn('activity.user_id', $users->pluck('id'))
            ->select('activity.user_id')->selectRaw(ActivityReport::totalsSql())
            ->groupBy('activity.user_id')->get()->keyBy('user_id');
        return $users->each(function ($user) use ($activity) {
            $user->display_name = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'Reader #'.$user->id;
            $user->activity = $activity->get($user->id) ?? (object) [
                'reports' => 0, 'reading' => 0, 'finished' => 0, 'saved' => 0, 'last_received_at' => null];
        });
    }

    public static function directory(Request $request): array
    {
        $criteria = array_merge(Queries::criteria($request), $request->validate([
            'provider' => 'nullable|in:apple,other', 'status' => 'nullable|in:reading,finished,saved,not_started,unreported',
        ]));
        $query = self::withProvider(Queries::db()->table('users as u')
            ->select('u.id', 'u.first_name', 'u.last_name', 'u.email', 'u.locale', 'u.created_at'), 'u.id')
            ->selectSub(Queries::db()->table('user_purchase_records as p')->selectRaw('COUNT(*)')->whereColumn('p.user_id', 'u.id'), 'saves');
        $fullName = Queries::db()->getDriverName() === 'sqlite'
            ? "(u.first_name || ' ' || u.last_name)" : "CONCAT_WS(' ', u.first_name, u.last_name)";
        Queries::search($query, [$fullName, 'u.email'], $criteria['q'] ?? null);
        self::filterProvider($query, $criteria['provider'] ?? null, 'u.id');
        if (!empty($criteria['activity'])) {
            $query->addWhereExistsQuery(Queries::db()->table('user_purchase_records as p')->selectRaw('1')->whereColumn('p.user_id', 'u.id'), 'and', $criteria['activity'] === 'none');
        }
        if (!empty($criteria['status'])) {
            $activity = ActivityReport::active()->selectRaw('1')->where('activity.reader_kind', 'account')->whereColumn('activity.user_id', 'u.id');
            ActivityReport::filterStatus($activity, $criteria['status'] === 'unreported' ? null : $criteria['status']);
            $query->addWhereExistsQuery($activity, 'and', $criteria['status'] === 'unreported');
        }
        $sort = $criteria['sort'] ?? 'newest';
        $sort === 'name' ? $query->orderBy('u.first_name')->orderBy('u.last_name') : $query->orderBy('u.created_at', $sort === 'oldest' ? 'asc' : 'desc');
        $users = Queries::page($query->orderByDesc('u.id'), $criteria);
        self::decorate($users->getCollection());
        $readerSummary = self::summary();
        return compact('users', 'criteria', 'readerSummary');
    }
}
