<?php

namespace App\Imports;

use App\Models\SubscriptionEntities;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class SubscriptionEntitiesImport implements ToModel, WithBatchInserts, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    /**
    * @param array $row
    *
    * @return SubscriptionEntities
     */
    public function model(array $row)
    {
        return new SubscriptionEntities([
            'entity_type' => "N/A",//$row["entity_type"],
            'entity_id' => $row["entity_id"],
            'price_factor' => 100,
            'publisher_id' => 0,//$row["publisher_id"],
            'publisher_name' => "N/A",//$row["publisher_name"],
            'publisher_share' => 10,//$row["publisher_market_share"],
            'created_at' => Carbon::now()->toDateTimeLocalString(),
            'updated_at' => Carbon::now()->toDateTimeLocalString(),
            'operator_id' => 654321
        ]);
    }

    public function rules(): array
    {
        return [
            'entity_id' => function($attribute, $value, $onFailure) {
                if ($value == null) {
                    $onFailure("null entity");
                } elseif (SubscriptionEntities::query()->where('entity_id', '=', $value)->exists()) {
                    $onFailure('Duplicate');
                }
            }
        ];
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function batchSize(): int
    {
        return 1000;
    }
}
