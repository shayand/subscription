<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionPartnersRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules =  [];
        if ($this->isMethod("post")) {
            $rules = ['endpoint_path' => ['required', 'alpha_dash'], 'name' => 'required'];
        }
        return $rules;
    }
}
