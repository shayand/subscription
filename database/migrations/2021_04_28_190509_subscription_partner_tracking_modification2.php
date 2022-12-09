<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SubscriptionPartnerTrackingModification2 extends Migration
{
    /**
     * @var string
     */
    protected $table = Tables::SUBSCRIPTION_PARTNERS_TRACKING;

    /**
     * The database schema.
     *
     * @var \Illuminate\Database\Schema\Builder
     */
    protected $schema;

    /**
     * SubscriptionPartnerTrackingModification constructor.
     */
    public function __construct()
    {
        $this->schema = Schema::connection($this->getConnection());
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->foreign('partner_plan_id')->references('id')->on(Tables::SUBSCRIPTION_PARTNERS_PLANS);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
