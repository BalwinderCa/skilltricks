<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class JsonlFileExtension implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function passes($attribute, $value)
    {
        // Check if the file extension is 'jsonl'
        return $value->getClientOriginalExtension() === 'jsonl';
    }


    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a file with a JSONL extension.';
    }
}
