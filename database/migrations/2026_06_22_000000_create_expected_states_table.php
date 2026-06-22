<?php

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
        $isUnsigned = true;
        $isBigInt = true;

        if (Schema::hasTable('search_user_chat')) {
            $connection = Illuminate\Support\Facades\DB::connection();
            $driver = $connection->getDriverName();
            if ($driver === 'mysql') {
                $columnInfo = Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM `search_user_chat` LIKE 'id'");
                if (!empty($columnInfo)) {
                    $type = strtolower($columnInfo[0]->Type);
                    $isUnsigned = (strpos($type, 'unsigned') !== false);
                    $isBigInt = (strpos($type, 'bigint') !== false);
                }
            }
        }

        Schema::create('expected_states', function (Blueprint $table) use ($isUnsigned, $isBigInt) {
            $table->id();
            
            if ($isBigInt) {
                if ($isUnsigned) {
                    $table->unsignedBigInteger('search_user_chat_id');
                } else {
                    $table->bigInteger('search_user_chat_id');
                }
            } else {
                if ($isUnsigned) {
                    $table->unsignedInteger('search_user_chat_id');
                } else {
                    $table->integer('search_user_chat_id');
                }
            }

            $table->string('role');
            $table->text('recommended_action');
            $table->string('decision')->nullable(); // act_on_it, review_in_detail, not_viable
            $table->text('success_metric')->nullable();
            $table->string('target_value')->nullable();
            $table->date('target_date')->nullable();
            $table->boolean('resources_committed')->default(false);
            $table->timestamps();

            // Set up relationship/indexes
            $table->foreign('search_user_chat_id')
                  ->references('id')
                  ->on('search_user_chat')
                  ->onDelete('cascade');
            
            $table->index(['search_user_chat_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('expected_states');
    }
};
