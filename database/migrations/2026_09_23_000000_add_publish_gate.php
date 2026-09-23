<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Publish gate (Features spec, phase 1): a strategy is Draft until a leader
     * commits resources and publishes it to their organization.
     */
    public function up(): void
    {
        Schema::table('search_user_chat', function (Blueprint $table) {
            if (! Schema::hasColumn('search_user_chat', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->index();
            }
            if (! Schema::hasColumn('search_user_chat', 'status')) {
                $table->string('status', 20)->default('draft');
            }
            if (! Schema::hasColumn('search_user_chat', 'published_by')) {
                $table->unsignedBigInteger('published_by')->nullable();
            }
            if (! Schema::hasColumn('search_user_chat', 'published_at')) {
                $table->timestamp('published_at')->nullable();
            }
        });

        $this->backfill();

        // search_user_chat.id differs in type between the legacy MySQL table and
        // the SQLite test table; the foreign key must match it exactly.
        [$isUnsigned, $isBigInt] = [true, true];
        if (DB::connection()->getDriverName() === 'mysql') {
            $info = DB::select("SHOW COLUMNS FROM `search_user_chat` LIKE 'id'");
            if (! empty($info)) {
                $type = strtolower($info[0]->Type);
                $isUnsigned = str_contains($type, 'unsigned');
                $isBigInt = str_contains($type, 'bigint');
            }
        }

        Schema::create('strategy_resources', function (Blueprint $table) use ($isUnsigned, $isBigInt) {
            $table->id();
            $column = match (true) {
                $isUnsigned && $isBigInt => 'unsignedBigInteger',
                $isUnsigned => 'unsignedInteger',
                $isBigInt => 'bigInteger',
                default => 'integer',
            };
            $table->{$column}('search_user_chat_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('department_name');
            $table->decimal('budget', 12, 2)->nullable();
            $table->decimal('fte', 6, 2)->nullable();
            $table->text('tools')->nullable();
            $table->text('notes')->nullable();
            $table->json('ai_suggestion')->nullable();
            $table->timestamps();

            $table->foreign('search_user_chat_id')->references('id')->on('search_user_chat')->onDelete('cascade');
            $table->index('department_id');
        });

        Schema::create('strategy_resource_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_resource_id')->constrained('strategy_resources')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('field', 20);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /** Existing strategies belong to their author's organization. */
    public function backfill(): void
    {
        DB::statement('UPDATE search_user_chat SET organization_id = '
            .'(SELECT organization_id FROM users WHERE users.id = search_user_chat.user_id) '
            .'WHERE organization_id IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_resource_changes');
        Schema::dropIfExists('strategy_resources');
        Schema::table('search_user_chat', function (Blueprint $table) {
            $table->dropIndex(['organization_id']);
            $table->dropColumn(['organization_id', 'status', 'published_by', 'published_at']);
        });
    }
};
