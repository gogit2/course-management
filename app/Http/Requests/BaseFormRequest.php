<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseFormRequest extends FormRequest
{
    use ApiResponseTrait;

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->errorResponse('Validation Error', 422, null, $validator->errors()->toArray())
        );
    }
}
