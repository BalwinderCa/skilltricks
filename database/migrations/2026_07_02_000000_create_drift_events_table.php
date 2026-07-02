<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drift_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expected_state_id');
            $table->string('drift_type'); // None, Timeline Drift, Capacity Drift, Priority Drift, Dependency Blocked
            $table->decimal('magnitude', 5, 2)->nullable(); // 0.00–1.00 shortfall vs target
            $table->string('severity')->nullable(); // Low, Medium, High
            $table->timestamp('detected_at');
            $table->timestamps();

            $table->foreign('expected_state_id')
                ->references('id')
                ->on('expected_states')
                ->onDelete('cascade');

            $table->index(['expected_state_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drift_events');
    }
};
