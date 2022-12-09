<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SubscriptionHistoryOperator extends Migration
{

    private $table = Tables::SUBSCRIPTION_USER_HISTORIES;

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
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->integer('operator_id')->nullable(true);
            $table->tinyInteger('update_reason')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->table($this->table,function (Blueprint $table) {
            $table->removeColumn('operator_id');
            $table->removeColumn('update_reason');
        });
    }
}
