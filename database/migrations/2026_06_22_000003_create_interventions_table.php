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
        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expected_state_id');
            $table->text('ai_recommendation');
            $table->string('status')->default('proposed'); // proposed, active, resolved
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            // Relationships/Indexes
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
        Schema::dropIfExists('interventions');
    }
};
