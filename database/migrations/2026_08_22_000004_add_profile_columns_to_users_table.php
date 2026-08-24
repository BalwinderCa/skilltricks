<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repairs drift between the migrations and the live database. These columns
     * are used all over the app but were added out of band, so a fresh database
     * never had them. Every add is guarded, making this a no-op wherever they
     * already exist.
     */
    private const COLUMNS = [
        'company',
        'company_name',
        'company_address',
        'number_employess',
        'chat_role_categories',
        'company_category',
    ];

    public function up()
    {
        foreach (self::COLUMNS as $column) {
            if (! Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column) {
                    $table->string($column)->nullable();
                });
            }
        }

        if (! Schema::hasColumn('users', 'about_company')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('about_company')->nullable();
            });
        }
    }

    public function down()
    {
        // Intentionally empty. These columns predate this migration on every
        // real database and hold live user data; dropping them on rollback
        // would destroy it.
    }
};
