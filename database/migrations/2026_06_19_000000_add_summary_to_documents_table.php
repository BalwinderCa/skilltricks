<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Compact, AI-generated summary of the document used as company context in
     * the GoalSync chat. Injected instead of the full parsed_text so token cost
     * stays bounded no matter how many (or how large) the documents are.
     */
    public function up()
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->longText('summary')->nullable()->after('parsed_text');
            $table->enum('summary_status', ['pending', 'completed', 'failed'])
                ->default('pending')
                ->after('summary');
        });
    }

    public function down()
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['summary', 'summary_status']);
        });
    }
};
