<?php namespace Acorn\Calendar\Updates;

use DB;
use Schema;
use \Acorn\Migration as AcornMigration;

class CreateAcornUsersExtraFields extends AcornMigration
{
    public function up()
    {
        // Add extra namespaced fields in to the users table
        Schema::table('acorn_user_users', function(\Winter\Storm\Database\Schema\Blueprint $table) {
            if (!Schema::hasColumn($table->getTable(), 'acorn_default_calendar')) $table->uuid('acorn_default_calendar')->nullable();
            if (!Schema::hasColumn($table->getTable(), 'acorn_start_of_week'))    $table->integer('acorn_start_of_week')->nullable();
            if (!Schema::hasColumn($table->getTable(), 'acorn_default_event_time_from')) $table->date('acorn_default_event_time_from')->nullable();
            if (!Schema::hasColumn($table->getTable(), 'acorn_default_event_time_to'))   $table->date('acorn_default_event_time_to')->nullable();
        });
    }

    public function down()
    {
        Schema::table('acorn_user_users', function(\Winter\Storm\Database\Schema\Blueprint $table) {
            if (Schema::hasColumn($table->getTable(), 'acorn_default_calendar')) $table->dropColumn('acorn_default_calendar');
            if (Schema::hasColumn($table->getTable(), 'acorn_start_of_week'))    $table->dropColumn('acorn_start_of_week');
            if (Schema::hasColumn($table->getTable(), 'acorn_default_event_time_from')) $table->dropColumn('acorn_default_event_time_from');
            if (Schema::hasColumn($table->getTable(), 'acorn_default_event_time_to'))   $table->dropColumn('acorn_default_event_time_to');
        });
    }
}
