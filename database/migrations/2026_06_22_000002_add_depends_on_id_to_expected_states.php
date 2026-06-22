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
        Schema::table('expected_states', function (Blueprint $table) {
            $table->unsignedBigInteger('depends_on_id')->nullable()->after('search_user_chat_id');

            // Setup self-referencing foreign key constraint
            $table->foreign('depends_on_id')
                  ->references('id')
                  ->on('expected_states')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('expected_states', function (Blueprint $table) {
            $table->dropForeign(['depends_on_id']);
            $table->dropColumn('depends_on_id');
        });
    }
};
