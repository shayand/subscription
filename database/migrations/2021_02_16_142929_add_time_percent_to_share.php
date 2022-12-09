<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimePercentToShare extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_shares', function (Blueprint $table) {
            $table->float('read_percent')->nullable(true)->default(0);
            $table->integer('on_the_table')->nullable(true)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_shares', function (Blueprint $table) {
            $table->removeColumn('read_percent');
            $table->removeColumn('on_the_table');

        });
    }
}
