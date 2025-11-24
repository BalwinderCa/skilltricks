<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAiPdfChatAtTableSubscriptionPackages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            if(!Schema::hasColumn($table->getTable(), 'allow_ai_pdf_chat')) {
                $table->tinyInteger('allow_ai_pdf_chat')->default(0);
            }
            if(!Schema::hasColumn($table->getTable(), 'allow_ai_pdf_chat')) {
                $table->tinyInteger('show_ai_pdf_chat')->default(0);
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
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->dropColumn(['allow_ai_pdf_chat']);
            $table->dropColumn(['show_ai_pdf_chat']);
        });
    }
}
