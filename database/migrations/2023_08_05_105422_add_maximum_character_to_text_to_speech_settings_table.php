<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMaximumCharacterToTextToSpeechSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('text_to_speech_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('text_to_speech_settings', 'maximum_character')) {
                $table->bigInteger('maximum_character')->nullable();
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
        Schema::table('text_to_speech_settings', function (Blueprint $table) {
            $columns = ['maximum_character'];
            $table->dropColumn($columns);
        });
    }
}
