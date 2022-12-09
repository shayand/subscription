<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PaymentModifications extends Migration
{
    /**
     * @var string
     */
    protected $table = Tables::SUBSCRIPTION_PAYMENTS;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->removeColumn('amount');
            $table->removeColumn('subscription_user_id');
            $table->integer('user_id');
            $table->string('currency');
            $table->integer('device_id')->nullable(true);
            $table->string('device_type')->nullable(true);
            $table->string('app_version')->nullable(true);
            $table->float('price')->default(0);
            $table->float('discount_price')->default(0);
            $table->string('discount_code')->nullable(true);
//            $table->dropForeign('subscription_payments_subscription_user_id_foreign');
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
            $table->dropColumn('user_id');
            $table->dropColumn('currency');
            $table->dropColumn('device_id');
            $table->dropColumn('device_type');
            $table->dropColumn('app_version');
            $table->dropColumn('price');
            $table->dropColumn('discount_price');
            $table->dropColumn('discount_code');
        });
    }
}
