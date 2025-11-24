<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLicensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_code')->nullable();
            $table->string('client_token')->nullable();
            $table->string('app_env')->nullable();
            $table->integer('is_active')->nullable()->default(1);
            $table->timestamps();
        });

        \App\Models\SystemSetting::query()->create([
            "default_max_result_length_blog_wizard" => 500
        ]);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('licenses');
    }
}
