<?php

use App\Constants\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SubscriptionPartnersTracking extends Migration
{
    /**
     * @var string
     */
    protected $table = Tables::SUBSCRIPTION_PARTNERS_TRACKING;

    /**
     * The database schema.
     *
     * @var \Illuminate\Database\Schema\Builder
     */
    protected $schema;

    /**
     * SubscriptionPartnersTracking constructor.
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
            $table->integer('partner_id')->index('fk_'.$this->table);
            $table->string('tracking_uid')->nullable(false);
            $table->bigInteger('phone');
            $table->tinyInteger('is_delivered_fdb')->default(0);
            $table->tinyInteger('is_fdb_processed')->default(0);
            $table->tinyInteger('is_checked_status')->default(0);
            $table->foreign('partner_id')->references('id')->on(Tables::SUBSCRIPTION_PARTNERS)->onDelete('cascade');

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
