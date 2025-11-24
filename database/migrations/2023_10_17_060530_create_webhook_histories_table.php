<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebhookHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('webhook_histories', function (Blueprint $table) {
            $table->id();
            $table->string('gateway')->nullable();
            $table->string('webhook_id')->nullable();
            $table->string('create_time')->nullable();
            $table->string('resource_type')->nullable();
            $table->string('event_type')->nullable();
            $table->string('summary')->nullable();
            $table->string('resource_id')->nullable();
            $table->string('resource_state')->nullable();
            $table->string('parent_payment')->nullable();
            $table->string('amount_total')->nullable();
            $table->string('amount_currency')->nullable();
            $table->text('incoming_json')->nullable();
            $table->string('status')->nullable();
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
        Schema::dropIfExists('webhook_histories');
    }
}
