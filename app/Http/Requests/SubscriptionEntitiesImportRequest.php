<?php

namespace App\Http\Requests;

class SubscriptionEntitiesImportRequest extends BaseFormRequest
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
            $rules = ['entities' => ['required']];
        }
        return $rules;
    }
}
