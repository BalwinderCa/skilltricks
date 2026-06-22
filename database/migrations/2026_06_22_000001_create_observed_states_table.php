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
        Schema::create('observed_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expected_state_id');
            $table->string('actual_value')->nullable();
            $table->string('status'); // e.g. Scheduled, In Progress, Complete, Blocked
            $table->date('observation_date');
            $table->string('source')->default('Manual');
            $table->text('status_notes')->nullable();
            $table->timestamps();

            // Set up relationship/indexes
            $table->foreign('expected_state_id')
                  ->references('id')
                  ->on('expected_states')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('observed_states');
    }
};
