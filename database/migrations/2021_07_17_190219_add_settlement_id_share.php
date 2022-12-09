<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSettlementIdShare extends Migration
{
    /**
     * @var string
     */
    protected $table = Tables::SUBSCRIPTION_SHARES;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->integer('subscription_settlement_id')->nullable()->index('fk_settlement_id');

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('subscription_settlement_id')->references('id')->on(Tables::SUBSCRIPTION_SETTELMENT_PERIODS)->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->dropColumn('subscription_settlement_id');
        });
    }
}
