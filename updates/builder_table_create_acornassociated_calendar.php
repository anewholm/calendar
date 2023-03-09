<?php namespace AcornAssociated\Calendar\Updates;

use Schema;
use AcornAssociated\Calendar\Updates\AcornAssociatedMigration;

class BuilderTableCreateAcornassociatedCalendar extends AcornAssociatedMigration
{
    static protected $table = 'acornassociated_calendar';

    public function up()
    {
        Schema::create(self::$table, function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name', 1024);
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable(false)->default('now()');
            $table->timestamp('updated_at')->nullable();

            // Ownership
            $table->integer('owner_user_id')->unsigned();
            $table->integer('owner_user_group_id')->unsigned()->nullable();
            $table->integer('permissions')->unsigned()->default();
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
