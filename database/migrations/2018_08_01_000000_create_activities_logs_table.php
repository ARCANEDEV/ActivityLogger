<?php

use Illuminate\Database\Schema\Blueprint;
use Arcanedev\Support\Database\Migration;

/**
 * Class     CreateActivitiesLogsTable
 *
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 *
 * @see  \Arcanedev\ActivityLogger\Models\Activity
 */
class CreateActivitiesLogsTable extends Migration
{
    /* -----------------------------------------------------------------
     |  Constructor
     | -----------------------------------------------------------------
     */

    public function __construct()
    {
        $this->setConnection(config('activity-logger.database.connection'));
        $this->setPrefix(config('activity-logger.database.prefix'));
        $this->setTable(config('activity-logger.activities.table'));
    }

    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    /**
     * Run the migrations.
     */
    public function up()
    {
        $this->createSchema(function (Blueprint $table) {
            $table->increments('id');
            $table->string('log_name')->nullable();
            $table->string('log_level')->default(config('activity-logger.defaults.log-level', 'info'));
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->nullableMorphs('causer');
            $table->text('properties')->nullable();
            $table->timestamps();

            $table->index(['log_name', 'log_level']);
        });
    }
}
