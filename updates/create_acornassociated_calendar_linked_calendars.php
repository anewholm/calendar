<?php namespace Acorn\Calendar\Updates;

use DB;
use Schema;
use \Acorn\Migration;

class CreateAcornCalendarLinkedCalendars extends Migration
{
    static protected $table = 'acorn_calendar_linked_calendars';


    public function up()
    {
        // The view is updated automatically by create-system
        // Events will show their associated Models using this view
        if (!Schema::hasTable(self::$table))
            parent::createView(self::$table, <<<BODY
                select 
                    NULL::uuid as calendar_id,
                    NULL::character varying(2048) as schema,
                    NULL::character varying(2048) as table,
                    NULL::character varying(2048) as column,
                    NULL::character varying(2048) as model_type,
                    NULL::uuid as model_id
                where false
BODY
        );
    }

    public function down()
    {
        $this->dropCascade(self::$table);
    }
}
