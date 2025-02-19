<?php namespace AcornAssociated\Calendar\Updates;

use DB;
use Schema;
use \AcornAssociated\Migration as AcornAssociatedMigration;

class CreateAcornassociatedUsersExtraFields extends AcornAssociatedMigration
{
    public function up()
    {
        // Add extra namespaced fields in to the users table
        Schema::table('acornassociated_user_users', function(\Winter\Storm\Database\Schema\Blueprint $table) {
            if (!Schema::hasColumn($table->getTable(), 'acornassociated_default_calendar')) $table->uuid('acornassociated_default_calendar')->nullable();
            if (!Schema::hasColumn($table->getTable(), 'acornassociated_start_of_week'))    $table->integer('acornassociated_start_of_week')->nullable();
            if (!Schema::hasColumn($table->getTable(), 'acornassociated_default_event_time_from')) $table->date('acornassociated_default_event_time_from')->nullable();
            if (!Schema::hasColumn($table->getTable(), 'acornassociated_default_event_time_to'))   $table->date('acornassociated_default_event_time_to')->nullable();
        });
    }

    public function down()
    {
        Schema::table('acornassociated_user_users', function(\Winter\Storm\Database\Schema\Blueprint $table) {
            if (Schema::hasColumn($table->getTable(), 'acornassociated_default_calendar')) $table->dropColumn('acornassociated_default_calendar');
            if (Schema::hasColumn($table->getTable(), 'acornassociated_start_of_week'))    $table->dropColumn('acornassociated_start_of_week');
            if (Schema::hasColumn($table->getTable(), 'acornassociated_default_event_time_from')) $table->dropColumn('acornassociated_default_event_time_from');
            if (Schema::hasColumn($table->getTable(), 'acornassociated_default_event_time_to'))   $table->dropColumn('acornassociated_default_event_time_to');
        });
    }
}
