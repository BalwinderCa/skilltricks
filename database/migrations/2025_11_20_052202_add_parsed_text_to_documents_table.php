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
        Schema::table('documents', function (Blueprint $table) {
            $table->longText('parsed_text')->nullable()->after('file_size');
            $table->string('parse_job_id')->nullable()->after('parsed_text');
            $table->enum('parse_status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->after('parse_job_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('parsed_text');
        });
    }
};
