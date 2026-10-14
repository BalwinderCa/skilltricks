<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * chat_subcategories, the legacy table 2026_09_11_000000 missed.
 *
 * FaqsController::getSubcategories() reads it to fill the New Chat wizard's
 * Sub Category picker, so without it that picker stays empty and the wizard
 * cannot get past its first step. Same rules as the rest of the set: column
 * types and the "update_at" spelling mirror the deployed MySQL table, and the
 * guard makes this a no-op there.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_subcategories')) {
            Schema::create('chat_subcategories', function (Blueprint $table) {
                $table->id();
                $table->text('role_name')->nullable();
                $table->longText('parent_category')->nullable();
                $table->longText('sub_category')->nullable();
                $table->integer('status')->default(1);
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('update_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Empty on purpose: on MySQL the table is real and holds config data.
    }
};
