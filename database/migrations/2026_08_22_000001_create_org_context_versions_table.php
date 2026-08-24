<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('org_context_versions')) {
            Schema::create('org_context_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id');
                $table->unsignedBigInteger('user_id');
                $table->integer('rank');
                $table->json('profile');
                // Null marks a backfilled row: derived from an existing profile
                // rather than produced by an interview.
                $table->json('transcript')->nullable();
                // Append-only: rows are never updated, so there is no updated_at.
                $table->timestamp('created_at')->nullable();

                $table->index(['organization_id', 'rank']);
                $table->index('user_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('org_context_versions');
    }
};
