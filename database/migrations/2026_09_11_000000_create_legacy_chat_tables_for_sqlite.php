<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The legacy chat tables, for databases that never inherited them.
 *
 * These five pre-date this repo's migrations: they exist on the deployed MySQL
 * database, created by hand, so nothing here ever needed to declare them. A
 * fresh SQLite database gets none of them, and AiChatController then 500s on
 * "no such table" for every user on every chat.
 *
 * 2026_06_16_000000 already did exactly this for search_user_chat; it simply
 * stopped one table short of search_user_chat_data. This is the rest of the set.
 *
 * Column types mirror the deployed schema rather than correcting it — user_id
 * and search_user_chat_id really are longtext there, and chat_categories really
 * does spell its second timestamp "update_at". A tidier local schema would only
 * make local behaviour diverge from the server's.
 *
 * Guarded per table: on MySQL every one of these already exists, so the
 * migration runs and does nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('search_user_chat_data')) {
            Schema::create('search_user_chat_data', function (Blueprint $table) {
                $table->id();
                $table->longText('search_user_chat_id')->nullable();
                $table->longText('user_id')->nullable();
                $table->longText('answers')->nullable();
                $table->longText('chat_role_categories')->nullable();
                $table->longText('categories')->nullable();
                $table->longText('subcategories')->nullable();
                $table->longText('questionmenuid')->nullable();
                $table->longText('search')->nullable();
                $table->text('response')->nullable();
                // No updated_at: the model sets UPDATED_AT = null to match.
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (! Schema::hasTable('chat_categories')) {
            Schema::create('chat_categories', function (Blueprint $table) {
                $table->id();
                $table->longText('name')->nullable();
                $table->text('role_name')->nullable();
                $table->integer('status')->default(1);
                $table->timestamp('created_at')->nullable()->useCurrent();
                // "update_at" is the deployed spelling. Not a typo here.
                $table->timestamp('update_at')->nullable();
            });
        }

        if (! Schema::hasTable('subcategory_menu')) {
            Schema::create('subcategory_menu', function (Blueprint $table) {
                $table->id();
                $table->longText('role')->nullable();
                $table->longText('categories')->nullable();
                $table->longText('subcategories')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('subcategory_menu_question')) {
            Schema::create('subcategory_menu_question', function (Blueprint $table) {
                $table->id();
                $table->integer('subcategorymenu_id')->nullable();
                $table->longText('question')->nullable();
                $table->integer('status')->default(1);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('user_chat_answers')) {
            Schema::create('user_chat_answers', function (Blueprint $table) {
                $table->id();
                $table->longText('user_id')->nullable();
                $table->longText('answers')->nullable();
                $table->longText('chat_role_categories')->nullable();
                $table->longText('categories')->nullable();
                $table->longText('subcategories')->nullable();
                $table->longText('questionmenuid')->nullable();
                $table->integer('status')->default(0);
                $table->integer('status1')->default(0);
                $table->integer('status2')->default(0);
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Deliberately empty. up() only ever creates tables this database was
        // missing; dropping them on rollback would delete the real ones on any
        // database that had them all along.
    }
};
