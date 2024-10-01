<?php namespace Acorn\Calendar\Updates;

use Schema;
use \Acorn\Migration as AcornMigration;

class BuilderTableCreateAcornCalendarEventUserGroup extends AcornMigration
{
    static protected $table = 'acorn_calendar_event_user_group';

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
                    ->references('id')->on('acorn_calendar_event_part')
                    ->onDelete('cascade');
                $table->foreign('user_group_id')
                    ->references('id')->on('acorn_user_user_groups')
                    ->onDelete('cascade');
            });
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
