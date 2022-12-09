<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class SubscriptionUsersModification
 */
class SubscriptionUsersModifications2 extends Migration
{
    /**
     * @var string
     */
    private $table = Tables::SUBSCRIPTION_USERS;

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
        $this->schema->table($this->table,function (Blueprint $table) {
            $table->integer('init_duration')->nullable(true)->comment('null on users/plan without reserve or renewal');
            $table->dateTime('init_start_date')->nullable(true)->comment('null on users/plan without reserve or renewal');
            $table->integer('duration')->comment('plan duration in minutes')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->removeColumn('init_duration');
            $table->removeColumn('init_start_date');
            $table->smallInteger('duration')->change();
        });
    }
}
