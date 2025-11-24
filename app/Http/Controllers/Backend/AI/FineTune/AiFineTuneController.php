<?php

namespace App\Http\Controllers\Backend\AI\FineTune;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\FineTune\FineTuneStoreRequest;
use App\Services\FineTune\FineTuneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiFineTuneController extends Controller
{
    public function index(Request $request, FineTuneService $fineTuneService)
    {
        try {
            $fineTuneJobs = $fineTuneService->fineTuningJobs();
            $data["jobs"] = convertJsonDecode($fineTuneJobs);

            return view("backend.pages.fine-tunes.jobs")->with($data);
        }
        catch (\Throwable $e){

            Log::info("Error: " . $e->getMessage());
            flash($e->getMessage())->error();
            return back();
        }


        // $data["fineTunes"] = $fineTuneService->getAll(true, "histories");

        // return view("backend.pages.fine-tunes.index")->with($data);
    }

    public function create(FineTuneService $fineTuneService)
    {
        $data["models"] = $fineTuneService->getAll();

        return view("backend.pages.fine-tunes.add-fine-tune")->with($data);
    }

    /**
     * Create a new Fine Tune
     *
     * Step 1 : Validate the request
     * Step 2 : Store the fine tune Data as new fine_tunes data
     * Step 3 : Make a call to create the fine tune upload file
     * Step 4 : Collect the Trained fine tune File ID
     * Step 5 : Update the Fine Tune ID at fine_tunes table & fine_tune_histories
     * Step 6 : Create Fine Tune Job
     * Step 7 : Collect the response
     * Step 8 : Update the Fine Tune Table & fine_tune_histories table.
     * Step 9 : Return the response.
     * */
    public function store(FineTuneStoreRequest $request, FineTuneService $fineTuneService)
    {
        try {
            DB::beginTransaction();

            // Step 2 : Store the fine tune Data as new fine_tunes data
            $fineTune = $fineTuneService->store($request);

            // Step 2 : Store the fine tune Data as new fine_tunes data
            // Save FineTune History
            $fineTuneHistory  = $fineTuneService->storeHistory($request, $fineTune);

            // Fine-Tuning File Upload
            // Step 3         : Make a call to create the fine tune upload file
            $fineTuningOpenAi = $fineTuneService->fineTuneOpenAiFileUpload($fineTuneHistory);


            // Step 4 : Collect the Trained fine tune File ID
            $jsonDecodeFineTune = convertJsonDecode($fineTuningOpenAi);

            $isOpenAiRaiseError = isOpenAiRaiseError($jsonDecodeFineTune);

            if ($isOpenAiRaiseError != false) {
                flash($isOpenAiRaiseError)->error();
                DB::rollBack();

                return redirect()->back();
            }


            // JSON File Save into local directory & Database
            $fineTuneService->storeJsonLineFileIntoFineTuneHistory($fineTuneHistory, $request->file("training_data_file"));


            // Step 5 : Update the Fine Tune ID at fine_tunes table & fine_tune_histories
            // Update Fine Tune & Fine Tune History Status & Status Details
            $fineTuneService->updateFineTuneFileUploadModel($fineTune, $fineTuneHistory, $jsonDecodeFineTune);


            // Step 6 :Start Fine Tune Job #
            $createFineTune = $fineTuneService->createFineTune($fineTune->model_engine, $fineTune->file_model_id);

            // Step 7 : Collect the response
            $decodedFineTuneJob = convertJsonDecode($createFineTune);


            $isOpenAiRaiseError = isOpenAiRaiseError($decodedFineTuneJob);

            if ($isOpenAiRaiseError != false) {

                DB::rollBack();

                flash($isOpenAiRaiseError)->error();

                return redirect()->route("fine-tunes.create");
            }

            // Step 8 : Update the Fine Tune Table & fine_tune_histories table.
            // Update Job Fine Tune
            $fineTuneService->updateJobFineTune($fineTune, $fineTuneHistory, $decodedFineTuneJob);

            flash("Fine Tune has been created successfully.")->success();

            DB::commit();

            // Step 9 : Return the response
            return redirect()->route("fine-tunes.index");
        } catch (\Throwable $e) {
            DB::rollBack();


            flash($e->getMessage())->error();
            commonLog("Failed to store fine tune", errorArray($e));

            return redirect()->back()->withInput();
        }
    }

    public function jobs(Request $request, FineTuneService $fineTuneService)
    {
        try {
            $fineTuneJobs = $fineTuneService->fineTuningJobs();
            $data["jobs"] = convertJsonDecode($fineTuneJobs);

            return view("backend.pages.fine-tunes.jobs")->with($data);
        }
        catch (\Throwable $e){

        }
    }


    public function getByFineTuneJobId($fineTuneJobId, FineTuneService $fineTuneService)
    {
        try {
            $fineTuneJobs = $fineTuneService->findByFineTuneJobId($fineTuneJobId);
            $data["job"]  = convertJsonDecode($fineTuneJobs);

           return $data["job"];
        }
        catch (\Throwable $e){

        }
    }

    public function cancelFineTuneJobByJobId($fineTuneJobId, FineTuneService $fineTuneService)
    {
        try {
            $fineTuneJobs = $fineTuneService->cancelFineTuneJobId($fineTuneJobId);
            $data["job"]  = convertJsonDecode($fineTuneJobs);
            return $data["job"];
        }
        catch (\Throwable $e){

        }
    }

    public function deleteFineTuneJobByJobId($fineTuneJobId, FineTuneService $fineTuneService)
    {
        try {
            $fineTuneJobs = $fineTuneService->deleteFineTuneJobId($fineTuneJobId);
            $data["job"]  = convertJsonDecode($fineTuneJobs);
            return $data["job"];
        }
        catch (\Throwable $e){

        }
    }
}
