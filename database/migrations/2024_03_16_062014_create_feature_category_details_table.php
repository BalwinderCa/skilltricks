<?php

use App\Models\Page;
use App\Models\PageLocalization;
use Illuminate\Support\Facades\Schema;
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
        Schema::create('feature_category_details', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('image')->nullable();
            $table->bigInteger('feature_category_id');
            $table->text('short_description')->nullable();
            $table->text('icon')->nullable();
            $table->string('is_active')->nullable()->default(1);
            $table->bigInteger('created_by')->nullable()->default(1);
            $table->bigInteger('updated_by')->nullable()->default(1);
            $table->timestamps();
        });
        try {
            $page = Page::where('slug', 'privacy-policy')->first();
            if($page){
                $page->is_system = 1;
                $page->save();
            }
            if(!$page){
                $request = (object)[
                    'title'            => 'Privacy Policy',
                    'slug'             => 'privacy-policy',
                    'meta_title'       => 'writebot',
                    'meta_description' => 'writebot',
                    'content'          => 'Welcome to ThemeTags!',
                    'meta_image'       => null,
                    'is_system'        => 1                
                ];
               
                $page = new Page;
               
                $page->title            = $request->title;
                $page->slug             = convertToSlug($request->title);
                $page->content          = $request->content;
                $page->meta_title       = $request->meta_title;
                $page->meta_description = $request->meta_description;
                $page->meta_image       = $request->meta_image;
                $page->is_system        = $request->is_system ?? 0;
                $page->save();
          
                $pageLocalization           = PageLocalization::firstOrNew(['lang_key' => env('DEFAULT_LANGUAGE'), 'page_id' => $page->id]);
                $pageLocalization->title    = $request->title;
                $pageLocalization->content  = $request->content;
                $pageLocalization->save();
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
        Schema::dropIfExists('feature_category_details');
    }
};
