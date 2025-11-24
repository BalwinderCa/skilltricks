<?php

use App\Models\User;
use App\Models\Project;
use App\Models\SubscribedUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnCurrencyCodeToSubscriptionHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_histories', function (Blueprint $table) {
            if(!Schema::hasColumn($table->getTable(), 'currency_code')) {
                $table->string('currency_code')->nullable();
            }
        });
        try {
            
            $customers = User::where('user_type', 'customer')->get(['email']);
            foreach($customers as $customer) {
     
                SubscribedUser::updateOrCreate([
                 'email'=>$customer->email
                ]);
            }
            Project::where('content_type', 'image')->where('engine', 'OpenAI')->update(['model_name'=>'dall-e-2']);
        } catch (\Throwable $th) {
           Log::info("customer to subscribe user error : ". $th->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_histories', function (Blueprint $table) {
            $dropColumns = ['currency_code'];
            $table->dropColumn($dropColumns);
        });
    }
}
