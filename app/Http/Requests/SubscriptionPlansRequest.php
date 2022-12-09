<?php

namespace App\Http\Requests;


class SubscriptionPlansRequest extends BaseFormRequest
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
                'title' => 'required',
                'store_id' => 'required|min:1|max:1000',
                'duration' => 'min:1|max:365',
                'price' => 'min:1|max:999999999999',
                'max_books' => 'min:1|max:1000',
                'max_audios' => 'min:1|max:1000',
                'total_publisher_share' => 'required|min:1|max:100',
            ];
        }
        return $rules;
    }
}
