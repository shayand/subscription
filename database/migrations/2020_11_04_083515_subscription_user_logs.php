<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SubscriptionUserLogs extends Migration
{
    /**
     * @var string
     */
    protected $table = Tables::SUBSCRIPTION_USER_LOGS;

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
            $table->integer('subscription_user_id')->index('fk' . $this->table . 'subscription_user_id');
            $table->integer('device_id')->index($this->table . 'device_id');

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('subscription_user_id')->references('id')->on(Tables::SUBSCRIPTION_USERS)->onDelete('cascade');

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
