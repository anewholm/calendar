<?php namespace Acorn\Calendar\Updates;

use Schema;
use \Acorn\Migration as AcornMigration;

class BuilderTableCreateAcornCalendarEvent extends AcornMigration
{
    static protected $table = 'acorn_calendar_event';


    public function up()
    {
        if (!Schema::hasTable(self::$table))
            Schema::create(self::$table, function($table)
            {
                $table->engine = 'InnoDB';
                $table->increments('id')->unsigned();
                $table->integer('calendar_id')->unsigned();
                $table->string('external_url', 2048)->nullable();
                $table->timestamp('created_at')->nullable(false)->default('now()');
                $table->timestamp('updated_at')->nullable();

                $table->foreign('calendar_id')
                    ->references('id')->on('acorn_calendar')
                    ->onDelete('cascade');

                // Ownership
                $table->integer('owner_user_id')->unsigned();
                $table->integer('owner_user_group_id')->unsigned()->nullable();
                $table->integer('permissions')->unsigned()->default(7+8+64);
                $table->foreign('owner_user_id')
                    ->references('id')->on('backend_users')
                    ->onDelete('cascade');
                $table->foreign('owner_user_group_id')
                    ->references('id')->on('backend_user_groups')
                    ->onDelete('cascade');
            });
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
