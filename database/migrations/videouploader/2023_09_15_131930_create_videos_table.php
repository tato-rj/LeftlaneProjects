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
            $table->unsignedInteger('piece_id');
            $table->string('temp_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('mimeType')->nullable();
            $table->unsignedInteger('original_size')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('abandoned_at')->nullable();
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
