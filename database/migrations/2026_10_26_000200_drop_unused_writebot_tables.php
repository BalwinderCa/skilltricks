<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tables inherited from WriteBot that no code reads or writes.
    private const TABLES = [
        'application_versions',
        'old_payment_gateway_products',
        'subscription_auto_payments',
        'subscription_items',
        'voice_language_details',
        'voice_languages',
    ];

    public function up()
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }

    // Not recreated: nothing uses these tables.
    public function down() {}
};
