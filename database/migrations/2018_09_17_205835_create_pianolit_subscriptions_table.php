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
            // CREATED ON SERVER
            $table->string('original_transaction_id')->index();
            $table->text('latest_receipt');
            $table->string('password');
            // UPDATED ON EVENT NOTIFICATION
            $table->string('environment')->nullable();
            $table->string('auto_renew_product_id')->nullable();
            $table->string('notification_type')->nullable();
            $table->boolean('auto_renew_status')->nullable();
            $table->string('auto_renew_adam_id')->nullable();
            $table->json('latest_receipt_info')->nullable();
            $table->tinyInteger('expiration_intent')->nullable();
            $table->timestamp('cancellation_date')->nullable();
            $table->string('web_order_line_item_id')->nullable();

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
