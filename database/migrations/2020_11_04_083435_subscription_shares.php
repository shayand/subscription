<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SubscriptionShares extends Migration
{
    /**
     * @var string
     */
    protected $table = Tables::SUBSCRIPTION_SHARES;

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
            $table->integer('subscription_user_id')->index('fk_'.$this->table . '_subscription_user_id');
            $table->integer('subscription_entity_id')->index('fk' . $this->table . '_subscription_entity_id');
            $table->bigInteger('total_calculated_amount')->default(0);
            $table->bigInteger('publisher_share_amount')->default(0)->comment('the amount store in toman');
            $table->integer('total_duration')->comment('duration in days');

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('subscription_entity_id')->references('id')->on(Tables::SUBSCRIPTION_ENTITIES)->onDelete('cascade');
            $table->foreign('subscription_user_id')->references('id')->on(Tables::SUBSCRIPTION_USERS)->onDelete('cascade');

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
