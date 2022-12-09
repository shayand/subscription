<?php

namespace App\Http\Requests;

class SubscriptionEntitiesRequest extends BaseFormRequest
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
            $rules = ['entity_id' => ['required', 'regex:/^[0-9,]*$/']];
        }
        return $rules;
    }
}
