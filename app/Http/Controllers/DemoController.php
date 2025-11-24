<?php

namespace App\Http\Controllers;

use App\Models\PdfChat;
use App\Services\Pdf\PdfService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ZipArchive;
use File;
use Illuminate\Http\Request;

class DemoController extends Controller
{

    public function __construct()
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
    }

    public function eventTry(Request $request)
    {


        $pdfChatId = getSession(($this)::SESSION_PDF_CHAT);

        dd($pdfChatId);

        $pdfChat = (new PdfService())->getPdfChatById(5);
        dd($pdfChat);



        $adminLimit = 0;
        $customerBal = 500;
        $generateLimit = 0;

        if($customerBal <= 0){
            dd("Sorry Mate, You doesn't have enough balance.");
        }

        // When Admin limit has 0 and also Generate limit has 0 -- keep continue generate articles
        if($adminLimit <= 0 && $generateLimit <= 0){
            // Keep Continue
        }

        // When Admin Limit Has 0 but Generate Limit has value means generate limit shouldn't be greater than customer balance
        if($adminLimit <= 0 && $generateLimit>0 && $generateLimit <= $customerBal) {
            // Keep Continue
        }

        // When Admin Has value but Generate Limit has 0 means admin limit shouldn't be greater than customer balance
        if($adminLimit > 0 && $generateLimit <= 0 && $adminLimit <= $customerBal) {
            // Keep Continue
        }

        if($customerBal >= $generateLimit){
            // Keep Continue
        }
    }

    public function cron_1()
    {
        $this->drop_all_tables();
        $this->import_demo_sql();

        writeToEnvFile('DEFAULT_CURRENCY', 'usd');
        writeToEnvFile('DEFAULT_CURRENCY_RATE', '1');
        writeToEnvFile('DEFAULT_CURRENCY_SYMBOL', '$');
        writeToEnvFile('DEFAULT_CURRENCY_SYMBOL_ALIGNMENT', '0');


        writeToEnvFile('DEFAULT_LANGUAGE', 'en');

        cacheClear();
    }

    public function drop_all_tables()
    {
        Schema::disableForeignKeyConstraints();

        foreach (DB::select('SHOW TABLES') as $table) {
            $table_array = get_object_vars($table);
            Schema::drop($table_array[key($table_array)]);
        }
    }

    public function import_demo_sql()
    {
        cacheClear();
        $sql_path = base_path('demo.sql');
        DB::unprepared(file_get_contents($sql_path));
    }


    public function cron_2()
    {
        if (env('DEMO_MODE') != 'On') {
            return back();
        }
        $this->remove_folder();
        $this->extract_uploads();
    }

    public function extract_uploads()
    {
        $zip = new ZipArchive;
        $zip->open(base_path('public/uploads.zip'));
        $zip->extractTo('public/uploads');
    }

    public function remove_folder()
    {
        File::deleteDirectory(base_path('public/uploads'));
    }
}
