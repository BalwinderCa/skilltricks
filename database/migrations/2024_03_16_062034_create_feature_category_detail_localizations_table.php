<?php

use App\Models\SubscriptionPackage;
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
        Schema::create('feature_category_detail_localizations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('feature_category_detail_id');
            $table->string('title');
            $table->text('short_description')->nullable();
            $table->string('lang_key');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::table('subscription_packages', function (Blueprint $table) {
            if (!Schema::hasColumn($table->getTable(), 'show_dall_e_2_image')) {
                $table->tinyInteger('show_dall_e_2_image')->nullable()->default(0);
            }
            if (!Schema::hasColumn($table->getTable(), 'show_dall_e_3_image')) {
                $table->tinyInteger('show_dall_e_3_image')->nullable()->default(1);
            }
            if (!Schema::hasColumn($table->getTable(), 'allow_dall_e_2_image')) {
                $table->integer('allow_dall_e_2_image')->nullable()->default(1);
            }
            if (!Schema::hasColumn($table->getTable(), 'allow_dall_e_3_image')) {
                $table->integer('allow_dall_e_3_image')->nullable()->default(1);
            }
        });
        $packages = SubscriptionPackage::where('allow_images', 1)->update([
            'show_dall_e_2_image'=>1,
            'show_dall_e_3_image'=>1,
            'allow_dall_e_2_image'=>1,
            'allow_dall_e_3_image'=>1
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('feature_category_detail_localizations');
        Schema::table('pages', function (Blueprint $table) {
            $columns = ['show_dall_e_2_image','show_dall_e_3_image','allow_dall_e_3_image','allow_dall_e_2_image'];
            $table->dropColumn($columns);
        });
    }
};
