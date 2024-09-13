<?php namespace Acorn\Calendar\Updates;

use Winter\Storm\Database\Updates\Seeder;
use Acorn\Calendar\Models\Status;
use Acorn\Calendar\Models\Type;
use DB;

class SeedCalendar extends Seeder
{
    public function run()
    {
        DB::table('acorn_calendar')->insert([
            'name' => 'Default',
            'owner_user_id' => 1,
            'permissions' => 511,
        ]);
    }
}
