<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePdfChatConversations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pdf_chat_conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId("user_id")->constrained();
            $table->foreignId("pdf_chat_id")->constrained();

            $table->string('pdf_file')->nullable();
            $table->longText('prompt');
            $table->longText('pdf_content')->comment("PDF Inside Text");
            $table->longText('pdf_embedding_content');
            $table->longText('prompt_embedding_content')->nullable();
            $table->longText('ai_response')->nullable();

            $table->integer('words')->default(0);

            $table->integer('prompt_tokens')->default(0);
            $table->integer("total_used_tokens")->default(0);
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
        Schema::dropIfExists('pdf_chat_conversations');
    }
}
