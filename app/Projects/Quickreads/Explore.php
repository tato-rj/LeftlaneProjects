<?php

namespace App\Projects\Quickreads;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Explore
{
    public const SOURCES = ['manual', 'daily', 'latest', 'popular', 'quick', 'classics', 'category', 'author'];
    public const MAX_SECTIONS = 20;
    public const MAX_STORIES = 24;

    public static function defaults(): array
    {
        $sections = [
            ['featured', "Today's pick", 'daily', 1], ['latest', 'Latest stories', 'latest', 10],
            ['popular', 'Most popular', 'popular', 8], ['under_10_minutes', 'Under 10 minutes', 'quick', 8],
            ['classics', 'Classics', 'classics', 6], ['suggestions', 'Not sure what to read?', 'daily', 6],
        ];
        return array_map(fn ($s) => ['id' => $s[0], 'title' => $s[1], 'source' => $s[2], 'limit' => $s[3],
            'story_ids' => [], 'category_id' => null, 'author_id' => null, 'max_minutes' => 10], $sections);
    }

    public static function db()
    {
        return DB::connection('quickreads');
    }

    public static function read(): array
    {
        $layout = self::db()->table('explore_layouts')->find(1);
        return ['revision' => $layout->revision ?? 'initial',
            'sections' => $layout ? json_decode($layout->sections, true, 512, JSON_THROW_ON_ERROR) : self::defaults()];
    }

    public static function validate(Request $request, bool $publishing): array
    {
        $input = Validator::make($request->all(), [
            'sections' => 'required|array|min:1|max:'.self::MAX_SECTIONS,
            'sections.*' => 'required|array:id,title,source,limit,story_ids,category_id,author_id,max_minutes',
            'sections.*.id' => ['required', 'string', 'distinct', 'regex:/^[a-z0-9][a-z0-9_-]{0,79}$/D'],
            'sections.*.title' => $publishing ? 'required|string|max:80' : 'present|nullable|string|max:80',
            'sections.*.source' => ['required', Rule::in(self::SOURCES)],
            'sections.*.limit' => 'required|integer|min:1|max:'.self::MAX_STORIES,
            'sections.*.story_ids' => 'present|array|max:'.self::MAX_STORIES,
            'sections.*.story_ids.*' => 'integer|min:1|max:4294967295',
            'sections.*.category_id' => 'nullable|integer|min:1|max:4294967295',
            'sections.*.author_id' => 'nullable|integer|min:1|max:4294967295',
            'sections.*.max_minutes' => 'required|numeric|min:0.1|max:1440',
        ])->validate();
        $sections = array_values($input['sections']);
        if ($sections[0]['id'] !== 'featured' || !in_array($sections[0]['source'], ['daily', 'manual'], true)) {
            throw ValidationException::withMessages(['sections' => 'Keep Today’s pick first, using a daily pick or one hand-picked story.']);
        }
        $allIds = collect($sections)->pluck('story_ids')->flatten()->unique()->values()->all();
        $existing = $allIds ? self::base()->whereIn('s.id', $allIds)->pluck('s.id')->map('strval')->all() : [];
        foreach ($sections as $index => &$section) {
            $section['title'] = trim($section['title'] ?? '');
            if ($section['title'] === '') {
                if ($publishing) throw ValidationException::withMessages(["sections.$index.title" => 'Give each section a heading.']);
                $section['title'] = 'Untitled section';
            }
            $section['limit'] = $section['id'] === 'featured' ? 1 : ($section['source'] === 'latest' ? 10 : (int) $section['limit']);
            $section['story_ids'] = array_values(array_unique(array_map('strval', $section['story_ids'])));
            $section['max_minutes'] = (float) $section['max_minutes'];
            if ($section['source'] === 'manual') {
                // Deleted/orphaned stories cannot be read by the legacy app.
                $section['story_ids'] = array_values(array_intersect($section['story_ids'], $existing));
                if ($section['id'] === 'featured') $section['story_ids'] = array_slice($section['story_ids'], 0, 1);
                $section['limit'] = $section['id'] === 'featured' ? 1 : max(1, count($section['story_ids']));
                if ($publishing && !$section['story_ids']) throw ValidationException::withMessages(["sections.$index.story_ids" => 'Choose at least one available story for '.$section['title'].'.']);
            }
            if (in_array($section['source'], ['category', 'author'], true)) {
                $field = $section['source'].'_id';
                $table = $section['source'] === 'category' ? 'categories' : 'authors';
                $query = self::db()->table($table)->where('id', $section[$field] ?? 0);
                if ($table === 'categories') LatestAdditions::categories($query, '', true);
                if ($publishing && !$query->exists()) throw ValidationException::withMessages(["sections.$index.$field" => 'Choose an available '.$section['source'].' for '.$section['title'].'.']);
            }
        }
        unset($section);
        return $sections;
    }

