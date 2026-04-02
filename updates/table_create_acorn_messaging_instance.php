<?php namespace Acorn\Calendar\Updates;

use Schema;
use \Acorn\Migration as AcornMigration;

class BuilderTableCreateAcornMessagingInstance extends AcornMigration
{
    static protected $table = 'acorn_messaging_message_instance';

    public function up()
    {
        if (!Schema::hasTable(self::$table))
            Schema::create(self::$table, function($table)
            {
                $table->engine = 'InnoDB';
                $table->uuid('message_id');
                $table->uuid('instance_id');
                $table->timestamp('created_at')->nullable(false)->default('now()');
                $table->timestamp('updated_at')->nullable();
                $table->primary(['message_id', 'instance_id']);

                $table->foreign('instance_id')
                    ->references('id')->on('acorn_calendar_instances')
                    ->onDelete('cascade');
            });

        // FK to Acorn.Messaging module — optional, added only when Messaging module is installed.
        if (Schema::hasTable('acorn_messaging_message')) {
            Schema::table(self::$table, function($table) {
                $table->foreign('message_id')
                    ->references('id')->on('acorn_messaging_message')
                    ->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
