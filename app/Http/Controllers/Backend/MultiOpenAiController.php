<?php

namespace App\Http\Controllers\Backend;


use App\Http\Controllers\Controller;
use App\Http\Requests\MultiOpenAiKeyRequestForm;
use App\Models\OpenAiKey;
use App\Services\OpenAiKeyHealth\OpenAiKeyHealthService;
use Illuminate\Http\Request;
use Orhanerday\OpenAi\OpenAi;

class MultiOpenAiController extends Controller
{
    # construct
    public function __construct()
    {
//        $this->middleware(['permission:multiOpenAi'])->only('index');
//        $this->middleware(['permission:add_multiOpenAi'])->only(['create', 'store']);
//        $this->middleware(['permission:edit_multiOpenAi'])->only(['edit', 'update']);
//        $this->middleware(['permission:status_multiOpenAi'])->only(['updateStatus']);
//        $this->middleware(['permission:delete_multiOpenAi'])->only(['delete']);
    }

    # API Key List
    public function index(Request $request)
    {
        $searchKey = $request->search ?? null;
        $status = $request->status ?? null;

        $openAiKeys = OpenAiKey::oldest()->when($searchKey, function ($q) use ($searchKey) {
            $q->where('title', 'like', '%' . $searchKey . '%');
        })->when(in_array($status, [0, 1]) && $status != null, function ($q) use ($status) {
            $q->where('is_active',  $status);
        })->paginate(paginationNumber());

        return view('backend.pages.systemSettings.openAi.multiOpenAiKey', compact('openAiKeys', 'searchKey', 'status'));
    }


    # create new api key page
    public function create()
    {
        return view('backend.pages.systemSettings.openAi.createUpdate');
    }


    # store api key
    public function store(MultiOpenAiKeyRequestForm $request)
    {
        OpenAiKey::create($this->formatParams($request->all()));
        flash(localize('API Key has been inserted successfully'))->success();
        return redirect()->route('admin.multiOpenAi.index');
    }


    # edit api key
    public function edit($id)
    {
        $editApiKey = OpenAiKey::findOrFail($id);
        return view('backend.pages.systemSettings.openAi.createUpdate', compact('editApiKey'));
    }


    # update api key
    public function update(MultiOpenAiKeyRequestForm $request)
    {

        $id             = $request->id;
        $model          = OpenAiKey::findOrFail($id);
        $model->update($this->formatParams($request->all(), $id));
        flash(localize('API Key has been Updated successfully'))->success();
        return redirect()->route('admin.multiOpenAi.index');
    }


    # format parameters
    # return array
    private function formatParams($payload, $model_id = null): array
    {
        $params = [
            'engine' => $payload['engine'],
            'status' => $payload['status'],
            'api_key'    => $payload['api_key'],
        ];
        if ($model_id) {
            $params['updated_by'] = auth()->user()->id;
        } else {
            $params['created_by'] = auth()->user()->id;
        }
        return $params;
    }


    # status change
    public function updateStatus(Request $request)
    {
        $openAiKey = OpenAiKey::findOrFail($request->id);
        $openAiKey->is_active = $request->is_active;
        if ($openAiKey->save()) {
            return 1;
        }
        return 0;
    }


    # delete api key
    public function delete($id)
    {
        $model = OpenAiKey::findOrFail($id);
        $model->delete();
        flash(localize('API Key has been Deleted successfully'))->success();
        return redirect()->route('admin.multiOpenAi.index');
    }
    # open ai key supported model
    public function openAiModeList(int $id)
    {
        $model      = OpenAiKey::findOrFail($id);
        if($model->engine != 1) {
            flash(localize('Operation failed'))->error();
            return redirect()->back();
        }
        $open_ai    = new OpenAi($model->api_key);
        $models     = $open_ai->listModels();
        $models     = json_decode($models);
        $models     = $models->data;

        return view('backend.pages.systemSettings.openAi.model_details', compact('models'));
    }

    public function checkKeyHealth(Request  $request, OpenAiKeyHealthService $aiKeyHealthService)
    {
        return  $aiKeyHealthService->echoOpenAiApiKeyHealth();
    }
}
