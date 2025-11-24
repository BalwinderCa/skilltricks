<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\ThemeRequestForm;

class ThemeController extends Controller
{
    public function index()
    {
        $data = self::loadData();
        return view('backend.pages.theme.index', $data);
    }
    public function store(ThemeRequestForm $request)
    {
        try {
            Theme::create($this->formattedParams($request));
            flash(localize('Theme Created Successfully'))->success();
            return redirect()->route('admin.theme.index');
        } catch (\Throwable $th) {
            flash($th->getMessage())->error();
            return redirect()->back();
        }
    }
    public function edit(ThemeRequestForm $request, $id)
    {
        $theme = self::singleTheme($id);
        return view('backend.pages.theme.edit-theme', compact('theme'));
    }
    public function update(ThemeRequestForm $request, $id)
    {
        try {
            $model = self::singleTheme($id);
            if ($model) {
                $model->update($this->formattedParams($request, $id));
            }
            flash(localize('Theme updated successfully'))->success();
            return redirect()->route('admin.theme.index');
        } catch (\Throwable $th) {
            flash($th->getMessage())->error();
            return redirect()->back();
        }
    }
    private function formattedParams($request, $modelId = null): array
    {
        $file_path = 'public/uploads/theme/';
        $params = [
            'name' => $request->name,
            'is_default' => $request->is_default == 'on' ? 1 : 0
        ];
        if ($modelId) {
            $params['updated_by'] = auth()->user()->id;
            $model = Theme::where('id', $modelId)->first();
            if ($request->preview_image) {
                $params['preview_image'] = fileUpdate($model->preview_image, $file_path, $request->preview_image);
            }
            if ($request->full_image) {
                $params['full__image'] = fileUpdate($model->full_image, $file_path, $request->full__image);
            }
        } else {
            $params['preview_image'] = fileUpload($file_path, $request->preview_image);
            $params['full__image'] = fileUpload($file_path, $request->full__image);
            $params['created_by'] = auth()->user()->id;
        }
        return $params;
    }
    private static function singleTheme(int $modelId)
    {
        return Theme::findOrFail($modelId);
    }
    public static function loadData(): array
    {
        $data = [];
        $data['themes'] = Theme::all();
        return $data;
    }
    public function activeTheme(Request $request)
    {
    }
    public function changeStatus(Request $request)
    {

        try {
            $id     = $request->id;
            $status = $request->status;
            if ($status == 'active_now') {
                DB::table('themes')->update(['is_default' => 0]);
                $theme = Theme::where('id', $id)->first();
                if ($theme) {
                    writeToEnvFile('APP_THEME', $theme->code);
                    $theme->update(['is_default' => 1]);
                }
            }
            $defaultTheme = Theme::where('is_default', 1)->first();
            if (!$defaultTheme) {
                Theme::where('code', 'default')->update(['is_default' => 1]);
                writeToEnvFile('APP_THEME', 'default');
            }
            cacheClear();
            flash(localize('Theme updated successfully'))->success();
            return redirect()->route('admin.theme.index');
        } catch (\Throwable $th) {
            flash($th->getMessage())->error();
            return redirect()->back();
        }
    }
}
