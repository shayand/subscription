<?php


namespace App\Http\Requests;


use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class BaseFormRequest extends FormRequest
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
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        Log::info('[BaseFormRequest][failedValidation] throw exception');
        throw new HttpResponseException(response()->json(['data'=> ['status' => 'failed','message' => $validator->getMessageBag()] ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY));
    }
}
