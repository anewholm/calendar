<?php namespace AcornAssociated\Calendar\Updates;

use Schema;
use \AcornAssociated\Migration as AcornAssociatedMigration;

class BuilderTableCreateAcornassociatedCalendarEventPartUserGroup extends AcornAssociatedMigration
{
    static protected $table = 'acornassociated_calendar_event_part_user_group';

    public function up()
    {
        if (!Schema::hasTable(self::$table))
            Schema::create(self::$table, function($table)
            {
                $table->engine = 'InnoDB';
                $table->uuid('event_part_id');
                $table->uuid('user_group_id');
                $table->primary(['event_part_id', 'user_group_id']);

                $table->foreign('event_part_id')
                    ->references('id')->on('acornassociated_calendar_event_parts')
                    ->onDelete('cascade');
                $table->foreign('user_group_id')
                    ->references('id')->on('acornassociated_user_user_groups')
                    ->onDelete('cascade');
            });
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
