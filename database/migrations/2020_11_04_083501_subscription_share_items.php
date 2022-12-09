<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SubscriptionShareItems extends Migration
{
    /**
     * @var string
     */
    protected $table = Tables::SUBSCRIPTION_SHARE_ITEMS;

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
            $table->integer('subscription_user_id')->index('fk_subscription_share_item_user_id');
            $table->integer('subscription_plan_entity_id')->index('fk_subscription_share_item_plan_entity_id');

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('subscription_user_id')->references('id')->on(Tables::SUBSCRIPTION_USERS)->onDelete('cascade');
            $table->foreign('subscription_plan_entity_id')->references('id')->on(Tables::SUBSCRIPTION_PLAN_ENTITIES)->onDelete('cascade');

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
        $this->schema->drop($this->table);
    }
}
