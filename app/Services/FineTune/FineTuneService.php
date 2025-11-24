<?php

namespace App\Services\FineTune;

use App\Models\FineTune;
use App\Models\FineTuneHistory;
use App\Traits\FileProcessTrait;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;


class FineTuneService
{
    use FileProcessTrait;

    public function getAll($paginateOrGetData = null, $withRelationship = [])
    {
        $query = FineTune::query()->userId(userId());

        // Relationship Bindings
        (empty($withRelationship) ? $query : $query->with($withRelationship));


        if(is_null($paginateOrGetData)){
            return $query->pluck("ft_model_id", "id");
        }

        return $paginateOrGetData ? $query->paginate(10) : $query->get();
    }

    public function store($request)
    {
        $validated = $request->validated();



        return  FineTune::query()->create($validated);
    }


    public function storeHistory($request, object $fineTune)
    {
        $data                 = $request->validated();
        $data["fine_tune_id"] = $fineTune->id;

        return FineTuneHistory::query()->create($data);
    }

    public function storeJsonLineFileIntoFineTuneHistory(object $fineTuneHistory, $file)
    {
        // JSON File Save into local directory & Database
            $data["trained_file_path"] = $this->fileProcess(
                $file,
                appStatic()::FINE_TUNE_JSONL_DIR
            );

            $data["training_data"] = getFileContents("public/".$data["trained_file_path"]);

            $fineTuneHistory->update($data);

            return $fineTuneHistory;
    }

    public function updateFineTuneFileUploadModel(object $fineTune, object $fineTuneHistory, $fineTuneTrainingResponse)
    {
        Log::info("Fine Tune Training Response : ".json_encode($fineTuneTrainingResponse));

        Log::info("#fine_tunes Before Updating Fine Tune Table : ".json_encode($fineTune));

        // Updating Fine Tune
        $fineTune->update([
            "file_model_id"       => $fineTuneTrainingResponse["id"],
            "status"               => $fineTuneTrainingResponse["status"],
            "status_details"       => $fineTuneTrainingResponse["status_details"],
            "file_upload_response" => $fineTuneTrainingResponse
        ]);

        Log::info("#fine_tunes After Updating Fine Tune Table : ".json_encode($fineTune));
        Log::info("#fine_tune_histories Before Updating Fine Tune History Table : ".json_encode($fineTuneHistory));

        // Updating Fine Tune History
        $fineTuneHistory->update([
            "status"               => $fineTuneTrainingResponse["status"],
            "status_details"       => $fineTuneTrainingResponse["status_details"],
            "file_upload_response" => $fineTuneTrainingResponse
        ]);

        Log::info("#fine_tune_histories After Updating Fine Tune History Table : ".json_encode($fineTuneHistory));
    }


    public function updateJobFineTune(object $fineTune, object $fineTuneHistory, $jobFineTuneResponse)
    {
        // Update Fine Tune
        $fineTune->update([
            "ft_model_id" => $jobFineTuneResponse["id"],
            "fine_tune_job_response" => $jobFineTuneResponse
        ]);

        // Fine Tune History
        $fineTuneHistory->update([
            "ft_model_id"            => $jobFineTuneResponse["id"],
            "fine_tune_job_response" => $jobFineTuneResponse
        ]);
    }


    public function createFineTune($model, $fineTuneId)
    {
        $openAi = initOpenAi();
        return $openAi->createFineTune([
            "model" => $model,
            "training_file" => $fineTuneId
        ]);
    }


    public function fineTuneOpenAiFileUpload(object $fineTuneHistory)
    {
        $file    = !empty( $_FILES['training_data_file'] ) ? $_FILES['training_data_file'] : array();

        $file_name = basename($file['name']);
        $tmp_file  = $file['tmp_name'];
        $file_type = $file['type'];

        $c_file    = curl_file_create($tmp_file,$file_type,$file_name);

        $payloads = [
            "purpose" => $fineTuneHistory->title,
            "purpose" => "fine-tune",
            "file"    => $c_file
        ];

        $openAiFileUpload = initOpenAi();

        return $openAiFileUpload->uploadFile($payloads);
    }


    public function fineTuningJobs()
    {
        $openAi = initOpenAi(openAiKey());

        return $openAi->listFineTunes();
    }

    public function findByFineTuneJobId($fineTuneJobId)
    {
        $openAi = initOpenAi(openAiKey());

        return $openAi->retrieveFineTune($fineTuneJobId);
    }

    public function cancelFineTuneJobId($fineTuneJobId)
    {
        $openAi = initOpenAi(openAiKey());

        return $openAi->cancelFineTune($fineTuneJobId);
    }

    public function deleteFineTuneJobId($fineTuneJobId)
    {
        $openAi = initOpenAi(openAiKey());

        return $openAi->deleteFineTune($fineTuneJobId);
    }
}
