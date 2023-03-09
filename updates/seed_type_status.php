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
            DB::table('acornassociated_calendar_event_status')->insert(['name' => 'Normal']);
            // TODO: Does status "Conflict" make sense? Because maybe only 1 instance will conflict
            DB::table('acornassociated_calendar_event_status')->insert(['name' => 'Conflict', 'style'  => 'border:1px solid red;background-color:#fff;color:#000;font-weight:bold;']);
            DB::table('acornassociated_calendar_event_status')->insert(['name' => 'Tentative']);
            DB::table('acornassociated_calendar_event_status')->insert(['name' => 'Cancelled']);
        }

        if (!Type::count()) {
            DB::table('acornassociated_calendar_event_type')->insert(['name' => 'Normal',  'colour' => '#091386', 'style'  => 'color:#fff']);
            DB::table('acornassociated_calendar_event_type')->insert(['name' => 'Meeting', 'colour' => '#C0392B', 'style'  => 'color:#fff']);
        }
    }
}
