<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $schema = Schema::connection('quickreads');
        $schema->create('story_activity', function (Blueprint $table) {
            $table->id();
            $table->string('reader_key', 64);
            $table->string('reader_kind', 7);
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->unsignedInteger('story_id')->index();
            $table->string('reading_status', 16)->nullable();
            $table->boolean('saved')->nullable();
            $table->timestamp('reading_observed_at', 3)->nullable();
            $table->timestamp('saved_observed_at', 3)->nullable();
            $table->string('reading_mutation_id', 36)->nullable();
            $table->string('saved_mutation_id', 36)->nullable();
            $table->timestamps();
            $table->unique(['reader_key', 'story_id']);
            $table->index('updated_at');
        });
        $schema->create('story_activity_mutations', function (Blueprint $table) {
            $table->id();
            $table->string('reader_key', 64);
            $table->string('mutation_id', 36);
            $table->string('payload_hash', 64);
            $table->boolean('applied')->default(false);
            $table->timestamp('created_at')->index();
            $table->unique(['reader_key', 'mutation_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('quickreads')->dropIfExists('story_activity_mutations');
        Schema::connection('quickreads')->dropIfExists('story_activity');
    }
};
