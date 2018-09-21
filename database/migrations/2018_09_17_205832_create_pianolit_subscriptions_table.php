<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePianoLitSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('pianolit')->create('subscriptions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('bundle_id');
            $table->string('application_version');
            $table->json('in_app');
            $table->string('original_application_version');
            $table->timestamp('receipt_creation_date')->nullable();
            $table->timestamp('receipt_creation_date_pst')->nullable();
            $table->unsignedInteger('receipt_creation_date_ms')->nullable();
            $table->string('version_external_identifier');
            $table->timestamp('original_purchase_date')->nullable();
            $table->timestamp('original_purchase_date_pst')->nullable();
            $table->unsignedInteger('original_purchase_date_ms')->nullable();
            $table->timestamp('request_date')->nullable();
            $table->timestamp('request_date_pst')->nullable();
            $table->unsignedInteger('request_date_ms')->nullable();
            $table->string('receipt_type');
            $table->unsignedInteger('download_id');
            $table->unsignedInteger('adam_id');
            $table->unsignedInteger('app_item_id');
            $table->timestamp('cancellation_date')->nullable();
            $table->timestamp('cancellation_reason')->nullable();
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
        Schema::connection('pianolit')->dropIfExists('subscriptions');
    }
}
