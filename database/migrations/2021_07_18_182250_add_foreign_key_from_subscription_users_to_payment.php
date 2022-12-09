<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeyFromSubscriptionUsersToPayment extends Migration
{
    /**
     * @var string
     */
    protected $table = Tables::SUBSCRIPTION_USERS;

    /**
     * The database schema.
     *
     * @var \Illuminate\Database\Schema\Builder
     */
    protected $schema;

    /**
     * Create a new migration instance.
     *
     * @return void
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
            $table->integer('subscription_payment_id')->nullable(true)->index('fk_subscription_payment_id');

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('subscription_payment_id')->references('id')->on(Tables::SUBSCRIPTION_PAYMENTS)->onDelete('cascade');
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
            $table->dropColumn('subscription_payment_id');
        });
    }
}
