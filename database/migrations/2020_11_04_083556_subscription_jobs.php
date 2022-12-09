<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Constants\Tables;

class SubscriptionJobs extends Migration
{
    /**
     * @var string
     */
    protected string $table = Tables::SUBSCRIPTION_JOBS;

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
            $table->string('uuid')->unique()->index($this->table.'_uuid');

            $table->json('settlements')->nullable(true);
            $table->json('subscription_users')->nullable(true);
            $table->json('subscription_users_entities')->nullable(true);
            $table->json('subscription_users_entities_shares')->nullable(true);

            $table->bigInteger('total_publisher_share_amount')->default(0)->comment('the amount store in toman');
            $table->bigInteger('total_fidibo_share_amount')->default(0)->comment('the amount store in toman');

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
        Schema::dropIfExists($this->table);
    }
}
