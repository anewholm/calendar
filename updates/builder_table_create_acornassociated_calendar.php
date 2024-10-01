<?php namespace Acorn\Calendar\Updates;

use DB;
use Schema;
use \Acorn\Migration as AcornMigration;

class BuilderTableCreateAcornCalendar extends AcornMigration
{
    static protected $table = 'acorn_calendar';

    public function up()
    {
        if (!Schema::hasTable(self::$table))
            Schema::create(self::$table, function($table)
            {
                $table->engine = 'InnoDB';
                $table->uuid('id')->primary()->default(DB::raw('(gen_random_uuid())'));
                $table->string('name', 1024);
                $table->text('description')->nullable();
                $table->string('sync_file', 4096)->nullable();
                $table->integer('sync_format')->default(0); // 0 - ICS
                $table->timestamp('created_at')->nullable(false)->default('now()');
                $table->timestamp('updated_at')->nullable();

                // Ownership
                $table->uuid('owner_user_id');
                $table->uuid('owner_user_group_id')->nullable();
                $table->integer('permissions')->unsigned()->default();
                $table->foreign('owner_user_id')
                    ->references('id')->on('acorn_user_users')
                    ->onDelete('cascade');
                $table->foreign('owner_user_group_id')
                    ->references('id')->on('acorn_user_user_groups')
                    ->onDelete('cascade');
            });
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
