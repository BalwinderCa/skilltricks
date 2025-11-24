<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Models\PWASettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use App\Http\Services\PWAManifestService;
use App\Http\Requests\PWASettingRequestForm;

class PWASettingsController extends Controller
{
    public function manifestJson()
    {
        $output = (new PWAManifestService)->generate();
        return response()->json($output);
    }
    public function index()
    {
        return view('backend.pages.systemSettings.pwa_settings');
    }
    public function store(PWASettingRequestForm $request)
    {
        $path = 'public/images/icons/';
        $start_url =$request->start_url;
        $public_start_url =$request->start_url;
        if ($request->name) {
            config(['laravelpwa.manifest.name' => $request->name]);
            $name = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
            file_put_contents(config_path('laravelpwa.php'), $name);

            config(['laravelpwa.name' => $request->name]);
            $name = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
            file_put_contents(config_path('laravelpwa.php'), $name);
        }
        if ($request->short_name) {
            config(['laravelpwa.manifest.short_name' => $request->short_name]);
            $short_name = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
            file_put_contents(config_path('laravelpwa.php'), $short_name);
        }
        if ($request->start_url) {
            config(['laravelpwa.manifest.start_url' => $request->start_url]);
            $start_url = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
            file_put_contents(config_path('laravelpwa.php'), $start_url);
        }

        if ($request->background_color) {
            config(['laravelpwa.manifest.background_color' => $request->background_color]);
            $background_color = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
            file_put_contents(config_path('laravelpwa.php'), $background_color);
        }

        if ($request->theme_color) {
            config(['laravelpwa.manifest.theme_color' => $request->theme_color]);
            $theme_color = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
            file_put_contents(config_path('laravelpwa.php'), $theme_color);
        }

        if ($request->status_bar) {
            config(['laravelpwa.manifest.status_bar' => $request->status_bar]);
            $status_bar = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
            file_put_contents(config_path('laravelpwa.php'), $status_bar);
        }

        if ($request->icon_72) {
            $file_path = $this->fileUpload($path, $request->file('icon_72'), 'icon-72x72.png', $public_start_url);
            config(['laravelpwa.manifest.icons.72x72.path' => $file_path]);
            $file_path = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
            file_put_contents(config_path('laravelpwa.php'), $file_path);
        }
        if ($request->icon_96) {
            $file_path_96 = $this->fileUpload($path, $request->file('icon_96'), 'icon-96x96.png', $public_start_url);
            config(['laravelpwa.manifest.icons.96x96.path' => $file_path_96]);
            $file_path_96 = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
            file_put_contents(config_path('laravelpwa.php'), $file_path_96);
        }
        if ($request->icon_128) {
            $file_path_128 = $this->fileUpload($path, $request->file('icon_128'), 'icon-128x128.png', $public_start_url);
            config(['laravelpwa.manifest.icons.128x128.path' => $file_path_128]);
            $file_path_128 = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
            file_put_contents(config_path('laravelpwa.php'), $file_path_128);
        }
        if ($request->icon_144) {
            $file_path_144 = $this->fileUpload($path, $request->file('icon_144'), 'icon-144x144.png', $public_start_url);
            config(['laravelpwa.manifest.icons.144x144.path' => $file_path_144]);
            $file_path_144 = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
            file_put_contents(config_path('laravelpwa.php'), $file_path_144);
        }
        if ($request->icon_152) {
            $file_path_152 = $this->fileUpload($path, $request->file('icon_152'), 'icon-152x152.png', $public_start_url);
            config(['laravelpwa.manifest.icons.152x152.path' => $file_path_152]);
            $file_path_152 = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
            file_put_contents(config_path('laravelpwa.php'), $file_path_152);
        }
        if ($request->icon_192) {
            $file_path_192 = $this->fileUpload($path, $request->file('icon_192'), 'icon-192x192.png', $public_start_url);
            config(['laravelpwa.manifest.icons.192x192.path' => $file_path_192]);
            $file_path_192 = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
            file_put_contents(config_path('laravelpwa.php'), $file_path_192);
        }
        if ($request->icon_384) {
            $file_path_384 = $this->fileUpload($path, $request->file('icon_384'), 'icon-384x384.png', $public_start_url);
            config(['laravelpwa.manifest.icons.384x384.path' => $file_path_384]);
            $file_path_384 = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
            file_put_contents(config_path('laravelpwa.php'), $file_path_384);
        }
        if ($request->icon_512) {
            $file_path_512 = $this->fileUpload($path, $request->file('icon_512'), 'icon-512x512.png', $public_start_url);
            config(['laravelpwa.manifest.icons.512x512.path' => $file_path_512]);
            $file_path_512 = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
            file_put_contents(config_path('laravelpwa.php'), $file_path_512);
        }

        config(['laravelpwa.manifest.start_url' => URL::to('/')]);
        $start_url = '<?php return ' . var_export(config('laravelpwa'), true) . ';';
        file_put_contents(config_path('laravelpwa.php'), $start_url);

        cacheClear();

        flash(localize('Operation successfully'))->success();
        return redirect()->route('admin.settings.pwa');
    }
    public  function fileUpload($path, $file, $name, $start_url = null)
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $fileName = $path . $name;
        if (file_exists($fileName)) {
            try {
                unlink($fileName);
            } catch (\Throwable $th) {}
        }

        $file->move($path, $name);
        $fileName = $path . $name;

        return $start_url.'/'.$fileName;
    }
}
