<?php namespace Acorn\Calendar\Updates;

use Schema;
use \Acorn\Migration;
use DB;

class CreateFunctions extends Migration
{
    public function up()
    {
        // Useful for DEFAULTS for created_at_event_id
        // especially when simple seeding data
        // TODO: More control over attributes, like color
        // TODO: CompleteCreatedAtEvent should use this function also
        $this->createFunction('fn_acorn_calendar_create_event', array('type character varying(1024)'), 'uuid', array(
            	'owner_user_id uuid',
                'title character varying(1024)',
                'calendar_id uuid',
                'event_type_id uuid',
                'event_status_id uuid',
                'event_id uuid',
            ), <<<SQL
                title := initcap(replace(type, '_', ' '));
                select into owner_user_id fn_acorn_user_get_seed_user();
                select into event_status_id id from public.acorn_calendar_event_status limit 1;

                -- Find/Add
                select into calendar_id id from public.acorn_calendar where name = title;
                if calendar_id is null then
                    insert into public.acorn_calendar(name, owner_user_id) values(title, owner_user_id) limit 1 returning id into calendar_id;
                end if;

                select into event_type_id id from public.acorn_calendar_event_type where name = 'Create' limit 1;
                if event_type_id is null then
                    insert into public.acorn_calendar_event_type(name, colour, style) values('Create', '#091386', 'color:#fff') returning id into event_type_id;
                end if;

                -- Create event
                insert into public.acorn_calendar_event(calendar_id, owner_user_id) values(calendar_id, owner_user_id) returning id into event_id;
                insert into public.acorn_calendar_event_part(event_id, type_id, status_id, name, start, "end")
                    values(event_id, event_type_id, event_status_id, concat(title, ' ', 'Create'), now(), now());

                return event_id;
SQL
        );
    }

    public function down()
    {
        Schema::dropIfExists('fn_acorn_calendar_create_event');
    }
}
