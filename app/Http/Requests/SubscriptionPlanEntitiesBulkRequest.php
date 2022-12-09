<?php

namespace App\Http\Requests;

class SubscriptionPlanEntitiesBulkRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return ['entity_id' => ['required']];
    }
}
