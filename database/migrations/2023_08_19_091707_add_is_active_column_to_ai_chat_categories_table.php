<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveColumnToAiChatCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ai_chat_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_chat_categories', 'is_active')) { 
                $table->tinyInteger('is_active')->default(1);
            }
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ai_chat_categories', function (Blueprint $table) {
            //
        });
    }
}
