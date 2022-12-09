<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFkToSharesFromSubscShareItems extends Migration
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
        Schema::table($this->table, function (Blueprint $table) {
            $table->integer('subscription_share_id')->index('fk_subscription_share_item_share_id');

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('subscription_share_id')->references('id')->on(Tables::SUBSCRIPTION_SHARES)->onDelete('cascade');
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
            //
        });
    }
}
