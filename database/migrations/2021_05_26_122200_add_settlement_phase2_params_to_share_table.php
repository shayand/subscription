<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSettlementPhase2ParamsToShareTable extends Migration
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
            $table->bigInteger('book_share')->nullable()->comment('book_share amount store in toman');
            $table->bigInteger('fidibo_static_share')->nullable()->comment('fidibo_static_share amount store in toman');
            $table->bigInteger('fidibo_dynamic_share')->nullable()->comment('fidibo_dynamic_share amount store in toman');
            $table->float('publisher_market_share')->nullable()->comment('the publisher share in percent has gotten from FDB(PAPI)');

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
            $table->dropColumn('book_share');
            $table->dropColumn('fidibo_static_share');
            $table->dropColumn('fidibo_dynamic_share');
            $table->dropColumn('publisher_market_share');
        });
    }
}
