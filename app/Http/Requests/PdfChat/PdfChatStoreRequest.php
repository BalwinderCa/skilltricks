<?php

namespace App\Http\Requests\PdfChat;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;


class PdfChatStoreRequest extends FormRequest
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
        info("Incoming PDF Chat : ".json_encode($this->request->all()));

        return [
            "prompt"   => "required",
            "pdfFile"  => "required|mimes:pdf"
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $appStatic = appStatic();

        $responsePayloads = [
            "status"  => $appStatic::FALSE,
            "code"    => $appStatic::INTERNAL_SERVER_ERROR,
            "message" => "Pdf Chat Validation Errors.",
            "data"    => $validator->errors()
        ];

        throw new HttpResponseException(response()->json($responsePayloads, 422));
    }


}
