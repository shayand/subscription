<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PaymentAddCreditId extends Migration
{
    protected $table = Tables::SUBSCRIPTION_PAYMENTS;
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->table, function (Blueprint $table) {
            // website buy support
            $table->integer('payment_id')->nullable(true)->change();
            $table->integer('credit_id')->nullable(true);
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
            $table->removeColumn('payment_id');
            $table->removeColumn('credit_id');
        });
    }
}
