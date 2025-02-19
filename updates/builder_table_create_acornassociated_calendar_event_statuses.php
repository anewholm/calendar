<?php namespace AcornAssociated\Calendar\Updates;

use DB;
use Schema;
use \AcornAssociated\Migration as AcornAssociatedMigration;

class BuilderTableCreateAcornassociatedCalendarEventStatuses extends AcornAssociatedMigration
{
    static protected $table = 'acornassociated_calendar_event_statuses';

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
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });

        $this->setTableTypeContent(self::$table);
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
