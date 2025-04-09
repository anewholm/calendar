<?php namespace AcornAssociated\Calendar\Updates;

use DB;
use Schema;
use \AcornAssociated\Migration;

class BuilderTableCreateAcornassociatedCalendarEvents extends Migration
{
    static protected $table = 'acornassociated_calendar_events';


    public function up()
    {
        if (!Schema::hasTable(self::$table))
            Schema::create(self::$table, function($table)
            {
                $table->engine = 'InnoDB';
                $table->uuid('id')->primary()->default(DB::raw('(gen_random_uuid())'));
                $table->uuid('calendar_id');
                $table->string('external_url', 2048)->nullable();
                $table->timestamp('created_at')->nullable(false)->default('now()');
                $table->timestamp('updated_at')->nullable();

                $table->foreign('calendar_id')
                    ->references('id')->on('acornassociated_calendar_calendars')
                    ->onDelete('cascade');

                // Ownership
                $table->uuid('owner_user_id');
                $table->uuid('owner_user_group_id')->nullable();
                $table->integer('permissions')->unsigned()->default(7+8+64);
                $table->foreign('owner_user_id')
                    ->references('id')->on('acornassociated_user_users')
                    ->onDelete('cascade');
                $table->foreign('owner_user_group_id')
                    ->references('id')->on('acornassociated_user_user_groups')
                    ->onDelete('cascade');
            });

        $this->setTableTypeContent(self::$table);
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
