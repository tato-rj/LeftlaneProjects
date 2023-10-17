<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('videouploader')->create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('origin');
            $table->unsignedInteger('piece_id');
            $table->unsignedInteger('user_id');
            $table->string('user_email');
            $table->string('temp_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('thumb_path')->nullable();
            $table->string('mimeType')->nullable();
            $table->string('notes')->nullable();
            $table->unsignedInteger('original_size')->nullable();
            $table->unsignedInteger('compressed_size')->nullable();
            $table->string('original_dimensions')->nullable();
            $table->string('compressed_dimensions')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('notification_received_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('videouploader')->dropIfExists('videos');
    }
};
