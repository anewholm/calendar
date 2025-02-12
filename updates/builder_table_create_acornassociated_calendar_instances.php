<?php namespace Acorn\Calendar\Updates;

use DB;
use Schema;
use \Acorn\Migration as AcornMigration;

class BuilderTableCreateAcornCalendarInstances extends AcornMigration
{
    static protected $table = 'acorn_calendar_instances';

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
                $table->timestamp('created_at')->nullable(false)->default('now()');
                $table->timestamp('updated_at')->nullable();

                $table->index(['date','event_part_id','instance_num']);
                $table->foreign('event_part_id')
                    ->references('id')->on('acorn_calendar_event_parts')
                    ->onDelete('cascade');
            });

        $this->setTableTypeContent(self::$table);
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
