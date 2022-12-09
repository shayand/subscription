<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SubscriptionPlans extends Migration
{

    /**
     * @var string
     */
    protected $table = Tables::SUBSCRIPTION_PLANS;

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
            $table->string('title',80);
            $table->date('start_date')->nullable(true);
            $table->date('end_date')->nullable(true);
            $table->bigInteger('price')->default('20000')->comment('the price amount is stored in toman');
            $table->tinyInteger('duration')->default('30')->comment('duration in days');
            $table->tinyInteger('max_books')->default('10');
            $table->tinyInteger('max_audios')->default('10');
            $table->integer('store_id')->index('subscription_store_id');
            $table->float('total_publisher_share')->comment('the publisher share in percent');
            $table->tinyInteger('status')->default('1')->comment('1 enable 2 disable');
            $table->tinyInteger('is_show')->default('1')->comment('0 no 1 yes');
            $table->tinyInteger('max_devices')->default('3');
            $table->tinyInteger('max_offline_entities')->default('1');
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
