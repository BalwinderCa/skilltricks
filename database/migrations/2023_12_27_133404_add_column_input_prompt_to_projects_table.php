<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnInputPromptToProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            if(!Schema::hasColumn($table->getTable(), 'input_prompt')){
                $table->longText('input_prompt')->nullable();
            }
            if(!Schema::hasColumn($table->getTable(), 'completion_tokens')){
                $table->longText('completion_tokens')->nullable();
            }
        });
        Schema::table('ai_chat_messages', function (Blueprint $table) {
            if(!Schema::hasColumn($table->getTable(), 'prompt_tokens')){
                $table->longText('prompt_tokens')->nullable()->after('result');
            }
            if(!Schema::hasColumn($table->getTable(), 'completion_tokens')){
                $table->longText('completion_tokens')->nullable()->after('result');
            }
            if(!Schema::hasColumn($table->getTable(), 'input_prompt')){
                $table->longText('input_prompt')->nullable();
            }
        });
        Schema::table('ai_blog_wizard_titles', function (Blueprint $table) {
            if(!Schema::hasColumn($table->getTable(), 'prompt_tokens')){
                $table->longText('prompt_tokens')->nullable()->after('values');
            }
            if(!Schema::hasColumn($table->getTable(), 'completion_tokens')){
                $table->longText('completion_tokens')->nullable()->after('values');
            }
            if(!Schema::hasColumn($table->getTable(), 'input_prompt')){
                $table->longText('input_prompt')->nullable();
            }
        });
        Schema::table('ai_blog_wizard_key_words', function (Blueprint $table) {
            if(!Schema::hasColumn($table->getTable(), 'prompt_tokens')){
                $table->longText('prompt_tokens')->nullable()->after('traffic');
            }
            if(!Schema::hasColumn($table->getTable(), 'completion_tokens')){
                $table->longText('completion_tokens')->nullable()->after('traffic');
            }
            if(!Schema::hasColumn($table->getTable(), 'input_prompt')){
                $table->longText('input_prompt')->nullable();
            }
        });
        Schema::table('ai_blog_wizard_outlines', function (Blueprint $table) {
            if(!Schema::hasColumn($table->getTable(), 'prompt_tokens')){
                $table->longText('prompt_tokens')->nullable()->after('values');
            }
            if(!Schema::hasColumn($table->getTable(), 'completion_tokens')){
                $table->longText('completion_tokens')->nullable()->after('values');
            }
            if(!Schema::hasColumn($table->getTable(), 'input_prompt')){
                $table->longText('input_prompt')->nullable();
            }
        });
        Schema::table('ai_blog_wizard_article_logs', function (Blueprint $table) {
            if(!Schema::hasColumn($table->getTable(), 'prompt_tokens')){
                $table->longText('prompt_tokens')->nullable()->after('subscription_history_id');
            }
            if(!Schema::hasColumn($table->getTable(), 'completion_tokens')){
                $table->longText('completion_tokens')->nullable()->after('subscription_history_id');
            }
            if(!Schema::hasColumn($table->getTable(), 'input_prompt')){
                $table->longText('input_prompt')->nullable();
            }
        });
        Schema::table('ai_blog_wizards', function (Blueprint $table) {
            if(!Schema::hasColumn($table->getTable(), 'prompt_tokens')){
                $table->longText('prompt_tokens')->nullable()->before('total_words');
            }
            if(!Schema::hasColumn($table->getTable(), 'completion_tokens')){
                $table->longText('completion_tokens')->nullable()->before('total_words');
            }
            if(!Schema::hasColumn($table->getTable(), 'input_prompt')){
                $table->longText('input_prompt')->nullable();
            }
        });
        Schema::table('ai_blog_wizard_articles', function (Blueprint $table) {
            if(!Schema::hasColumn($table->getTable(), 'prompt_tokens')){
                $table->longText('prompt_tokens')->nullable()->before('total_words');
            }
            if(!Schema::hasColumn($table->getTable(), 'completion_tokens')){
                $table->longText('completion_tokens')->nullable()->before('total_words');
            }
            if(!Schema::hasColumn($table->getTable(), 'input_prompt')){
                $table->longText('input_prompt')->nullable();
            }
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $columns = ['input_prompt', 'completion_tokens'];
            $table->dropColumn($columns);
        });
        Schema::table('ai_chat_messages', function (Blueprint $table) {
            $columns = ['prompt_tokens', 'completion_tokens', 'input_prompt'];
            $table->dropColumn($columns);
        });
        Schema::table('ai_blog_wizard_titles', function (Blueprint $table) {
            $columns = ['prompt_tokens', 'completion_tokens', 'input_prompt'];
            $table->dropColumn($columns);
        });
        Schema::table('ai_blog_wizard_key_words', function (Blueprint $table) {
            $columns = ['prompt_tokens', 'completion_tokens', 'input_prompt'];
            $table->dropColumn($columns);
        });
        Schema::table('ai_blog_wizard_outlines', function (Blueprint $table) {
            $columns = ['prompt_tokens', 'completion_tokens', 'input_prompt'];
            $table->dropColumn($columns);
        });
        Schema::table('ai_blog_wizard_article_logs', function (Blueprint $table) {
            $columns = ['prompt_tokens', 'completion_tokens', 'input_prompt'];
            $table->dropColumn($columns);
        });
        Schema::table('ai_blog_wizards', function (Blueprint $table) {
            $columns = ['prompt_tokens', 'completion_tokens', 'input_prompt'];
            $table->dropColumn($columns);
        });
        Schema::table('ai_blog_wizard_articles', function (Blueprint $table) {
            $columns = ['prompt_tokens', 'completion_tokens', 'input_prompt'];
            $table->dropColumn($columns);
        }); 
      
    }
}
