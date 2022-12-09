<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFkToAssignmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_users', function (Blueprint $table) {
            $table->integer('subscription_assignment_id')->nullable(true)->index('fk_subscription_assignment_id');

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('subscription_assignment_id')->references('id')->on(Tables::SUBSCRIPTION_USER_PLAN_ASSIGNMENTS)->onDelete('cascade');
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
            $table->removeColumn('subscription_assignment_id');
        });
    }
}
