<?php namespace AcornAssociated\Calendar\Updates;

use Schema;
use \AcornAssociated\Migration as AcornAssociatedMigration;

class BuilderTableCreateAcornassociatedCalendarEventUserGroup extends AcornAssociatedMigration
{
    static protected $table = 'acornassociated_calendar_event_user_group';

    public function up()
    {
        if (!Schema::hasTable(self::$table))
            Schema::create(self::$table, function($table)
            {
                $table->engine = 'InnoDB';
                $table->integer('event_part_id')->unsigned();
                $table->integer('user_group_id')->unsigned();
                $table->primary(['event_part_id', 'user_group_id']);

                $table->foreign('event_part_id')
                    ->references('id')->on('acornassociated_calendar_event_part')
                    ->onDelete('cascade');
                $table->foreign('user_group_id')
                    ->references('id')->on('backend_user_groups')
                    ->onDelete('cascade');
            });
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
