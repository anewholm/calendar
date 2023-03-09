<?php namespace AcornAssociated\Calendar\Updates;

use Winter\Storm\Database\Updates\Seeder;
use AcornAssociated\Calendar\Models\Status;
use AcornAssociated\Calendar\Models\Type;
use DB;

class SeedCalendar extends Seeder
{
    public function run()
    {
        DB::table('acornassociated_calendar')->insert([
            'name' => 'Default',
            'owner_user_id' => 1,
        ]);
    }
}
