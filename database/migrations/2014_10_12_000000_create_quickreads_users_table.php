<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickreadsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('quickreads')->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender')->nullable();
            $table->string('email')->unique();
            $table->string('locale')->nullable();
            $table->string('facebook_id')->nullable();
            $table->string('password');
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
        Schema::connection('quickreads')->dropIfExists('users');
    }
}
