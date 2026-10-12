<?php

namespace App\Projects\Quickreads;

use Illuminate\Support\Str;

class LatestAdditions
{
    public const LIMIT = 10;
    public const NAME = 'Latest additions';
    public const SLUG = 'latest-additions';
    // Real category IDs are positive. This virtual ID works on a fresh catalogue
    // without creating a category as a side effect of an API read.
    public const VIRTUAL_ID = 0;

    public static function reserved(string $name): bool
    {
        return in_array(Str::slug($name), [self::SLUG, 'latest-stories'], true);
    }

    public static function isCategory($category): bool
    {
        return $category && (self::reserved($category->slug) || self::reserved($category->category));
    }

    public static function categories($query, string $prefix = '', bool $exclude = false)
    {
        return $query->whereRaw(($exclude ? 'NOT ' : '')."(LOWER(TRIM({$prefix}slug)) IN (?, ?) OR LOWER(TRIM({$prefix}category)) IN (?, ?))",
            [self::SLUG, 'latest-stories', 'latest additions', 'latest stories']);
    }

    public static function matchesId($id): bool
    {
        if (!is_scalar($id) || !preg_match('/^[0-9]+$/D', (string) $id)) return false;
        return (string) $id === (string) self::VIRTUAL_ID
            || self::categories(Category::query()->where('id', $id))->exists();
    }

    private static function query()
    {
        return Story::formatted()->orderByDesc('stories.created_at')->orderByDesc('stories.id')->limit(self::LIMIT);
    }

    public static function stories()
    {
        return self::query()->get();
    }

    public static function legacyCategories()
    {
        $categories = Category::all();
        // Bound this count too; a system collection never contains more than ten.
        $count = self::query()->toBase()->reorder()->select('stories.id')->get()->count();
        $hasLatest = false;
        foreach ($categories as $category) {
            if (!self::isCategory($category)) continue;
            $category->stories_count = $count;
            $hasLatest = true;
        }
        if (!$hasLatest) {
            $categories->push((new Category)->forceFill(['id' => self::VIRTUAL_ID, 'slug' => self::SLUG,
                'category' => self::NAME, 'sorting_order' => -128, 'stories_count' => $count,
                'created_at' => null, 'updated_at' => null]));
        }
        return $categories;
    }
}
