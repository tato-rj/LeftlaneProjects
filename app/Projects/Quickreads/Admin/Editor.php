<?php

namespace App\Projects\Quickreads\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Projects\Quickreads\LatestAdditions;

class Editor
{
    public static function slug(string $table, string $name): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        for ($n = 2; Queries::db()->table($table)->where('slug', $slug)->exists(); $n++) $slug = $base.'-'.$n;
        return $slug;
    }

    public static function story(Request $request, $story = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('quickreads.stories', 'title')->ignore($story->id ?? null)],
            'summary' => 'required|string|max:10000', 'text' => 'required|string|max:2000000',
            'author_id' => 'required|integer|exists:quickreads.authors,id',
            'category_id' => ['required', 'integer', Rule::exists('quickreads.categories', 'id')->where(fn ($query) => LatestAdditions::categories($query, '', true))],
            'time' => 'required|numeric|min:0.1|max:1440',
            'is_classic' => 'nullable|boolean', 'image' => 'nullable|image|mimes:jpeg,png,webp|max:5120',
        ], ['category_id.exists' => 'Choose a regular category. Latest additions is an automatic collection.']);
        unset($data['image']);
        $data['time'] = (string) max(1, (int) round((float) $data['time']));
        $data['cost'] = 'Free';
        $data['is_classic'] = $request->boolean('is_classic');
        return $data;
    }

    public static function changed(): void
    {
        Cache::forget('quickreads.admin.dashboard.v1');
        // Old report entries expire naturally; a new namespace also prevents
        // an in-flight report from restoring deleted records to the next view.
        Cache::forever('quickreads.admin.report_version', (string) Str::uuid());
        // Existing app discovery uses this cache key. Editors should see their changes in the app.
        Cache::forget('quickreads-explore-6');
    }
}
