<?php namespace AcornAssociated\Calendar\Updates;

use Schema;
use AcornAssociated\Calendar\Updates\AcornAssociatedMigration;

class BuilderTableCreateAcornassociatedCalendarInstance extends AcornAssociatedMigration
{
    static protected $table = 'acornassociated_calendar_instance';

    public function up()
    {
        Schema::create(self::$table, function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->date('date');
            $table->integer('event_part_id')->unsigned();
            $table->integer('instance_id')->unsigned();
            $table->dateTime('instance_start');
            $table->dateTime('instance_end');
            $table->timestamp('created_at')->nullable(false)->default('now()');
            $table->timestamp('updated_at')->nullable();

            $table->index(['date','event_part_id','instance_id']);
            $table->foreign('event_part_id')
                ->references('id')->on('acornassociated_calendar_event_part')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
