<?php namespace AcornAssociated\Calendar\Updates;

use DB;
use Schema;
use \AcornAssociated\Migration as AcornAssociatedMigration;

class BuilderTableCreateAcornassociatedCalendarInstances extends AcornAssociatedMigration
{
    static protected $table = 'acornassociated_calendar_instances';

    public function up()
    {
        if (!Schema::hasTable(self::$table))
            Schema::create(self::$table, function($table)
            {
                $table->engine = 'InnoDB';
                $table->uuid('id')->primary()->default(DB::raw('(gen_random_uuid())'));
                $table->date('date');
                $table->uuid('event_part_id')->unsigned();
                $table->integer('instance_num')->unsigned();
                $table->dateTime('instance_start');
                $table->dateTime('instance_end');

                $table->index(['date','event_part_id','instance_num']);
                $table->foreign('event_part_id')
                    ->references('id')->on('acornassociated_calendar_event_parts')
                    ->onDelete('cascade');
            });

        $this->setTableTypeContent(self::$table);
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
