<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionPartnersPlansRequest extends BaseFormRequest
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
            $rules = ['partner_id' => 'required', 'plan_id' => 'required'];
        }
        return $rules;
    }
}
