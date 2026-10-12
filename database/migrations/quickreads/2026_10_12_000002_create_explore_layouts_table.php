<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('quickreads')->create('explore_layouts', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->json('sections');
            $table->string('revision', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('quickreads')->dropIfExists('explore_layouts');
    }
};
