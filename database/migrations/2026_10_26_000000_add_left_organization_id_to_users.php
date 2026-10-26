<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Which organization a removed member last belonged to. Removing a member
    // keeps the account, so this is what lets that same organization (and only
    // it) add them back later instead of the address being taken for good.
    public function up()
    {
        if (! Schema::hasColumn('users', 'left_organization_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('left_organization_id')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'left_organization_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('left_organization_id');
            });
        }
    }
};
