<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use App\Http\Services\SystemUpdateService;
use App\Http\Requests\LicenseVerificationRequestForm;
use App\Http\Controllers\Backend\Templates\TemplatesController;
use App\Models\License;

class SetupController extends Controller
{

    # init installation
    public function init()
    {

        $this->writeToEnvFile('APP_URL', URL::to('/'));
        return view('setup.init');
    }

    # checklist
    public function checklist()
    {
        $permission['curl_enabled']           = function_exists('curl_version');
        $permission['file_get_contents']      = function_exists('file_get_contents');
        $permission['file_put_contents']      = function_exists('file_put_contents');
        $permission['db_file_write_perm']     = is_writable(base_path('.env'));
        $permission['routes_file_write_perm'] = is_writable(base_path('app/Providers/RouteServiceProvider.php'));
        $permission['server_connection']      = true;
        return view('setup.checklist', compact('permission'));
    }

    #purchase code validation
    public function purchase()
    {
        return view('setup.license');
    }

    #purchase code validation
    public function purchaseCode(LicenseVerificationRequestForm $request)
    {

        try {
            $opts = [
                'purchase_code'        => $request->purchase_code,
                'app_name'             => env('APP_NAME'),
                'current_version'      => env('APP_VERSION'),
                'customer_current_url' => URL::to('/'),
                'product_type'         => 1,
                'server_info'          => $_SERVER,
                'app_env'              => $request->server_mode,
            ];
            session()->put('app_env', $request->server_mode);
            $message = null;
            $systemService = new SystemUpdateService();
            $healthCheck = $systemService->healthCheck(['data' => '']);
            if ($healthCheck == true) {
                $response = json_decode($systemService->verification($opts));
                if ($response) {
                    if ($response->status == true && $response->code == 201) {
                        file_put_contents(storage_path('app/') . '.access_token', $response->data->client_token);
                        file_put_contents(storage_path('app/') . '.purchase_code', $response->data->purchase_code);
                        $message = $response->message;
                    }
                    if ($response->status == false || $response->code == 525) {
                        $message = $response->message;

                        flash($message)->error();
                        return redirect()->route('installation.purchase');
                    }

                    flash($message)->success();
                    return redirect()->route('installation.dbSetup');
                }
                flash($message)->error();
                return redirect()->route('installation.purchase');
            }
            return redirect()->route('installation.dbSetup');
        } catch (\Throwable $th) {
            flash($th->getMessage())->error();
            return redirect()->back();
        }
    }
    # db form
    public function databaseSetup($error = "")
    {
        $systemService = new SystemUpdateService();
        $healthCheck = $systemService->healthCheck(['data' => '']);
        if($healthCheck) {
            $purchase_code =  file_get_contents(storage_path('app/') . '.purchase_code');
            $client_token = file_get_contents(storage_path('app/') . '.access_token');
            if (empty($purchase_code) || empty($client_token)) {
                flash('Your application not verified')->error();
                return redirect()->route('installation.purchase');
            }
        }

        if ($error == "") {
            return view('setup.dbSetup');
        } else {
            return view('setup.dbSetup', compact('error'));
        }
    }

    # db store
    public function storeDatabaseSetup(Request $request)
    {

        if ($this->checkDatabaseConnection($request->DB_HOST, $request->DB_DATABASE, $request->DB_USERNAME, $request->DB_PASSWORD)) {
            $path = base_path('.env');

            if (file_exists($path)) {

                foreach ($request->types as $type) {
                    $this->writeToEnvFile($type, $request[$type]);
                }
                return redirect('db-migration');
            } else {
                // fallback
                return redirect('database-setup');
            }
        } else {
            // db connection error
            return redirect('database-setup/database_error');
        }
    }

    # overwrite env file
    public function writeToEnvFile($key, $value)
    {

        $env = file_get_contents(base_path() . '/.env');
        $env = explode("\n", $env);
        foreach ($env as $env_key => $env_value) {
            $entry = explode("=", $env_value, 2);

            if ($entry[0] === $key) {
                $env[$env_key] = $key . "=" . (is_string($value) ? '"' . $value . '"' : $value);
            } else {
                $env[$env_key] = $env_value;
            }
        }
        $env = implode("\n", $env);
        file_put_contents(base_path() . '/.env', $env);

        return true;
    }

