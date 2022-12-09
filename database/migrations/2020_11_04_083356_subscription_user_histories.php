<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SubscriptionUserHistories extends Migration
{
    /**
     * @var string
     */
    protected $table = Tables::SUBSCRIPTION_USER_HISTORIES;

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
            $table->integer('subscription_user_id')->index('fk_subscription_user_id');
            $table->integer('subscription_plan_entity_id')->index('fk_subscription_plan_entity_id');
            $table->integer('entity_id')->index($this->table. '_entity_id');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable(true);
            $table->float('read_percent_start')->default(0);
            $table->float('read_percent_end')->nullable(true);
            $table->tinyInteger('is_hide_from_list')->default('0')->comment('0 not hide from list 1 hide');
            $table->tinyInteger('is_logged')->default('0')->comment('0 not logged 1 logged');
            $table->json('subscription_entity_details')->nullable(true);

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('subscription_user_id')->references('id')->on(Tables::SUBSCRIPTION_USERS)->onDelete('cascade');
            $table->foreign('subscription_plan_entity_id')->references('id')->on(Tables::SUBSCRIPTION_PLAN_ENTITIES)->onDelete('cascade');

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
