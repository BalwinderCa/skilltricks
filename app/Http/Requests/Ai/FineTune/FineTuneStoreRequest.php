<?php

namespace App\Http\Requests\Ai\FineTune;

use App\Rules\JsonlFileExtension;
use App\Services\Engine\ModelEngine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FineTuneStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "title"              => "required",
            "model_engine"       => ["required", Rule::in([ModelEngine::DAVINCI_002, ModelEngine::GPT_3_5_TURBO])],
            "training_data_file" => ["required","file",new JsonlFileExtension()],
            "description"        => "nullable",
        ];
    }

}
