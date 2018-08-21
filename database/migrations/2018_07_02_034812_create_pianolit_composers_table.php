<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePianolitComposersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('pianolit')->create('composers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('biography');
            $table->text('curiosity')->nullable();
            $table->enum('period', periods());
            $table->unsignedInteger('country_id')->nullable();
            $table->date('date_of_birth');
            $table->date('date_of_death');
            $table->unsignedInteger('creator_id')->nullable();
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
        Schema::dropIfExists('composers');
    }
}
