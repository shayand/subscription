<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlanIdToAssignmentUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_user_plan_assignments', function (Blueprint $table) {
            $table->integer('subscription_plan_id')->index('fk_subscription_plan_id');

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('subscription_plan_id')->references('id')->on(Tables::SUBSCRIPTION_PLANS)->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_user_plan_assignments', function (Blueprint $table) {
            //
        });
    }
}
