<?php

use App\Constants\Tables;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSubscriptionPaymentsTableAddRequiredFields extends Migration
{
    /**
     * @var string
     */
    private string $table = Tables::SUBSCRIPTION_PAYMENTS;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table($this->table, static function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->default(0)->after('plan_id');
            $table->unsignedTinyInteger('store_id')->default(0)->after('campaign_id');
            $table->boolean('is_processed')->default(false)->after('discount_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table($this->table, static function (Blueprint $table) {
            $table->dropColumn('campaign_id', 'store_id', 'is_processed');
        });
    }
}
