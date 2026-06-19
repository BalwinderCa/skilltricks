<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Running total of AI tokens spent on a chat across ALL of its sections
     * (first message, strategy/scenario regenerations, alignment brief, action
     * table). Surfaced in the chat header so users see the chat's lifetime cost,
     * not just the last request.
     */
    public function up()
    {
        Schema::table('search_user_chat', function (Blueprint $table) {
            $table->unsignedBigInteger('total_tokens')->default(0)->after('user_id');
        });
    }

    public function down()
    {
        Schema::table('search_user_chat', function (Blueprint $table) {
            $table->dropColumn('total_tokens');
        });
    }
};
