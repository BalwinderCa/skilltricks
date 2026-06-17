<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Orphan rows (user_id / package_id / template_id / ai_chat_id pointing at
     * missing parents) must be cleaned up before running this on production.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->addForeignKeyIfMissing(
            'subscription_histories',
            'user_id',
            'users',
            'id',
            'subscription_histories_user_id_foreign'
        );

        $this->addForeignKeyIfMissing(
            'subscription_histories',
            'subscription_package_id',
            'subscription_packages',
            'id',
            'subscription_histories_subscription_package_id_foreign'
        );

        $this->addForeignKeyIfMissing(
            'ai_chat_messages',
            'user_id',
            'users',
            'id',
            'ai_chat_messages_user_id_foreign'
        );

        $this->addForeignKeyIfMissing(
            'ai_chat_messages',
            'ai_chat_id',
            'ai_chats',
            'id',
            'ai_chat_messages_ai_chat_id_foreign'
        );

        $this->addForeignKeyIfMissing(
            'template_usages',
            'user_id',
            'users',
            'id',
            'template_usages_user_id_foreign'
        );

        $this->addForeignKeyIfMissing(
            'template_usages',
            'template_id',
            'templates',
            'id',
            'template_usages_template_id_foreign'
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->dropForeignKeyIfExists('subscription_histories', 'subscription_histories_user_id_foreign');
        $this->dropForeignKeyIfExists('subscription_histories', 'subscription_histories_subscription_package_id_foreign');
        $this->dropForeignKeyIfExists('ai_chat_messages', 'ai_chat_messages_user_id_foreign');
        $this->dropForeignKeyIfExists('ai_chat_messages', 'ai_chat_messages_ai_chat_id_foreign');
        $this->dropForeignKeyIfExists('template_usages', 'template_usages_user_id_foreign');
        $this->dropForeignKeyIfExists('template_usages', 'template_usages_template_id_foreign');
    }

    private function addForeignKeyIfMissing(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $constraintName
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasTable($referencedTable)) {
            return;
        }

        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        if ($this->foreignKeyExists($table, $constraintName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $referencedColumn, $constraintName) {
            $blueprint->foreign($column, $constraintName)
                ->references($referencedColumn)
                ->on($referencedTable)
                ->restrictOnDelete();
        });
    }

    private function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        if (! Schema::hasTable($table) || ! $this->foreignKeyExists($table, $constraintName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($constraintName) {
            $blueprint->dropForeign($constraintName);
        });
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $dbName = DB::getDatabaseName();

        return collect(DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$dbName, $table, $constraintName, 'FOREIGN KEY']
        ))->isNotEmpty();
    }
};
