<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Middle-out initiatives (Features spec, phase 9): the parent priority, the match, the approval. */
    public function up(): void
    {
        Schema::table('search_user_chat', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_chat_id')->nullable()->index();
            $table->unsignedTinyInteger('correlation_score')->nullable();
            $table->string('correlation_reason', 300)->nullable();
            $table->timestamp('approval_requested_at')->nullable();
            $table->unsignedBigInteger('approval_decided_by')->nullable();
            $table->timestamp('approval_decided_at')->nullable();
            $table->text('approval_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('search_user_chat', function (Blueprint $table) {
            $table->dropIndex(['parent_chat_id']);
            $table->dropColumn(['parent_chat_id', 'correlation_score', 'correlation_reason', 'approval_requested_at', 'approval_decided_by', 'approval_decided_at', 'approval_note']);
        });
    }
};