    private static function base()
    {
        return self::db()->table('stories as s')->join('authors as a', 'a.id', '=', 's.author_id')
            ->join('categories as c', 'c.id', '=', 's.category_id');
    }

    public static function orderIds($query, array $ids, string $column)
    {
        if (!$ids) return $query->whereRaw('1 = 0');
        $case = 'CASE '.$column;
        foreach ($ids as $index => $id) $case .= ' WHEN ? THEN '.$index;
        return $query->orderByRaw($case.' END', $ids);
    }

    public static function ids(array $section): array
    {
        $query = self::base();
        $source = $section['source'];
        $limit = min(self::MAX_STORIES, max(1, (int) $section['limit']));
        if ($source === 'manual') {
            $ids = array_slice(array_values(array_unique($section['story_ids'])), 0, $limit);
            self::orderIds($query->whereIn('s.id', $ids), $ids, 's.id');
        } elseif ($source === 'daily') {
            $count = (clone $query)->count();
            if (!$count) return [];
            $seed = (int) now('UTC')->format('Ymd') + ($section['id'] === 'featured' ? 0 : 17);
            $offset = $seed % $count;
            $ids = (clone $query)->orderBy('s.id')->offset($offset)->limit($limit)->pluck('s.id')->all();
            if (count($ids) < min($limit, $count)) {
                $ids = array_merge($ids, (clone $query)->orderBy('s.id')->limit(min($limit, $count) - count($ids))->pluck('s.id')->all());
            }
            return self::dailyOrder($ids, $section['id']);
        } else {
            if ($source === 'quick') $query->whereRaw("s.time REGEXP '^[0-9]+([.][0-9]+)?$'")->whereRaw('CAST(s.time AS DECIMAL(12,4)) <= ?', [$section['max_minutes']]);
            if ($source === 'classics') $query->where('s.is_classic', true);
            if ($source === 'category') $query->where('s.category_id', $section['category_id'] ?? 0);
            if ($source === 'author') $query->where('s.author_id', $section['author_id'] ?? 0);
            if ($source === 'popular') $query->orderByDesc(self::db()->table('user_purchase_records as p')->selectRaw('COUNT(DISTINCT p.user_id)')->whereColumn('p.story_id', 's.id'));
            $query->orderByDesc('s.created_at')->orderByDesc('s.id');
        }
        $ids = $query->limit($source === 'latest' ? 10 : $limit)->pluck('s.id')->map('strval')->all();
        return $source === 'latest' ? $ids : self::dailyOrder($ids, $section['id']);
    }

    private static function dailyOrder(array $ids, string $sectionId): array
    {
        // Shared by admin previews, both Explore APIs and section pagination.
        // Hashing each ID keeps the order stable all day without stored shuffles
        // or changing PHP's global random state. Curation still controls membership.
        $seed = now('UTC')->format('Y-m-d').':'.$sectionId.':';
        $ranks = [];
        foreach ($ids as $id) $ranks[(string) $id] = hash('sha256', $seed.$id);
        usort($ids, fn ($a, $b) => strcmp($ranks[$a], $ranks[$b]) ?: strcmp((string) $a, (string) $b));
        return array_map('strval', $ids);
    }

    public static function collections(array $sections)
    {
        $ids = array_map(fn ($section) => self::ids($section), $sections);
        $all = array_values(array_unique(array_merge(...$ids)));
        $stories = $all ? Story::formatted()->whereIn('stories.id', $all)->get()->keyBy('id') : collect();
        return collect($sections)->map(fn ($section, $i) => collect(['title' => $section['title'],
            'collection' => collect($ids[$i])->map(fn ($id) => $stories->get($id))->filter()->values()]));
    }

    public static function preview(array $sections): array
    {
        $collections = self::collections($sections);
        return collect($sections)->map(fn ($section, $i) => ['id' => $section['id'],
            'stories' => $collections[$i]['collection']->map(fn ($story) => self::card($story))->all()])->all();
    }

    public static function card($story): array
    {
        $path = parse_url($story->story_filename ?? '', PHP_URL_PATH);
        return ['id' => (string) $story->id, 'title' => $story->title, 'author' => $story->author ?? '',
            'time' => (string) $story->time, 'summary' => \Illuminate\Support\Str::limit($story->summary ?? '', 220),
            'image' => $path ? asset(ltrim($path, '/')) : null];
    }
}
