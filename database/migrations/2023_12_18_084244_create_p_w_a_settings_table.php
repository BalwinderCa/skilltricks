<?php

use App\Models\PWASettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class CreatePWASettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('p_w_a_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->text('background_color')->nullable();
            $table->text('theme_color')->nullable();
            $table->text('status_bar')->nullable();
            $table->text('icon_72')->nullable();
            $table->text('icon_96')->nullable();
            $table->text('icon_128')->nullable();
            $table->text('icon_144')->nullable();
            $table->text('icon_152')->nullable();
            $table->text('icon_192')->nullable();
            $table->text('icon_384')->nullable();
            $table->text('icon_512')->nullable();
            $table->timestamps();
        });
        
        $pwa                   = new PWASettings();
        $pwa->name             = env('APP_NAME', 'WriteBot AI');
        $pwa->short_name       = 'PWA';
        $pwa->background_color = '#ffffff';
        $pwa->theme_color      = '#7a16d4';
        $pwa->status_bar       = '#000000';
        $pwa->icon_72          = 'images/icons/icon-72x72.png';
        $pwa->icon_96          = 'images/icons/icon-96x96.png';
        $pwa->icon_128         = 'images/icons/icon-128x128.png';
        $pwa->icon_144         = 'images/icons/icon-144x144.png';
        $pwa->icon_152         = 'images/icons/icon-152x152.png';
        $pwa->icon_192         = 'images/icons/icon-192x192.png';
        $pwa->icon_384         = 'images/icons/icon-384x384.png';
        $pwa->icon_512         = 'images/icons/icon-512x512.png';
        $pwa->save();

        if (file_exists(public_path('logo.png'))) {
            File::move(public_path('logo.png'), base_path('public/images/icons/logo.png'));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('p_w_a_settings');
    }
}
