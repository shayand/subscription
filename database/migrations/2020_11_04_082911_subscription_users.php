<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SubscriptionUsers extends Migration
{
    /**
     * @var string
     */
    protected $table = Tables::SUBSCRIPTION_USERS;

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
            $table->integer('user_id')->index($this->table . 'user_id');
            $table->integer('plan_id')->index('fk_' . $this->table . 'plan_id');
            $table->date('start_date');
            $table->smallInteger('duration');
            $table->json('plan_details')->comment('plan data stored in json')->nullable();

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('plan_id')->references('id')->on(Tables::SUBSCRIPTION_PLANS)->onDelete('cascade');

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
        
    }
}
