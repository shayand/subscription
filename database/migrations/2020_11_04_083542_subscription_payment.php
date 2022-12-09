<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SubscriptionPayment extends Migration
{
    /**
     * @var string
     */
    protected $table = Tables::SUBSCRIPTION_PAYMENTS;

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
        $this->schema->create($this->table,function (Blueprint $table) {
            $table->integer('id',true);
            $table->integer('plan_id')->index($this->table . '_plan_id');
            $table->bigInteger('amount')->default(0);
            $table->string('payment_type',80)->index($this->table . '_payment_type');
            $table->integer('payment_id')->index($this->table . '_payment_id');

            //FOREIGN KEY CONSTRAINTS
//            $table->foreign('subscription_user_id')->references('id')->on(Tables::SUBSCRIPTION_USERS)->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on(Tables::SUBSCRIPTION_PLANS)->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->drop($this->table);
    }
}