    # check db connection
    function checkDatabaseConnection($db_host = "", $db_name = "", $db_user = "", $db_pass = "")
    {
        if (@mysqli_connect($db_host, $db_user, $db_pass, $db_name)) {
            return true;
        } else {
            return false;
        }
    }

    # db migration confirmation view
    public function dbMigration()
    {
        try {
            $systemService = new SystemUpdateService();
            $healthCheck = $systemService->healthCheck(['data' => '']);
            if($healthCheck == true) {
                if (file_exists(storage_path('app/') . '.purchase_code') && file_exists(storage_path('app/') . '.access_token')) {
                    $purchase_code =  file_get_contents(storage_path('app/') . '.purchase_code');
                    $client_token = file_get_contents(storage_path('app/') . '.access_token');
                    if (empty($purchase_code) || empty($client_token)) {
                        flash('Your application not verified')->error();
                        return redirect()->route('installation.purchase');
                    }
                } else if (file_exists(storage_path('app/') . '.purchase_code') == false || file_exists(storage_path('app/') . '.access_token') == false) {
                    flash('Your application not verified')->error();
                    return redirect()->route('installation.purchase');
                }
            }
            
            if ($this->checkDatabaseConnection(env('DB_HOST'), env('DB_DATABASE'), env('DB_USERNAME'), env('DB_PASSWORD'))) {
                return view('setup.dbMigration');
            } else {
                // db connection error
                return redirect('database-setup/database_error');
            }
        } catch (\Throwable $th) {
            flash('Your application not verified')->error();
            return redirect()->route('installation.purchase');
        }
    }

    # run db migration
    public function runDbMigration($demo = false)
    {

        if ($demo) {
            $this->runDemoDbMigration();
        } else {

            # run migrations  here
            Artisan::call('migrate:refresh');


            # import templates
            $templatesController = new TemplatesController();
            $templatesController->store();

            # run seeds here
            Artisan::call('db:seed');

            $purchase_code = null;
            $client_token = null;
            $systemService = new SystemUpdateService();
            $healthCheck = $systemService->healthCheck(['data' => '']);

            if($healthCheck == true) {
                if (file_exists(storage_path('app/') . '.purchase_code') && file_exists(storage_path('app/') . '.access_token')) {
                    $purchase_code =  file_get_contents(storage_path('app/') . '.purchase_code');
                    $client_token = file_get_contents(storage_path('app/') . '.access_token');
                    $client_token = file_get_contents(storage_path('app/') . '.access_token');
                }
                $license = new License();

                $license->purchase_code = $purchase_code;
                $license->client_token = $client_token;
                $license->app_env = session()->get('app_env');
                $license->save();
            }
        }

        cacheClear();
        return redirect()->route('installation.storeAdminForm');
    }

    # run Demo db migration
    public function runDemoDbMigration($name = 'demo')
    {
        // TODO:: [update version] demo seeders 
        ini_set('memory_limit', '-1');
        $this->writeToEnvFile('DEMO_MODE', 'On');
        $sql_path = base_path($name . '.sql');
        DB::unprepared(file_get_contents($sql_path));
    }

    # add admin form view
    public function storeAdminForm()
    {

        if ($this->checkDatabaseConnection(env('DB_HOST'), env('DB_DATABASE'), env('DB_USERNAME'), env('DB_PASSWORD'))) {
            return view('setup.adminConfig');
        } else {
            // db connection error
            return redirect('database-setup/database_error');
        }
    }

    # admin configuration
    public function storeAdmin(Request $request)
    {
        $user = User::where('user_type', 'admin')->first();
        $user->name      = $request->admin_name;
        $user->email     = $request->admin_email;
        $user->password  = Hash::make($request->admin_password);
        $user->email_verified_at = date('Y-m-d H:m:s');
        $user->save();

        $oldRouteServiceProvider        = base_path('app/Providers/RouteServiceProvider.php');
        $setupRouteServiceProvider      = base_path('app/Providers/SetupServiceComplete.php');

        copy($setupRouteServiceProvider, $oldRouteServiceProvider);
        return view('setup.complete');
    }
}
