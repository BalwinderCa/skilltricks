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
        Schema::create('expected_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('search_user_chat_id');
            $table->string('role');
            $table->text('recommended_action');
            $table->string('decision')->nullable(); // act_on_it, review_in_detail, not_viable
            $table->text('success_metric')->nullable();
            $table->string('target_value')->nullable();
            $table->date('target_date')->nullable();
            $table->boolean('resources_committed')->default(false);
            $table->timestamps();

            // Set up relationship/indexes
            $table->foreign('search_user_chat_id')
                  ->references('id')
                  ->on('search_user_chat')
                  ->onDelete('cascade');
            
            $table->index(['search_user_chat_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('expected_states');
    }
};
