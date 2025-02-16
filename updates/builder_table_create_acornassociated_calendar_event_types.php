<?php namespace Acorn\Calendar\Updates;

use DB;
use Schema;
use \Acorn\Migration as AcornMigration;

class BuilderTableCreateAcornCalendarEventTypes extends AcornMigration
{
    static protected $table = 'acorn_calendar_event_types';

    public function up()
    {
        if (!Schema::hasTable(self::$table))
            Schema::create(self::$table, function($table)
            {
                $table->engine = 'InnoDB';
                $table->uuid('id')->primary()->default(DB::raw('(gen_random_uuid())'));
                $table->string('name', 2048);
                $table->text('description')->nullable();
                $table->boolean('whole_day')->default(false);
                $table->string('colour', 16)->nullable();
                $table->string('style', 2048)->nullable();
                $table->boolean('system')->default(false);
                $table->integer('activity_log_related_oid')->unsigned()->nullable();
                $table->timestamp('created_at')->nullable(false)->default('now()');
                $table->timestamp('updated_at')->nullable();
            });

        $this->setTableTypeContent(self::$table);
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
