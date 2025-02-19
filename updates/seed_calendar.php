<?php namespace AcornAssociated\Calendar\Updates;

use Winter\Storm\Database\Updates\Seeder;
use AcornAssociated\Calendar\Models\EventStatus;
use AcornAssociated\Calendar\Models\EventType;
use DB;

class SeedCalendar extends Seeder
{
    public function run()
    {
        DB::unprepared('select fn_acornassociated_calendar_seed()');
    }
}
