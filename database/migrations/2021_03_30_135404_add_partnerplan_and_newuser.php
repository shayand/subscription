<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPartnerplanAndNewuser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_users', function (Blueprint $table) {
            $table->integer('partner_plan_id')->nullable();
            $table->boolean('new_user')->default(false)->comment("true=New User, false=Old User");

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('partner_plan_id')->references('id')->on(Tables::SUBSCRIPTION_PARTNERS_PLANS)->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_users', function (Blueprint $table) {
            $table->dropColumn('partner_plan_id');
            $table->dropColumn('new_user');
        });
    }
}
