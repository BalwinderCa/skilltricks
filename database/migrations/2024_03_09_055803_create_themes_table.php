<?php

use App\Models\Page;
use App\Models\Theme;
use Illuminate\Support\Facades\Log;
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
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('code');
            $table->tinyInteger('is_active')->default(1);
            $table->tinyInteger('is_default')->default(1);
            $table->text('preview_image')->nullable();
            $table->text('full_image')->nullable();
            $table->integer('created_by')->nullable()->default(1);
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::table('pages', function (Blueprint $table) {
            if (!Schema::hasColumn($table->getTable(), 'is_system')) {
                $table->tinyInteger('is_system')->nullable()->default(0);
            }
            if (!Schema::hasColumn($table->getTable(), 'is_active')) {
                $table->tinyInteger('is_active')->nullable()->default(1);
            }
            if (!Schema::hasColumn($table->getTable(), 'created_by')) {
                $table->integer('created_by')->nullable()->default(1);
            }
            if (!Schema::hasColumn($table->getTable(), 'updated_by')) {
                $table->integer('updated_by')->nullable()->default(1);
            }
        });
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn($table->getTable(), 'is_published')) {
                $table->tinyInteger('is_published')->nullable()->default(0);
            }
           
        });

        try {
            $themes = ['Default', 'Theme 1'];
            foreach ($themes as $theme) {
                Theme::updateOrCreate([
                    'name' => $theme,
                    'slug' => strtolower($theme),
                    'code' => str_replace(' ', '',strtolower($theme))
                ], [
                    'is_default'    => $theme == 'Default' ? 1 : 0,
                    'preview_image' => $theme == 'Default' ? 'theme-default.jpg' : 'theme-1.jpg',
                ]);
            }
            $page = Page::where('slug', 'terms-conditions')->first();
            if($page){
                $page->is_system = 1;
                $page->save(); 
            }
        } catch (\Throwable $th) {
            //throw $th;
            Log::info('Theme migration issue : ' . $th->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('themes');
        Schema::table('pages', function (Blueprint $table) {
            $columns = ['is_system','is_active','created_by','updated_by'];
            $table->dropColumn($columns);
        });
        Schema::table('projects', function (Blueprint $table) {
            $columns = ['is_published'];
            $table->dropColumn($columns);
        });
    }
};
