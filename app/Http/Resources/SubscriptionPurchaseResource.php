<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPurchaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "store_id" => $this->store_id,
            "user_id" => $this->user_id,
            "currency" => $this->currency,
            "platform" => $this->device_type,
            "version" => $this->app_version,
            "purchased_at" => $this->created_at->format('Y-m-d H:i:s'),
            "items" => [
                [
                    "item_id" => $this->plan_id,
                    "campaign_id" => $this->campaign_id,
                    "code" => $this->discount_code,
                    "invoice_item_id" => $this->id,
                    "price" => $this->price,
                    "discount" => $this->discount_price
                ],
            ],
        ];
    }
}
