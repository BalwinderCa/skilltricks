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

        // A FK column must match the referenced PK exactly (type + signedness)
        // or MySQL/MariaDB rejects the constraint with errno 150. Some child
        // columns are int(11) while the *.id PKs are bigint unsigned — align
        // the child column's type to the parent before adding the constraint.
        $this->alignColumnToReference($table, $column, $referencedTable, $referencedColumn);

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $referencedColumn, $constraintName) {
            $blueprint->foreign($column, $constraintName)
                ->references($referencedColumn)
                ->on($referencedTable)
                ->restrictOnDelete();
        });
    }

    /**
     * Ensure $table.$column has the same column type as $referencedTable.$referencedColumn,
     * preserving the child column's nullability. No-op if they already match.
     */
    private function alignColumnToReference(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn
    ): void {
        $dbName = DB::getDatabaseName();

        $ref = DB::selectOne(
            'SELECT COLUMN_TYPE AS type FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$dbName, $referencedTable, $referencedColumn]
        );

        $child = DB::selectOne(
            'SELECT COLUMN_TYPE AS type, IS_NULLABLE AS nullable FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$dbName, $table, $column]
        );

        if (! $ref || ! $child || strcasecmp($child->type, $ref->type) === 0) {
            return; // missing column, or types already match
        }

        $nullClause = strcasecmp($child->nullable, 'YES') === 0 ? 'NULL' : 'NOT NULL';

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$ref->type} {$nullClause}");
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
