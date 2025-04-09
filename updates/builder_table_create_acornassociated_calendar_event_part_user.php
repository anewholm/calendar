<?php namespace AcornAssociated\Calendar\Updates;

use Schema;
use \AcornAssociated\Migration as AcornAssociatedMigration;

class BuilderTableCreateAcornassociatedCalendarEventPartUser extends AcornAssociatedMigration
{
    static protected $table = 'acornassociated_calendar_event_part_user';

    public function up()
    {
        if (!Schema::hasTable(self::$table))
            Schema::create(self::$table, function($table)
            {
                $table->engine = 'InnoDB';
                $table->uuid('event_part_id');
                $table->uuid('user_id');
                $table->uuid('role_id')->nullable();
                $table->timestamp('created_at')->nullable(false)->default('now()');
                $table->timestamp('updated_at')->nullable();
                $table->primary(['event_part_id', 'user_id', 'role_id']);

                $table->foreign('event_part_id')
                    ->references('id')->on('acornassociated_calendar_event_parts')
                    ->onDelete('cascade');
                $table->foreign('user_id')
                    ->references('id')->on('acornassociated_user_users')
                    ->onDelete('cascade');
                $table->foreign('role_id')
                    ->references('id')->on('acornassociated_user_roles')
                    ->onDelete('cascade');
            });

        $this->setTableTypeContent(self::$table);
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
