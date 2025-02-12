<?php namespace Acorn\Calendar\Updates;

use Schema;
use \Acorn\Migration;
use DB;

class CreateFunctions extends Migration
{
    public function up()
    {
        $this->createFunction('fn_acorn_calendar_seed', array(), 'void', array(),
        <<<SQL
            -- Default calendar, with hardcoded id
            if not exists(select * from acorn_calendars where "id" = 'ceea8856-e4c8-11ef-8719-5f58c97885a2'::uuid) then
                insert into acorn_calendars(id, "name", "system") 
                    values('ceea8856-e4c8-11ef-8719-5f58c97885a2'::uuid, 'Default', true);
            end if;

            -- System Statuses. Cannot be deleted
            if not exists(select * from acorn_calendar_event_statuses where "id" = '27446472-e4c9-11ef-bde0-9b663c96a619'::uuid) then
                insert into acorn_calendar_event_statuses(id, "name", "system") 
                    values('27446472-e4c9-11ef-bde0-9b663c96a619'::uuid, 'Normal', TRUE);
            end if;
            if not exists(select * from acorn_calendar_event_statuses where "id" = 'fb2392de-e62e-11ef-b202-5fe79ff1071f') then
                insert into acorn_calendar_event_statuses(id, "name", "system", "style") 
                    values('fb2392de-e62e-11ef-b202-5fe79ff1071f', 'Cancelled', TRUE, 'text-decoration:line-through;border:1px dotted #fff;');
            end if;
            if not exists(select * from acorn_calendar_event_statuses where "name" = 'Tentative') then
                insert into acorn_calendar_event_statuses("name", "system", "style") 
                    values('Tentative', TRUE, 'opacity:0.7;');
            end if;
            -- TODO: Does status "Conflict" make sense? Because maybe only 1 instance will conflict
            if not exists(select * from acorn_calendar_event_statuses where "name" = 'Conflict') then
                insert into acorn_calendar_event_statuses("name", "system", "style") 
                    values('Conflict', TRUE, 'border:1px solid red;background-color:#fff;color:#000;font-weight:bold;');
            end if;

            -- System Types. Cannot be deleted
            if not exists(select * from acorn_calendar_event_types where "id" = '2f766546-e4c9-11ef-be8c-1f2daa98a10f'::uuid) then
                insert into acorn_calendar_event_types(id, "name", "system", "colour", "style") 
                    values('2f766546-e4c9-11ef-be8c-1f2daa98a10f'::uuid, 'Normal', TRUE, '#091386', 'color:#fff');
            end if;
            if not exists(select * from acorn_calendar_event_types where "name" = 'Meeting') then
                insert into acorn_calendar_event_types("name", "system", "colour", "style") 
                    values('Meeting', TRUE, '#C0392B', 'color:#fff');
            end if;
SQL
        );

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
                select into event_status_id id from public.acorn_calendar_event_statuses limit 1;

                -- Find/Add
                select into calendar_id id from public.acorn_calendars where name = title;
                if calendar_id is null then
                    insert into public.acorn_calendars(name, owner_user_id) values(title, owner_user_id) limit 1 returning id into calendar_id;
                end if;

                select into event_type_id id from public.acorn_calendar_event_types where name = 'Create' limit 1;
                if event_type_id is null then
                    insert into public.acorn_calendar_event_types(name, colour, style) values('Create', '#091386', 'color:#fff') returning id into event_type_id;
                end if;

                -- Create event
                insert into public.acorn_calendar_events(calendar_id, owner_user_id) values(calendar_id, owner_user_id) returning id into event_id;
                insert into public.acorn_calendar_event_parts(event_id, type_id, status_id, name, start, "end")
                    values(event_id, event_type_id, event_status_id, concat(title, ' ', 'Create'), now(), now());

                return event_id;
SQL
        );
    }

    public function down()
    {
        Schema::dropIfExists('fn_acorn_calendar_create_event');
        Schema::dropIfExists('fn_acorn_calendar_seed');
    }
}
