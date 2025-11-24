<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OpenAiModelTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        \DB::table('open_ai_models')->delete();

        \DB::table('open_ai_models')->insert(array(
            array('id' => '1', 'name' => 'Gpt 3.5 turbo instruct', 'key' => 'gpt-3.5-turbo-instruct',  'order' => 0, 'created_at' => NULL, 'updated_at' => NULL, 'deleted_at' => '2023-09-04 17:50:22'),
            array('id' => '2', 'name' => 'ChatGPT 3.5', 'key' => 'gpt-3.5-turbo',  'order' => 0, 'created_at' => NULL, 'updated_at' => NULL, 'deleted_at' => NULL),
            array('id' => '3', 'name' => 'ChatGPT 4', 'key' => 'gpt-4',  'order' => 1, 'created_at' => NULL, 'updated_at' => NULL, 'deleted_at' => NULL),
            array('id' => '4', 'name' => 'ChatGPT 3.5 Turbo-16k', 'key' => 'gpt-3.5-turbo-16k',  'order' => 0,'created_at' => NULL, 'updated_at' => NULL, 'deleted_at' => NULL), 
            array('id' => '5', 'name' => 'Updated GPT 3.5 Turbo', 'key' => 'gpt-3.5-turbo-1106', 'order' => 1, 'created_at' => NULL, 'updated_at' => NULL, 'deleted_at' => NULL),
            array('id' => '6', 'name' => 'ChatGPT 4 Gpt-4-32k', 'key' => 'gpt-4-0613', 'order' => 1, 'created_at' => NULL, 'updated_at' => NULL, 'deleted_at' => NULL), 
            array('id' => '7', 'name' => 'GPT-4 Turbo', 'key' => 'gpt-4-turbo',  'order' => 0,'created_at' => NULL, 'updated_at' => NULL, 'deleted_at' => NULL), 
            array('id' => '8', 'name' => 'GPT-4o', 'key' => 'gpt-4o', 'order' => 1, 'created_at' => NULL, 'updated_at' => NULL, 'deleted_at' => NULL), 
            array('id' => '9', 'name' => 'Gpt 4o mini', 'key' => 'gpt-4o-mini', 'order' => 1, 'created_at' => NULL, 'updated_at' => NULL, 'deleted_at' => NULL),
            array('id' => '10', 'name' => 'Gpt 4o mini 2024 07 18', 'key' => 'gpt-4o-mini-2024-07-18', 'order' => 1, 'created_at' => NULL, 'updated_at' => NULL, 'deleted_at' => NULL),
            array('id' => '11', 'name' => 'Chatgpt 4o latest', 'key' => 'chatgpt-4o-latest', 'order' => 1, 'created_at' => NULL, 'updated_at' => NULL, 'deleted_at' => NULL),
        ));
    }
}
