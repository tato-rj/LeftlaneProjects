<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePianolitTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('pianolit')->create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection('pianolit')->create('piece_tag', function (Blueprint $table) {
            $table->unsignedInteger('piece_id');
            $table->unsignedInteger('tag_id');
            $table->primary(['piece_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tags');
        Schema::dropIfExists('piece_tag');
    }
}
