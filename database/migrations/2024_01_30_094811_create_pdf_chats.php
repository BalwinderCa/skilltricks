<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePdfChats extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pdf_chats', function (Blueprint $table) {
            $table->id();
            $table->string('chat_code')->unique();
            $table->integer('prompt_tokens')->default(0);
            $table->integer("total_words")->default(0);
            $table->integer("total_tokens")->default(0);
            $table->foreignId("user_id")->constrained();
            $table->integer("total_conversations")->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pdf_chats');
    }
}
