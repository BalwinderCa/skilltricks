<?php

use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_content_detectors', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('ai')->nullable();
            $table->string('human')->nullable();
            $table->longText('content')->nullable();
            $table->longText('output')->nullable();
            $table->longText('response')->nullable();
            $table->tinyInteger('is_active')->nullable()->default(1);
            $table->integer('length')->nullable();
            $table->integer('created_by')->nullable()->default(1);
            $table->integer('updated_by')->nullable()->default(1);
            $table->tinyInteger('type')->nullable()->default(1)->comment('1 = detection, 2 = plagiarism');
            $table->timestamps();
        });
        Schema::table('subscription_packages', function (Blueprint $table) {
            if (!Schema::hasColumn($table->getTable(), 'show_ai_detector')) {
                $table->tinyInteger('show_ai_detector')->nullable()->default(0);
            }
            if (!Schema::hasColumn($table->getTable(), 'show_ai_plagiarism')) {
                $table->tinyInteger('show_ai_plagiarism')->nullable()->default(1);
            }
            if (!Schema::hasColumn($table->getTable(), 'allow_ai_detector')) {
                $table->integer('allow_ai_detector')->nullable()->default(1);
            }
            if (!Schema::hasColumn($table->getTable(), 'allow_ai_plagiarism')) {
                $table->integer('allow_ai_plagiarism')->nullable()->default(1);
            }
        });
        try {
           $permissions = array(
            array('id' => 120, 'name' => 'plagiarism_api', 'group_name' => 'plagiarism_api', 'guard_name' => 'web'),
            array('id' => 121, 'name' => 'ai_plagiarism', 'group_name' => 'plagiarism_api', 'guard_name' => 'web'),
            array('id' => 122, 'name' => 'ai_detector', 'group_name' => 'plagiarism_api', 'guard_name' => 'web'),
            array('id' => 123, 'name' => 'add_content_detectors', 'group_name' => 'plagiarism', 'guard_name' => 'web'),
            array('id' => 124, 'name' => 'add_content_plagiarism', 'group_name' => 'plagiarism', 'guard_name' => 'web'),
           );
           foreach($permissions as $permission)
           {
                $data = Permission::where('name', $permission['name'])->first();
                if(!$data){
                    $data = new Permission();
                    $data->name = $data['name'];
                    $data->group_name = $data['group_name'];
                    $data->guard_name = $data['guard_name'];
                    $data->save();
                }
           }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_content_detectors');
        Schema::table('pages', function (Blueprint $table) {
            $columns = ['show_ai_detector','show_ai_plagiarism','allow_ai_plagiarism','allow_ai_detector'];
            $table->dropColumn($columns);
        });
    }
};
