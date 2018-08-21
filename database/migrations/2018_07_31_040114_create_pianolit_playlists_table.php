<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePianolitPlaylistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('pianolit')->create('playlists', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection('pianolit')->create('piece_playlist', function (Blueprint $table) {
            $table->unsignedInteger('piece_id');
            $table->unsignedInteger('playlist_id');
            $table->primary(['piece_id', 'playlist_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('pianolit')->dropIfExists('playlists');
        Schema::connection('pianolit')->dropIfExists('piece_playlist');
    }
}
