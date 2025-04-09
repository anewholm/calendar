<?php namespace Acorn\Calendar\Updates;

use Winter\Storm\Database\Updates\Seeder;
use Acorn\Calendar\Models\EventStatus;
use Acorn\Calendar\Models\EventType;
use DB;

class SeedCalendar extends Seeder
{
    public function run()
    {
        DB::unprepared('select fn_acorn_calendar_seed()');
    }
}
