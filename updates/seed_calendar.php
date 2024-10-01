<?php namespace Acorn\Calendar\Updates;

use Winter\Storm\Database\Updates\Seeder;
use Acorn\Calendar\Models\Status;
use Acorn\Calendar\Models\Type;
use DB;

class SeedCalendar extends Seeder
{
    public function run()
    {
        DB::unprepared("
            insert into acorn_user_users(name, email, password) values('DEMO user', 'demo@user.com', 'password');
            insert into acorn_calendar(name, owner_user_id, permissions)
                select 'Default', id, 511 from acorn_user_users limit 1;
        ");
    }
}
