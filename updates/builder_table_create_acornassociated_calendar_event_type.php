<?php namespace AcornAssociated\Calendar\Updates;

use Schema;
use AcornAssociated\Calendar\Updates\AcornAssociatedMigration;

class BuilderTableCreateAcornassociatedCalendarEventType extends AcornAssociatedMigration
{
    static protected $table = 'acornassociated_calendar_event_type';

    public function up()
    {
        Schema::create(self::$table, function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name', 2048);
            $table->boolean('whole_day')->default(false);
            $table->string('colour', 16)->nullable();
            $table->string('style', 2048)->nullable();
            $table->timestamp('created_at')->nullable(false)->default('now()');
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
