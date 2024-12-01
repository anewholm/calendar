<?php namespace Acorn\Calendar\Updates;

use Winter\Storm\Database\Updates\Seeder;
use Acorn\Calendar\Models\Status;
use Acorn\Calendar\Models\Type;
use DB;

class SeedTypeStatus extends Seeder
{
    public function run()
    {
        if (!Status::count()) {
            // System Statuses. Cannot be deleted
            DB::table('acorn_calendar_event_status')->insert(['name' => 'Normal', 'system' => TRUE]);
            DB::table('acorn_calendar_event_status')->insert(['name' => 'Cancelled', 'style' => 'text-decoration:line-through;border:1px dotted #fff;', 'system' => TRUE]);
            DB::table('acorn_calendar_event_status')->insert(['name' => 'Tentative', 'style' => 'opacity:0.7;', 'system' => TRUE]);
            // TODO: Does status "Conflict" make sense? Because maybe only 1 instance will conflict
            DB::table('acorn_calendar_event_status')->insert(['name' => 'Conflict',  'style' => 'border:1px solid red;background-color:#fff;color:#000;font-weight:bold;', 'system' => TRUE]);
        }

        if (!Type::count()) {
            // System Types. Cannot be deleted
            DB::table('acorn_calendar_event_type')->insert(['name' => 'Normal',  'colour' => '#091386', 'style'  => 'color:#fff', 'system' => TRUE]);
            DB::table('acorn_calendar_event_type')->insert(['name' => 'Meeting', 'colour' => '#C0392B', 'style'  => 'color:#fff', 'system' => TRUE]);
        }
    }
}
