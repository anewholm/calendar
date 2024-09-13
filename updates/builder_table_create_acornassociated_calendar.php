<?php namespace Acorn\Calendar\Updates;

use Schema;
use \Acorn\Migration as AcornMigration;

class BuilderTableCreateAcornCalendar extends AcornMigration
{
    static protected $table = 'acorn_calendar';

    public function up()
    {
        if (!Schema::hasTable(self::$table))
            Schema::create(self::$table, function($table)
            {
                $table->engine = 'InnoDB';
                $table->increments('id')->unsigned();
                $table->string('name', 1024);
                $table->text('description')->nullable();
                $table->string('sync_file', 4096)->nullable();
                $table->integer('sync_format')->default(0); // 0 - ICS
                $table->timestamp('created_at')->nullable(false)->default('now()');
                $table->timestamp('updated_at')->nullable();

                // Ownership
                $table->integer('owner_user_id')->unsigned();
                $table->integer('owner_user_group_id')->unsigned()->nullable();
                $table->integer('permissions')->unsigned()->default();
                $table->foreign('owner_user_id')
                    ->references('id')->on('backend_users')
                    ->onDelete('cascade');
                $table->foreign('owner_user_group_id')
                    ->references('id')->on('backend_user_groups')
                    ->onDelete('cascade');
            });

        // Add extra namespaced fields in to the backend_users table
        Schema::table('backend_users', function(\Winter\Storm\Database\Schema\Blueprint $table) {
            if (!Schema::hasColumn($table->getTable(), 'acorn_default_calendar')) $table->integer('acorn_default_calendar')->nullable();
            if (!Schema::hasColumn($table->getTable(), 'acorn_start_of_week'))    $table->integer('acorn_start_of_week')->nullable();
            if (!Schema::hasColumn($table->getTable(), 'acorn_default_event_time_from')) $table->date('acorn_default_event_time_from')->nullable();
            if (!Schema::hasColumn($table->getTable(), 'acorn_default_event_time_to'))   $table->date('acorn_default_event_time_to')->nullable();
        });
    }

    public function down()
    {
        $this->dropCascade(self::$table);

        Schema::table('backend_users', function(\Winter\Storm\Database\Schema\Blueprint $table) {
            if (Schema::hasColumn($table->getTable(), 'acorn_default_calendar')) $table->dropColumn('acorn_default_calendar');
            if (Schema::hasColumn($table->getTable(), 'acorn_start_of_week'))    $table->dropColumn('acorn_start_of_week');
            if (Schema::hasColumn($table->getTable(), 'acorn_default_event_time_from')) $table->dropColumn('acorn_default_event_time_from');
            if (Schema::hasColumn($table->getTable(), 'acorn_default_event_time_to'))   $table->dropColumn('acorn_default_event_time_to');
        });
    }
}
