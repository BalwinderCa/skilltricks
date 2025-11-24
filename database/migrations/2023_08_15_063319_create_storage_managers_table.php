<?php

use App\Models\SystemSetting;
use App\Models\StorageManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStorageManagersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('storage_managers', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('access_key')->nullable();
            $table->string('secret_key')->nullable();
            $table->string('bucket')->nullable();
            $table->string('region')->nullable();

            $table->string('container')->nullable();
            $table->string('storage_name')->nullable();
            $table->string('storage_url')->nullable();

            $table->string('file_name')->nullable();
            $table->string('path')->nullable();

            $table->boolean('is_active')->nullable()->default(true);
            $table->boolean('is_deActive')->nullable()->default(true);
            $table->integer('created_by')->nullable()->default(1);
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
        $types = ['local', 'aws', 'gcs', 'azure'];

        foreach($types as $type)
        {
            $manager = new StorageManager();
            $manager->type = $type;
            $manager->is_active = $type == 'local' ? true : false;
            $manager->is_deActive = in_array($type, ['azure', 'gcs']) ? true :false;
            $manager->save();
        }
        SystemSetting::updateOrCreate(
            [
                'entity' => 'active_storage'
            ],

            ['value' => 'local']
        );
      
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('storage_managers');
    }
}
