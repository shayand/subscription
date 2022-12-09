<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SubscriptionPartnersPlans extends Migration
{
    /**
     * @var string
     */
    protected $table = Tables::SUBSCRIPTION_PARTNERS_PLANS;

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
            $table->integer('subscription_partner_plan_id')->index("fk_partner_plan_id")->nullable();

            $table->integer('subscription_partner_id')->index('fk_subscription_partner_id');
            $table->integer('subscription_plan_id')->index('fk_subscription_plan_id');
            $table->unique(['subscription_partner_id', 'subscription_plan_id'], 'unique_partner_plan');

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('subscription_partner_id')->references('id')->on(Tables::SUBSCRIPTION_PARTNERS)->onDelete('cascade');
            $table->foreign('subscription_plan_id')->references('id')->on(Tables::SUBSCRIPTION_PLANS)->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->table);
    }
}
