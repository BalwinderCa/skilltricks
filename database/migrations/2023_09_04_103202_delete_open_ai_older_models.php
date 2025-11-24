<?php

use App\Models\OpenAiModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteOpenAiOlderModels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $oldModels = [1,2,3,4];
        OpenAiModel::whereIn('id', $oldModels)->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('open_ai_models', function (Blueprint $table) {
            //
        });
    }
}
