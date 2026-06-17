<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('subscription_histories', ['user_id'], 'subscription_histories_user_id_index');
        $this->addIndexIfMissing('subscription_histories', ['subscription_package_id'], 'subscription_histories_package_id_index');
        $this->addIndexIfMissing('subscription_histories', ['subscription_status'], 'subscription_histories_status_index');
        $this->addIndexIfMissing('subscription_histories', ['payment_status'], 'subscription_histories_payment_status_index');
        $this->addIndexIfMissing('subscription_histories', ['user_id', 'subscription_status'], 'subscription_histories_user_status_index');

        $this->addIndexIfMissing('ai_chat_messages', ['user_id'], 'ai_chat_messages_user_id_index');
        $this->addIndexIfMissing('ai_chat_messages', ['ai_chat_id'], 'ai_chat_messages_ai_chat_id_index');
        $this->addIndexIfMissing('ai_chat_messages', ['user_id', 'ai_chat_id'], 'ai_chat_messages_user_chat_index');

        $this->addIndexIfMissing('users', ['email'], 'users_email_index');
        $this->addIndexIfMissing('users', ['user_type'], 'users_user_type_index');
        $this->addIndexIfMissing('users', ['referred_by'], 'users_referred_by_index');

        $this->addIndexIfMissing('template_usages', ['user_id'], 'template_usages_user_id_index');
        $this->addIndexIfMissing('template_usages', ['template_id'], 'template_usages_template_id_index');
        $this->addIndexIfMissing('template_usages', ['user_id', 'template_id'], 'template_usages_user_template_index');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('subscription_histories', 'subscription_histories_user_id_index');
        $this->dropIndexIfExists('subscription_histories', 'subscription_histories_package_id_index');
        $this->dropIndexIfExists('subscription_histories', 'subscription_histories_status_index');
        $this->dropIndexIfExists('subscription_histories', 'subscription_histories_payment_status_index');
        $this->dropIndexIfExists('subscription_histories', 'subscription_histories_user_status_index');

        $this->dropIndexIfExists('ai_chat_messages', 'ai_chat_messages_user_id_index');
        $this->dropIndexIfExists('ai_chat_messages', 'ai_chat_messages_ai_chat_id_index');
        $this->dropIndexIfExists('ai_chat_messages', 'ai_chat_messages_user_chat_index');

        $this->dropIndexIfExists('users', 'users_email_index');
        $this->dropIndexIfExists('users', 'users_user_type_index');
        $this->dropIndexIfExists('users', 'users_referred_by_index');

        $this->dropIndexIfExists('template_usages', 'template_usages_user_id_index');
        $this->dropIndexIfExists('template_usages', 'template_usages_template_id_index');
        $this->dropIndexIfExists('template_usages', 'template_usages_user_template_index');
    }

    private function addIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $exists = collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]))->isNotEmpty();

        if (! $exists) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                $blueprint->index($columns, $indexName);
            });
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $exists = collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]))->isNotEmpty();

        if ($exists) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                $blueprint->dropIndex($indexName);
            });
        }
    }
};
