<?php

namespace App\Http\Requests;

class SubscriptionPaymentRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => 'required',
            'plan_id' => 'required',
            'store_id' => 'required',
            'price' => 'required',
            'payment_type' => 'required',
            'currency' => 'required',
        ];
    }
}
