<?php

namespace App\Projects\Quickreads\Api\V2;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage, Validator};
use Illuminate\Validation\Rule;
use App\Projects\Quickreads\Explore;

class Catalog
{
    public const SECTIONS = ['featured', 'latest', 'popular', 'under_10_minutes', 'classics', 'suggestions'];
    private array $artwork = [];
    private ?array $explore = null;

    public function beginRequest(): void
    {
        $this->artwork = [];
        $this->explore = null;
    }

    public function exploreSections(): array
    {
        return $this->explore ??= Explore::read()['sections'];
    }

    public function db()
    {
        return DB::connection('quickreads');
    }

    public function query(): Builder
    {
        // Do not inherit the legacy model's eager biography/ratings/comments loads.
        return $this->db()->table('stories as s')
            ->leftJoin('authors as a', 'a.id', '=', 's.author_id')
            ->leftJoin('categories as c', 'c.id', '=', 's.category_id')
            ->select(['s.id', 's.slug', 's.title', 's.summary', 's.time', 's.is_classic',
                's.created_at', 's.updated_at', 'a.id as author_id', 'a.name as author_name',
                'c.id as category_id', 'c.slug as category_key', 'c.category as category_name']);
    }

    public function criteria(Request $request): array
    {
        $input = Validator::make($request->query(), [
            'page' => 'sometimes|required|integer|min:1|max:1000000',
            'per_page' => 'sometimes|required|integer|min:1|max:100',
            'q' => 'nullable|string|max:200',
            'author_id' => 'sometimes|required|integer|min:1|max:4294967295',
            'category_id' => 'sometimes|required|integer|min:1|max:4294967295',
            'exclude_id' => 'sometimes|required|integer|min:1|max:4294967295',
            'min_minutes' => 'sometimes|required|numeric|min:0|max:1000000',
            'max_minutes' => 'sometimes|required|numeric|min:0|max:1000000',
            'sort' => ['sometimes', 'required', Rule::in(['editorial', 'title', 'latest', 'popular'])],
            'section' => ['sometimes', 'required', 'string', 'max:80'],
        ])->validate();
        if (isset($input['section']) && !in_array($input['section'], array_merge(self::SECTIONS, array_column($this->exploreSections(), 'id')), true)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['section' => ['Choose an available Explore section.']]);
        }
        if (isset($input['min_minutes'], $input['max_minutes']) && $input['min_minutes'] > $input['max_minutes']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['max_minutes' => ['Must be greater than or equal to min_minutes.']]);
        }
        if (isset($input['section'], $input['sort'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['sort' => ['Section ordering cannot be overridden.']]);
        }
        foreach (['author_id' => 'authors', 'category_id' => 'categories', 'exclude_id' => 'stories'] as $key => $table) {
            if (isset($input[$key])) {
                abort_unless($this->db()->table($table)->where('id', $input[$key])->exists(), 404);
                $input[$key] = (string) $input[$key];
            }
        }
        $input['q'] = trim($input['q'] ?? '');
        $input['page'] = (int) ($input['page'] ?? 1);
        $input['per_page'] = (int) ($input['per_page'] ?? 20);
        foreach (['min_minutes', 'max_minutes'] as $key) {
            if (isset($input[$key])) $input[$key] = (float) $input[$key];
        }
        if (!isset($input['section'])) $input['sort'] = $input['sort'] ?? 'editorial';
        return $input;
    }

    public function filtered(array $input): Builder
    {
        $query = $this->query();
        foreach (['author_id' => 'a.id', 'category_id' => 'c.id'] as $key => $column) {
            if (isset($input[$key])) $query->where($column, $input[$key]);
        }
        if (isset($input['exclude_id'])) $query->where('s.id', '<>', $input['exclude_id']);
        if (isset($input['q']) && $input['q'] !== '') {
            // ! is a portable explicit LIKE escape: %, _ and ! are literal search characters.
            $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($input['q'], 'UTF-8')).'%';
            $query->where(function ($query) use ($term) {
                $query->whereRaw("LOWER(s.title) LIKE ? ESCAPE '!'", [$term])
                    ->orWhereRaw("LOWER(a.name) LIKE ? ESCAPE '!'", [$term]);
            });
        }
        if (isset($input['min_minutes']) || isset($input['max_minutes'])) {
            // time is a legacy string; do not treat nonnumeric values as zero.
            $query->whereRaw("s.time REGEXP '^[0-9]+([.][0-9]+)?$'");
            if (isset($input['min_minutes'])) $query->whereRaw('CAST(s.time AS DECIMAL(12,4)) >= ?', [$input['min_minutes']]);
            if (isset($input['max_minutes'])) $query->whereRaw('CAST(s.time AS DECIMAL(12,4)) <= ?', [$input['max_minutes']]);
        }
        $section = $input['section'] ?? null;
        if ($section !== null) {
            $definition = collect($this->exploreSections())->firstWhere('id', $section)
                ?? collect(Explore::defaults())->firstWhere('id', $section);
            $ids = $definition ? Explore::ids($definition) : [];
            return Explore::orderIds($query->whereIn('s.id', $ids), $ids, 's.id');
        }
        $sort = $input['sort'] ?? 'editorial';
        if ($sort === 'popular') {
            $query->orderByDesc($this->db()->table('user_purchase_records as f')
                ->selectRaw('COUNT(DISTINCT f.user_id)')->whereColumn('f.story_id', 's.id'));
        } elseif ($sort === 'latest') {
            $query->orderByDesc('s.created_at');
        } elseif ($sort === 'title') {
            $query->orderBy('s.title');
        } else {
            $query->orderByRaw('c.sorting_order IS NULL')->orderBy('c.sorting_order');
        }
        return $query->orderBy('s.id');
    }

    public function image($story): ?array
    {
        if (array_key_exists($story->slug, $this->artwork)) return $this->artwork[$story->slug];
        if (!preg_match('/^[a-zA-Z0-9_-]+$/D', $story->slug ?? '')) return null;
        $disk = Storage::disk('public');
        foreach (['jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'] as $extension => $mime) {
            $path = 'stories/'.$story->slug.'/'.$story->slug.'.'.$extension;
            if (!$disk->exists($path)) continue;
            $local = $disk->path($path);
            $dimensions = @getimagesize($local);
            if (!$dimensions || !in_array($dimensions['mime'] ?? '', ['image/jpeg', 'image/png', 'image/webp'], true)) continue;
            // The legacy uploader always used .jpeg without converting bytes.
            $mime = $dimensions['mime'];
            $revision = hash_file('sha256', $local);
            return $this->artwork[$story->slug] = ['cover_url' => secure_asset('storage/'.$path).'?v='.$revision,
                'thumbnail_url' => null, 'width' => $dimensions[0], 'height' => $dimensions[1],
                'content_type' => $mime, 'revision' => $revision];
        }
        return $this->artwork[$story->slug] = null;
    }

    public function summary($story): array
    {
        $data = ['id' => (string) $story->id, 'title' => $story->title, 'summary' => $story->summary,
            'reading_minutes' => preg_match('/^[0-9]+([.][0-9]+)?$/D', $story->time ?? '') ? (float) $story->time : null,
            'is_classic' => (bool) $story->is_classic,
            'author' => $story->author_id ? ['id' => (string) $story->author_id, 'name' => $story->author_name] : null,
            'categories' => $story->category_id ? [['id' => (string) $story->category_id,
                'key' => $story->category_key, 'name' => $story->category_name]] : [],
            'artwork' => $this->image($story)];
        $data['metadata_revision'] = hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $data;
    }

    public function page(Builder $query, array $input, string $path, callable $serialize): array
    {
        $total = (clone $query)->count();
        $rows = $query->forPage($input['page'], $input['per_page'])->get();
        $next = $input['page'] * $input['per_page'] < $total ? $input['page'] + 1 : null;
        $url = $next ? $path.'?'.http_build_query(array_merge($input, ['page' => $next]), '', '&', PHP_QUERY_RFC3986) : null;
        return ['data' => $rows->map($serialize)->values()->all(),
            'meta' => ['page' => $input['page'], 'per_page' => $input['per_page'], 'total' => $total,
                'has_more' => $next !== null, 'next_page' => $next, 'criteria' => (object) array_diff_key($input, array_flip(['page', 'per_page']))],
            'links' => ['next' => $url]];
    }
}
