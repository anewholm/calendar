<?php namespace AcornAssociated\Calendar\Updates;

use Schema;
use AcornAssociated\Calendar\Updates\AcornAssociatedMigration;

class BuilderTableCreateAcornassociatedCalendarEventUser extends AcornAssociatedMigration
{
    static protected $table = 'acornassociated_calendar_event_user';

    public function up()
    {
        Schema::create(self::$table, function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('event_part_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned()->default(1);
            $table->timestamp('created_at')->nullable(false)->default('now()');
            $table->timestamp('updated_at')->nullable();
            $table->primary(['event_part_id', 'user_id', 'role_id']);

            $table->foreign('event_part_id')
                ->references('id')->on('acornassociated_calendar_event_part')
                ->onDelete('cascade');
            $table->foreign('user_id')
                ->references('id')->on('backend_users')
                ->onDelete('cascade');
            $table->foreign('role_id')
                ->references('id')->on('backend_user_roles')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        $this->dropCascade(self::table);
    }
}
