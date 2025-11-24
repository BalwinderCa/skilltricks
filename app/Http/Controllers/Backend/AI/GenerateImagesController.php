<?php

namespace App\Http\Controllers\Backend\AI;


use Carbon\Carbon;
use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Orhanerday\OpenAi\OpenAi;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class GenerateImagesController extends Controller
{
    public function __construct()
    {
        if (getSetting('enable_ai_images') == '0') {
            redirect()->route('writebot.dashboard')->send();
        }
    }
    # dalle 3 image index
    public function dallE3(Request $request)
    {
        $user = auth()->user();

        if (isCustomer()) {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_dall_e_3_image == 0) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('dall_e_3')) {
                abort(403);
            }
        }

        $searchKey = null;


        $imagesDalle3 = Project::where('content_type', 'image')->where('model_name','dall-e-3')->where('engine', 'OpenAI')->where('user_id', auth()->user()->id)->latest();

        if ($request->search != null) {
            $imagesDalle3 = $imagesDalle3->where('title', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        $imagesDalle3 = $imagesDalle3->paginate(paginationNumber());
        return view('backend.pages.templates.generate-images-dall-e-3', ['imagesDalle3' => $imagesDalle3, 'searchKey' => $searchKey]);
    }
    # images dall e 2
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->user_type == "customer") {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_dall_e_2_image == 0) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('dall_e_2')) {
                abort(403);
            }
        }

        $searchKey = null;
        $imagesDalle2 = Project::where('content_type', 'image')->where('model_name','dall-e-2')->where('engine', 'OpenAI')->where('user_id', auth()->user()->id)->latest();

        if ($request->search != null) {
            $imagesDalle2 = $imagesDalle2->where('title', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        $imagesDalle2 = $imagesDalle2->paginate(paginationNumber());

        $imagesDalle3 = Project::where('content_type', 'image')->where('model_name','dall-e-3')->where('engine', 'OpenAI')->where('user_id', auth()->user()->id)->latest();

        if ($request->search != null) {
            $imagesDalle3 = $imagesDalle3->where('title', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        $imagesDalle3 = $imagesDalle3->paginate(paginationNumber());
        return view('backend.pages.templates.generate-images', ['imagesDalle3' => $imagesDalle3, 'imagesDalle2'=>$imagesDalle2, 'searchKey' => $searchKey]);
    }

    # generate images
    public function generate(Request $request)
    {

        if (env('DEMO_MODE') == "On") {
            $data = [
                'status'  => 400,
                'success' => false,
                'message' => localize('Image generation is turned off in demo')
            ];
            return $data;
        }

        $user = auth()->user();

        # 1. init openAi
        $open_ai = new OpenAi(openAiKey());

        # 2. verify if user has access to the template [template available in subscription package]
        if ($user->user_type == "customer") {
            // check package balance
            $checkBalanceData = activePackageBalance('allow_images');
            if (!empty($checkBalanceData)) {
                return $checkBalanceData;
            }

            // check images limit
            if (availableDataCheck('images')  < (int)$request->num_of_results) {
                $data = [
                    'status'  => 400,
                    'success' => false,
                    'message' => localize('Your limit is low, please upgrade you plan'),
                ];
                return $data;
            }
        }

        # 4. generate prompt in selected language
        $parsePromptsController = new ParsePromptsController;
        $prompt                 = $parsePromptsController->images($request->all());


        # 6. generate image
        $n              = 1;
        $resolution     = '256x256';


        $n              = (int)$request->num_of_results;

        $model          = $request->model_name ?? 'dall-e-2';
        if($model == 'dall-e-3'){
            $resolution     = $request->resolution;
            $opts = [
                'model'  => $model,
                'prompt' => $prompt,
                'size'  => $resolution,
                'n'     => $n,
                'quality'=>$request->quality ? $request->quality : 'standard',
                "response_format" => "url",
            ];
        }else{
            $resolution     = $request->resolution;
            $opts = [
                'model'  => $model,
                'prompt' => $prompt,
                'size'  => $resolution,
                'n'     => $n,
                "response_format" => "url",
            ];
        }

        $result = $open_ai->image($opts);

        # parse response
        $result = json_decode($result, true);

        if (isset($result['data'])) {

            if (count($result['data']) > 1) {
                foreach ($result['data'] as $key => $value) {
                    $url = $value['url'];



                    $name = Str::random(10) . '.png';
                    $file_path = 'images/' . $name;



                    // aws  storage
                    if(activeStorage('aws')){
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl, CURLOPT_URL, $url);
                        $contents = curl_exec($curl);
                        curl_close($curl);
                        Storage::disk('s3')->put('images/' . $name, $contents, 'public');
                        $file_path = Storage::disk('s3')->url('images/' . $name);
                    }else{
                        $image = file_get_contents($url);
                        file_put_contents(public_path($file_path), $image);
                    }
                    // end
                    $project = new Project;
                    $project->user_id       = $user->id;
                    $project->title         = $request->title . '-' . ($key + 1);
                    $project->slug          = preg_replace('/\s+/', '-', trim($project->title)) . '-' . strtolower(Str::random(5));
                    $project->content_type  = 'image';
                    $project->model_name    = $model;
                    $project->resolution    = $resolution;
                    $project->content       = $file_path;
                    $project->storage_type       = activeStorage('aws') ? 'aws' : 'local';
                    $project->save();
                }
            } else {
                $url = $result['data'][0]['url'];
                $name = Str::random(10) . '.png';
                $file_path = 'images/' . $name;
                $image = file_get_contents($url);
                file_put_contents(public_path($file_path), $image);

                // aws s3 storage
                if(activeStorage('aws')){
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl, CURLOPT_URL, $url);
                    $contents = curl_exec($curl);
                    curl_close($curl);
                    Storage::disk('s3')->put('images/' . $name, $contents, 'public');
                    $file_path = Storage::disk('s3')->url('images/' . $name);
                }
                // end
                $project = new Project;
                $project->user_id       = $user->id;
                $project->title         = $request->title;
                $project->slug          = preg_replace('/\s+/', '-', trim($project->title)) . '-' . strtolower(Str::random(5));
                $project->content_type  = 'image';
                $project->model_name    = $model;
                $project->resolution    = $resolution;
                $project->content       = $file_path;
                $project->storage_type  = activeStorage('aws') ? 'aws' : 'local';
                $project->save();
            }

            $latestPackage = activePackageHistory();
            $previousBalance = $latestPackage ? $latestPackage->this_month_available_images : null;
            $after_balance = $latestPackage ? $latestPackage->this_month_available_images - 1 : null;
            # keep log
            $logData = [
                'user_id' => $project->user_id,
                'project_id' => $project->id,
                'subscription_history_id' => optional(activePackageHistory())->id,
                'subscription_package_id' => optional(activePackageHistory())->subscription_package_id,
                'content' => $project->content,
                'content_type' => $project->content_type,
                'resolution' => $project->resolution,
                'previous_balance' => $previousBalance,
                'after_balance' => $after_balance
            ];
            $generateController = new GenerateContentsController();
            $generateController->createLog($logData);

            # Update credit balance
            $this->updateUserImages($user, $n);


            $images = Project::where('content_type', 'image')->where('engine', 'OpenAI')
            ->when($request->model_name, function($q) use($request){
                $q->where('model_name', $request->model_name);
            })->where('user_id', auth()->user()->id)->latest();
            $images = $images->paginate(paginationNumber());

            $data = [
                'status'            => 200,
                'success'           => true,
                'images'            => view('backend.pages.templates.inc.images-list', compact('images'))->render(),
                'usedPercentage'    => view('backend.pages.templates.inc.used-images-percentage')->render(),
            ];
            return $data;
        } else {
            $message = $result['error']['message'];
            $data = [
                'status'  => 400,
                'success' => false,
                'message' => $message
            ];
            return $data;
        }

        $data = [
            'status'  => 500,
            'success' => false,
        ];
        return $data;
    }

    # updateUserImages - take n
    public function updateUserImages($user, $n)
    {
        if ($user->user_type == "customer") {
            updateDataBalance('images', $n, $user);
        }
    }

    # delete image
    public function delete($id)
    {
        $image = Project::where('user_id', auth()->user()->id)->where('id', $id)->first();
        if (!is_null($image)) {
            try {
                if($image->storage_type == 'aws') {
                    $image_name = explode('.com/', $image->content);
                    Storage::disk('s3')->delete($image_name[1]);
                }else{
                    fileDelete($image->content);
                }

            } catch (\Throwable $th) {
                //throw $th;
            }
            $image->delete();
        }

        flash(localize('Image has been deleted successfully'))->success();
        return back();
    }
}
