<?php namespace AcornAssociated\Calendar\Updates;

use Schema;
use \AcornAssociated\Migration as AcornAssociatedMigration;

class BuilderTableCreateAcornassociatedMessagingInstance extends AcornAssociatedMigration
{
    static protected $table = 'acornassociated_messaging_message_instance';

    public function up()
    {
        if (!Schema::hasTable(self::$table))
            Schema::create(self::$table, function($table)
            {
                $table->engine = 'InnoDB';
                $table->integer('message_id');
                $table->integer('instance_id');
                $table->timestamp('created_at')->nullable(false)->default('now()');
                $table->timestamp('updated_at')->nullable();
                $table->primary(['message_id', 'instance_id']);

                // TODO: These need to be done, after the Messaging is installed...
                $table->foreign('message_id')
                    ->references('id')->on('acornassociated_messaging_message')
                    ->onDelete('cascade');
                $table->foreign('instance_id')
                    ->references('id')->on('acornassociated_calendar_instance')
                    ->onDelete('cascade');
            });
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
