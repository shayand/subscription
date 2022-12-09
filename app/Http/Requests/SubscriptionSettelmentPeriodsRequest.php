<?php

namespace App\Http\Requests;

class SubscriptionSettelmentPeriodsRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
//            'subscription_user_id' => 'required|min:1',
//            'settelment_date' => 'required|date|after:now',
        ];
    }
}
