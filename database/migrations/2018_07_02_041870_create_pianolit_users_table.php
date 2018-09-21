<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePianolitUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('pianolit')->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();

            $table->enum('age_range', ['under 13', '13 to 18', '18 to 25', '25 to 35', '35 to 45', '45 and up'])->nullable();
            $table->enum('experience', ['none', 'little', 'a lot'])->nullable();
            $table->unsignedInteger('preferred_piece_id')->nullable();
            $table->enum('occupation', ['student', 'teacher', 'music lover'])->nullable();
            
            $table->string('locale')->default('en_US');
            $table->string('password');
            $table->timestamp('trial_ends_at')->nullable();
            $table->rememberToken();
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
        Schema::connection('pianolit')->dropIfExists('users');
    }
}
