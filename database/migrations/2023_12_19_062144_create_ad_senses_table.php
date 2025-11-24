<?php

use App\Models\AdSense;
use App\Models\SystemSetting;
use App\Models\TextToSpeechSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdSensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ad_senses', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('size')->nullable();
            $table->string('name')->nullable();
            $table->longText('code')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
        try {
            Schema::table('text_to_speeches', function (Blueprint $table) {
                if (!Schema::hasColumn($table->getTable(), 'type')) {
                    $table->string('type')->nullable();
                }
            });
            $ads = [
                'Header Top',
                'Bottom trusted by',
                'Top Best Feature',
                'Top Template Section',
                'Top Review Section',
                'Top Subscription Package',
                'Top Trail Banner Section',
                'Top Footer Section',
            ];
            foreach ($ads as $item) {
                $str = strtolower($item);
                $ad = new AdSense();
                $ad->slug = str_replace(' ', '-', $str);
                $ad->size = '728x90';
                $ad->name = $item;
                $ad->save();
            }
            TextToSpeechSetting::create([
                'type' => 'open_ai_tts',
                'maximum_character' => 4096
            ]);
            $default_voiceover = SystemSetting::where('entity', 'default_voiceover')->first();
            if (!$default_voiceover) {
                $settings = new  SystemSetting;
                $settings->entity = 'default_voiceover';
                $settings->value = 'open_ai_tts';
                $settings->save();
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ad_senses');
    }
}
