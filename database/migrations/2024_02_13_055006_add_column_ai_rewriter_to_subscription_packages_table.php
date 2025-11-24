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
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->tinyInteger('allow_ai_rewriter')->default(0);
            $table->tinyInteger('show_ai_rewriter')->default(0);
  
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->dropColumn(['allow_ai_rewriter']);
            $table->dropColumn(['show_ai_rewriter']);
        });
    }
};
