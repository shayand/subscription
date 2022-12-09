<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSettledFieldToSettlementTable extends Migration
{
    /**
     * @var string
     */
    protected $table = Tables::SUBSCRIPTION_SETTELMENT_PERIODS;
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->tinyInteger('is_settled')->nullable(true)->default('0')->comment('0 not settled, 1 settled');
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
            //
        });
    }
}
