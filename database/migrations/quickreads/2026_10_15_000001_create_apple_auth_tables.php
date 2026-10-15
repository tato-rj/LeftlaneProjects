<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $schema = Schema::connection('quickreads');
        $schema->create('apple_auth_challenges', function (Blueprint $table) {
            $table->string('attempt_id', 36)->primary();
            $table->string('challenge_id', 43)->unique();
            $table->string('nonce', 43);
            $table->string('scope', 64);
            $table->string('status', 16)->default('pending');
            $table->string('payload_hash', 64)->nullable();
            $table->text('verified_grant')->nullable();
            $table->text('session_response')->nullable();
            $table->unsignedBigInteger('session_token_id')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('recovery_until')->nullable()->index();
            $table->timestamps();
        });
        $schema->create('apple_identities', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 64);
            $table->string('subject_hash', 64);
            $table->unsignedInteger('user_id')->index();
            $table->text('refresh_token')->nullable();
            $table->boolean('forwarding_enabled')->nullable();
            $table->timestamp('authorized_at');
            $table->timestamp('last_event_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['scope', 'subject_hash']);
        });
        $schema->create('apple_notifications', function (Blueprint $table) {
            $table->string('event_id', 64)->primary();
            $table->string('scope', 64);
            $table->string('subject_hash', 64);
            $table->string('type', 32);
            $table->timestamp('event_at');
            $table->timestamp('created_at')->index();
            $table->index(['scope', 'subject_hash', 'event_at'], 'apple_events_subject_time');
        });
        $schema->table('api_v2_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('apple_identity_id')->nullable()->index();
            $table->timestamp('apple_authorized_at')->nullable();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('quickreads');
        $schema->table('api_v2_tokens', function (Blueprint $table) {
            $table->dropIndex(['apple_identity_id']);
            $table->dropColumn(['apple_identity_id', 'apple_authorized_at']);
        });
        $schema->dropIfExists('apple_notifications');
        $schema->dropIfExists('apple_identities');
        $schema->dropIfExists('apple_auth_challenges');
    }
};
