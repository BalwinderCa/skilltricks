<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('organizations')) {
            Schema::create('organizations', function (Blueprint $table) {
                $table->id();
                $table->string('domain')->unique();
                $table->string('name')->nullable();
                $table->unsignedBigInteger('owner_user_id')->nullable();
                // No FK: this points at org_context_versions, which is created after
                // this table. A constraint here would be circular at migrate time.
                $table->unsignedBigInteger('active_context_id')->nullable();
                $table->timestamps();

                $table->index('owner_user_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('organizations');
    }
};
