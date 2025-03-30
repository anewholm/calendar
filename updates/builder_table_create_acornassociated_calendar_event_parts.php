<?php namespace Acorn\Calendar\Updates;

use DB;
use Schema;
use \Acorn\Migration;

class BuilderTableCreateAcornCalendarEventParts extends Migration
{
    static protected $table = 'acorn_calendar_event_parts';
    static public $NULLABLE = TRUE;

    public function up()
    {
        if (!Schema::hasTable(self::$table)) {
            Schema::create(self::$table, function($table)
            {
                $table->engine = 'InnoDB';
                $table->uuid('id')->primary()->default(DB::raw('(gen_random_uuid())'));
                $table->uuid('event_id');
                $table->string('name', 1024);
                $table->text('description')->nullable();
                $table->dateTime('start');
                $table->dateTime('end');
                $table->dateTime('until')->nullable();
                $table->integer('mask')->default(0);
                $table->string('mask_type', 256)->nullable();
                $table->uuid('type_id');
                $table->uuid('status_id');
                $table->integer('repeat_frequency')->default(1);
                $table->uuid('parent_event_part_id')->nullable();
                $table->uuid('location_id')->nullable();
                $table->integer('locked_by_user_id')->nullable();
                $table->timestamp('created_at')->nullable(false)->default('now()');
                $table->timestamp('updated_at')->nullable();

                $table->foreign('event_id')
                    ->references('id')->on('acorn_calendar_events')
                    ->onDelete('cascade');
                $table->foreign('type_id')
                    ->references('id')->on('acorn_calendar_event_types')
                    ->onDelete('cascade');
                $table->foreign('status_id')
                    ->references('id')->on('acorn_calendar_event_statuses')
                    ->onDelete('cascade');
                $table->foreign('locked_by_user_id')
                    ->references('id')->on('backend_users')
                    ->onDelete('set null');

                // Integration with the required location plugin
                if (Schema::hasTable('acorn_location_location')) {
                    $table->foreign('location_id')
                        ->references('id')->on('acorn_location_location')
                        ->onDelete('cascade');
                }
            });

            Schema::table(self::$table, function(\Winter\Storm\Database\Schema\Blueprint $table) {
                // Create after main create because it is self-referencing
                $table->foreign('parent_event_part_id')
                    ->references('id')->on(self::$table)
                    ->onDelete('cascade');
            });

            $this->interval(self::$table, 'repeat', self::$NULLABLE);
            $this->interval(self::$table, 'alarm',  self::$NULLABLE);
            $this->integerArray(self::$table, 'instances_deleted', self::$NULLABLE);
            $this->createFunction('fn_acorn_calendar_is_date', ['s varchar', 'd timestamp without time zone'], 'timestamp without time zone', [], '
                if s is null then
                    return d;
                end if;
                perform s::timestamp without time zone;
                    return s;
                exception when others then
                    return d;
            ');
            $this->setTableTypeContent(self::$table);
        }
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
