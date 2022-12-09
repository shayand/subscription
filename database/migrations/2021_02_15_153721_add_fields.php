<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFields extends Migration
{
    /**
     * @var string
     */
    protected $table = Tables::SUBSCRIPTION_USER_PLAN_ASSIGNMENTS;

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
            $table->string('assignment_title',80)->nullable(true);
            $table->tinyInteger('assignment_reason')->default(1)->nullable(true);
            $table->integer('number_of_ids')->nullable(true);
            $table->json('inserted_ids')->nullable(true);
            $table->json('invalid_ids')->nullable(true);
            $table->json('all_ids')->nullable(true);
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
            $table->removeColumn('assignment_title');
            $table->removeColumn('assignment_reason');
            $table->removeColumn('number_of_ids');
            $table->removeColumn('inserted_ids');
            $table->removeColumn('invalid_ids');
            $table->removeColumn('all_ids');
        });
    }
}
