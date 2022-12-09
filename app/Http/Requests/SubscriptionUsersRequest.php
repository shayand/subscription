<?php

namespace App\Http\Requests;

class SubscriptionUsersRequest extends BaseFormRequest
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
            $rules = [
                'user_id' => 'required',
                'plan_id' => 'required',
            ];
        }
        return $rules;
    }
}
