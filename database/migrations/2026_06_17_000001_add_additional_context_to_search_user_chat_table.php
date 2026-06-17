<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Provisions the additional_context column that AiChatController previously
     * created at request time via raw ALTER TABLE statements. Guarded with
     * hasColumn() so it is safe on environments where the column already exists.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('search_user_chat', 'additional_context')) {
            Schema::table('search_user_chat', function (Blueprint $table) {
                $table->text('additional_context')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('search_user_chat', 'additional_context')) {
            Schema::table('search_user_chat', function (Blueprint $table) {
                $table->dropColumn('additional_context');
            });
        }
    }
};
