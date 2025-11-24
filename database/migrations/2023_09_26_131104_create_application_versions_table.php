<?php

use Carbon\Carbon;
use App\Models\SystemSetting;
use App\Models\ApplicationVersion;
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
        $versionList = [  
            "1.0.0",
            "1.1.0",
            "1.5.0",
            "1.6.0",
            "1.7.0",
            "1.8.0",
            "1.9.0",
            "1.9.5",
            "2.0.0",
            "2.1.0",
            "2.2.0",
            "2.3.0",
            "2.3.1",
            "2.4.0",
            "2.4.1",
            "2.5.0",
            "2.6.0",
            "2.6.1",
            "2.7.0",
            "2.7.1",
            "2.8.0"
        ];
        $currentVersion = str_replace('v', '', env('APP_VERSION'));
        foreach($versionList as $data) {

            $version = new ApplicationVersion();
            $version->version = $data;
            $version->status = $currentVersion >= $data ? 1 : 0;           
            $version->save();

        }
        Schema::table('ai_blog_wizard_images', function (Blueprint $table) { 
            if (!Schema::hasColumn($table->getTable(), 'storage_type'))  {
                $table->string('storage_type')->nullable()->default('local'); 
            }    
        });
        Schema::table('ai_chat_messages', function (Blueprint $table) { 
            if (!Schema::hasColumn($table->getTable(), 'random_number'))  {
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
