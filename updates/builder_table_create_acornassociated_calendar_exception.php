<?php namespace AcornAssociated\Calendar\Updates;

use Schema;
use AcornAssociated\Calendar\Updates\AcornAssociatedMigration;

class BuilderTableCreateAcornassociatedCalendarException extends AcornAssociatedMigration
{
    static protected $table = 'acornassociated_calendar_exception';

    public function up()
    {
        Schema::create(self::$table, function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('event_part_id');
            $table->integer('instance');
            $table->dateTime('start');
            $table->dateTime('end');
            $table->string('name', 1024);
            $table->text('description')->nullable();
            $table->string('type', 1)->default('"S"');

            $table->primary(['event_part_id','instance']);
        });
    }

    public function down()
    {
        $this->dropCascade(self::table);
    }
}
