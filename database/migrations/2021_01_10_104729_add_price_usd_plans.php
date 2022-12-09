<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriceUsdPlans extends Migration
{
    /**
     * @var string
     */
    private $table = Tables::SUBSCRIPTION_PLANS;

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
            $table->float('price_usd')->default(0);
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
            $table->removeColumn('price_usd');
        });
    }
}
