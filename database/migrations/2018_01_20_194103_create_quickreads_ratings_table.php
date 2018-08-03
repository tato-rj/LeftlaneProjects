<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickreadsRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('quickreads')->create('ratings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('story_id');
            $table->unsignedInteger('user_id');
            $table->tinyInteger('score');
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
        Schema::connection('quickreads')->dropIfExists('ratings');
    }
}
