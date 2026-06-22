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
        if (!Schema::hasTable('search_user_chat')) {
            Schema::create('search_user_chat', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->longText('answers')->nullable();
                $table->longText('chat_role_categories')->nullable();
                $table->longText('categories')->nullable();
                $table->longText('subcategories')->nullable();
                $table->string('questionmenuid')->nullable();
                $table->longText('search')->nullable();
                $table->longText('response')->nullable();
                $table->integer('status1')->default(0);
                $table->integer('status2')->default(0);
                $table->timestamps();
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
        Schema::dropIfExists('search_user_chat');
    }
};
