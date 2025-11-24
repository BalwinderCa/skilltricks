<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ad_sense_localizations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ad_sense_id');
            $table->text('name')->nullable();
            $table->string('lang_key')->nullable();
            $table->timestamps();
        });
        Schema::table('ad_senses', function (Blueprint $table) {
            if (!Schema::hasColumn($table->getTable(), 'is_dashboard')) {
                $table->string('is_dashboard')->nullable()->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ad_sense_localizations');
        Schema::table('ad_senses', function (Blueprint $table) {
            $columns = ['is_dashboard'];
            $table->dropColumn($columns);
        });
    }
};
