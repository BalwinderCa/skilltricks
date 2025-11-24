<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CreateAiResponseLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ai_response_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('ai_chat_message_id')->nullable();
            $table->unsignedBigInteger('ai_blog_wizard_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->longText('row_response')->nullable();
            $table->longText('generate_response')->nullable();
            $table->timestamps();
        });
        try {
            $settings = SystemSetting::where('entity', 'image_stable_diffusion_engine')->where('value', 'stable-diffusion-v1-5')->first();

            if ($settings) {
                $settings->value =  'stable-diffusion-v1-6';
                $settings->save();
            }

        } catch (\Throwable $th) {
            //throw $th;
            Log::info('ai resonse migration issue : '.$th->getMessage());
        }
      

    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_response_logs');
    }
}
