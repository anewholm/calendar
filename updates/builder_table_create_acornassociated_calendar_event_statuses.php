<?php namespace Acorn\Calendar\Updates;

use DB;
use Schema;
use \Acorn\Migration as AcornMigration;

class BuilderTableCreateAcornCalendarEventStatuses extends AcornMigration
{
    static protected $table = 'acorn_calendar_event_statuses';

    public function up()
    {
        if (!Schema::hasTable(self::$table))
            Schema::create(self::$table, function($table)
            {
                $table->engine = 'InnoDB';
                $table->uuid('id')->primary()->default(DB::raw('(gen_random_uuid())'));
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('style')->nullable();
                $table->boolean('system')->default(false);
                $table->uuid('calendar_id')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->foreign('calendar_id')
                    ->references('id')->on('acorn_calendar_calendars')
                    ->onDelete('cascade');
            });

        $this->setTableTypeContent(self::$table);
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
