<?php namespace AcornAssociated\Calendar\Updates;

use Winter\Storm\Database\Updates\Seeder;
use AcornAssociated\Calendar\Models\Status;
use AcornAssociated\Calendar\Models\Type;
use DB;

class SeedTypeStatus extends Seeder
{
    public function run()
    {
        if (!Status::count()) {
            // System Statuses. Cannot be deleted
            DB::table('acornassociated_calendar_event_status')->insert(['id' => 1, 'name' => 'Normal']);
            DB::table('acornassociated_calendar_event_status')->insert(['id' => 2, 'name' => 'Cancelled', 'style' => 'text-decoration:line-through;border:1px dotted #fff;']);
            DB::table('acornassociated_calendar_event_status')->insert(['id' => 3, 'name' => 'Tentative', 'style' => 'opacity:0.7;']);
            // TODO: Does status "Conflict" make sense? Because maybe only 1 instance will conflict
            DB::table('acornassociated_calendar_event_status')->insert(['id' => 4, 'name' => 'Conflict',  'style' => 'border:1px solid red;background-color:#fff;color:#000;font-weight:bold;']);
        }

        if (!Type::count()) {
            // System Types. Cannot be deleted
            DB::table('acornassociated_calendar_event_type')->insert(['id' => 1, 'name' => 'Normal',  'colour' => '#091386', 'style'  => 'color:#fff']);
            DB::table('acornassociated_calendar_event_type')->insert(['id' => 2, 'name' => 'Meeting', 'colour' => '#C0392B', 'style'  => 'color:#fff']);
        }
    }
}
