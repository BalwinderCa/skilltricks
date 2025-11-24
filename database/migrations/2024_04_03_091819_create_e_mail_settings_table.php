<?php

use App\Models\EMailSetting;
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
        Schema::create('e_mail_settings', function (Blueprint $table) {
            $table->id();
            $table->string('email_engine')->nullable();
            $table->string('mail_driver')->nullable();
            $table->string('mail_host')->nullable();
            $table->string('mail_port')->nullable();
            $table->string('mail_username')->nullable();
            $table->string('mail_password')->nullable();
            $table->string('mail_encryption')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->tinyInteger('is_active')->nullable()->default(0);
            $table->timestamps();
        });
        $settings = new EMailSetting();
        $settings->email_engine    = 'smtp';
        $settings->mail_driver     = 'smtp';
        $settings->mail_host       = 'sandbox.smtp.mailtrap.io';
        $settings->mail_port       = '2525';
        $settings->mail_username   = 'bc7fd289c0fcf5';
        $settings->mail_password   = 'bb50929b550873';
        $settings->mail_encryption = 'tls';
        $settings->from_email      = 'no-reply@themetags.com';
        $settings->from_name       = 'WritebotAI - ThemeTags';
        $settings->is_active       = 1;
        $settings->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('e_mail_settings');
    }
};
