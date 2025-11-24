<?php

use App\Models\AiChatCategory;
use App\Models\ContactUsMessage;
use App\Models\WrNotification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWrNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wr_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('role_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('url')->nullable();
            $table->tinyInteger('is_read')->nullable()->default(0);
            $table->string('user_role')->nullable();
            $table->string('type')->nullable();
            $table->text('description')->nullable();     
            $table->timestamps();
        });
        $allUnReadContactUsMessage = ContactUsMessage::where('is_seen', 0)->get();
        foreach($allUnReadContactUsMessage as $contatcMessage) {
            saveNotification('Contact us', 'dashboard/contacts', 'admin', null, null, 'contact', null);
        }
        AiChatCategory::where('slug', 'UI/UX-Designer')->update([
            'slug'=>'UI-UX-Designer'
        ]);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wr_notifications');
    }
}
