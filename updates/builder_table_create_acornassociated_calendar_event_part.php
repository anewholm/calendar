<?php namespace AcornAssociated\Calendar\Updates;

use Schema;
use \AcornAssociated\Migration as AcornAssociatedMigration;

class BuilderTableCreateAcornassociatedCalendarEventPart extends AcornAssociatedMigration
{
    static protected $table = 'acornassociated_calendar_event_part';
    static public $NULLABLE = TRUE;

    public function up()
    {
        if (!Schema::hasTable(self::$table)) {
            Schema::create(self::$table, function($table)
            {
                $table->engine = 'InnoDB';
                $table->increments('id')->unsigned();
                $table->integer('event_id')->unsigned();
                $table->string('name', 1024);
                $table->text('description')->nullable();
                $table->dateTime('start');
                $table->dateTime('end');
                $table->dateTime('until')->nullable();
                $table->integer('mask')->default(0);
                $table->string('mask_type', 256)->nullable();
                $table->integer('type_id')->unsigned()->default(1);
                $table->integer('status_id')->unsigned()->default(1);
                $table->integer('repeat_frequency')->default(1);
                $table->integer('parent_event_part_id')->unsigned()->nullable();
                $table->integer('location_id')->unsigned()->nullable();
                $table->integer('locked_by')->unsigned()->nullable();
                $table->timestamp('created_at')->nullable(false)->default('now()');
                $table->timestamp('updated_at')->nullable();

                $table->foreign('event_id')
                    ->references('id')->on('acornassociated_calendar_event')
                    ->onDelete('cascade');
                $table->foreign('type_id')
                    ->references('id')->on('acornassociated_calendar_event_type')
                    ->onDelete('cascade');
                $table->foreign('status_id')
                    ->references('id')->on('acornassociated_calendar_event_status')
                    ->onDelete('cascade');
                $table->foreign('parent_event_part_id')
                    ->references('id')->on(self::$table)
                    ->onDelete('cascade');
                $table->foreign('locked_by')
                    ->references('id')->on('backend_users')
                    ->onDelete('set null');

                // Integration with the required location plugin
                if (Schema::hasTable('acornassociated_location_location')) {
                    $table->foreign('location_id')
                        ->references('id')->on('acornassociated_location_location')
                        ->onDelete('cascade');
                }
            });

            $this->interval(self::$table, 'repeat', self::$NULLABLE);
            $this->integerArray(self::$table, 'instances_deleted', self::$NULLABLE);
            $this->createFunction('is_date', ['s varchar', 'd timestamp with time zone'], 'timestamp with time zone', '
                if s is null then
                    return d;
                end if;
                perform s::timestamp with time zone;
                    return s;
                exception when others then
                    return d;
            ');
        }
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
