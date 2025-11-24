<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnTypeToAiChatMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ai_chat_categories', function (Blueprint $table) {
            if(!Schema::hasColumn($table->getTable(), 'type')){
                $table->string('type')->nullable()->default('chat');
            }
        });
        Schema::table('ai_chat_messages', function (Blueprint $table) {
            if(!Schema::hasColumn($table->getTable(), 'type')){
                $table->string('type')->nullable()->default('chat');
            }
            if(!Schema::hasColumn($table->getTable(), 'images')){
                $table->longText('images')->nullable();
            }
            if(!Schema::hasColumn($table->getTable(), 'file_path')){
                $table->longText('file_path')->nullable();
            }
            if(!Schema::hasColumn($table->getTable(), 'revers_prompt')){
                $table->longText('revers_prompt')->nullable();
            }
            if(!Schema::hasColumn($table->getTable(), 'storage_type')){
                $table->string('storage_type')->nullable();
            }
        });
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->tinyInteger('allow_ai_vision')->default(0);
            $table->tinyInteger('show_ai_vision')->default(0);
            $table->tinyInteger('allow_ai_image_chat')->default(0);
            $table->tinyInteger('show_ai_image_chat')->default(0);


        });
        $chatExpert = array(
                array(
                    'name' => 'AI Vision',
                    'short_name' => 'AV',
                    'slug' => 'ai-vision',
                    'description' => 'Chat With Image Expert',
                    'role' => 'Image Expert',
                    'user_name' => '',
                    'type'=>'vision',
                    'assists_with' => 'Hi, I am Image Expert.I will help you to understand your images',
                    'avatar' => 'backend/assets/img/expertise/1.jpg'
                ),
                array(
                    'name' => 'Chat Image Creator',
                    'short_name' => 'AIC',
                    'slug' => 'ai-image-chat',
                    'description' => 'Chat With Image Expert',
                    'role' => 'Image Expert',
                    'user_name' => '',
                    'type'=>'image',
                    'assists_with' => 'Hi! I am Image Creator Expert. I can assist to generate image by user inputI will help you to create your Image',
                    'avatar' => 'backend/assets/img/expertise/1.jpg'
                )
            );
        DB::table('ai_chat_categories')->insert($chatExpert);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ai_chat_categories', function (Blueprint $table) {
            $columns = ['type'];
            $table->dropColumn($columns);
        });
        Schema::table('ai_chat_messages', function (Blueprint $table) {
            $columns = ['type', 'images', 'file_path', 'revers_prompt','storage_type'];
            $table->dropColumn($columns);
        });
        Schema::table('subscription_packages', function (Blueprint $table) {
            $columns = ['allow_ai_vision', 'show_ai_vision', 'allow_ai_image_chat',
            'show_ai_image_chat'];
            $table->dropColumn($columns);
        });
    }
}
