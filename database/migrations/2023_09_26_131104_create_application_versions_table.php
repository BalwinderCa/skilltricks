<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicationVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('application_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version')->nullable();
            $table->string('release_date')->nullable();
            $table->boolean('status')->nullable()->default(false);
            $table->timestamps();
        });
        Schema::table('ai_blog_wizard_images', function (Blueprint $table) {
            if (! Schema::hasColumn($table->getTable(), 'storage_type')) {
                $table->string('storage_type')->nullable()->default('local');
            }
        });
        Schema::table('ai_chat_messages', function (Blueprint $table) {
            if (! Schema::hasColumn($table->getTable(), 'random_number')) {
                $table->string('random_number')->nullable();
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
        Schema::dropIfExists('application_versions');
    }
}
