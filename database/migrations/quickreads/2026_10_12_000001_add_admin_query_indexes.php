<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $indexes = [
        'users' => ['qr_users_created_id' => ['created_at', 'id'], 'qr_users_slug' => ['slug']],
        'stories' => ['qr_stories_created_id' => ['created_at', 'id'], 'qr_stories_author' => ['author_id'], 'qr_stories_category' => ['category_id'], 'qr_stories_slug' => ['slug']],
        'authors' => ['qr_authors_slug' => ['slug']],
        'categories' => ['qr_categories_slug' => ['slug']],
        'user_purchase_records' => ['qr_saves_created_story_user' => ['created_at', 'story_id', 'user_id'], 'qr_saves_user_created_id' => ['user_id', 'created_at', 'id'], 'qr_saves_story_user' => ['story_id', 'user_id']],
        'ratings' => ['qr_ratings_user_created_id' => ['user_id', 'created_at', 'id'], 'qr_ratings_story' => ['story_id']],
        'comments' => ['qr_comments_user_created_id' => ['user_id', 'created_at', 'id'], 'qr_comments_story' => ['story_id']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            Schema::connection('quickreads')->table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $name => $columns) $blueprint->index($columns, $name);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            Schema::connection('quickreads')->table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $name => $columns) $blueprint->dropIndex($name);
            });
        }
    }
};
