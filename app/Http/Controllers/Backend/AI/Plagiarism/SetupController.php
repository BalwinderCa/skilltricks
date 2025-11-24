<?php

namespace App\Http\Controllers\Backend\AI\Plagiarism;

use Copyleaks\Copyleaks;
use Illuminate\Http\Request;
use App\Models\PlagiarismSettings;
use App\Http\Controllers\Controller;
use App\Http\Services\PlagiarismService;
use App\Http\Requests\PlagiarismRequestForm;

class SetupController extends Controller
{
    public function index()
    {
        $settings = PlagiarismSettings::query()->paginate(paginationNumber());
        return view('backend.pages.plagiarism.settings.index-settings', compact('settings'));
    }
    public function create()
    {
    }
    public function store(PlagiarismRequestForm $request)
    {
        try {
            PlagiarismSettings::create($this->formattedParams($request));
            flash(localize('Operation Successfully'))->success();
            return redirect()->back();
        } catch (\Throwable $th) {
            flash($th->getMessage())->error();
            return redirect()->back();
        }
    }
    public function formattedParams($request, $model = null): array
    {
        $params = [
            'key' => $request->key,
           
        ];
        $model ?  $params['updated_by'] : $params['created_by'] = auth()->user()->id;

        $params['is_active'] = self::activeKey() ? 0 : 1;
     

        return $params;
    }
    public function edit(int $id)
    {
    }
    public function destroy(int $id)
    {
    }
    public  static function activeKey()
    {
        return  PlagiarismSettings::where('is_active', 1)->value('key'); 
    }
    public  static function plagiarismInstance()
    {        
        return  new PlagiarismService(self::activeKey()); 
    }

    
}
