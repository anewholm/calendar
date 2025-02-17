<?php namespace Acorn\Calendar\Updates;

use Schema;
use \Acorn\Migration;
use DB;

class CreateFunctions extends Migration
{
    public function up()
    {
        // TODO: Finish fn_acorn_calendar_create_activity_log_event
        $this->createFunction('fn_acorn_calendar_create_activity_log_event', array(
            'type character varying',
            'user_id uuid',
        ),
         'uuid',
         array(
            'owner_user_id uuid',
            'title character varying(1024)',
            'calendar_id uuid',
            'event_type_id uuid',
            'event_status_id uuid',
            'new_event_id uuid',
         ),
        <<<SQL
            -- Calendar (system): acorn.justice::lang.plugin.activity_log
            calendar_id   := 'f3bc49bc-eac7-11ef-9e4a-1740a039dada';
            title         := initcap(replace(type, '_', ' '));
            owner_user_id := user_id;
        
            select into event_status_id id from public.acorn_calendar_event_statuses limit 1;
            insert into public.acorn_calendar_event_types(name, colour, style) values('Create', '#091386', 'color:#fff') returning id into event_type_id;
        
            insert into public.acorn_calendar_events(calendar_id, owner_user_id) values(calendar_id, owner_user_id) returning id into new_event_id;
            insert into public.acorn_calendar_event_parts(event_id, type_id, status_id, name, start, "end") 
                values(new_event_id, event_type_id, event_status_id, concat(title, ' ', 'Create'), now(), now());
        
            return new_event_id;
SQL
        );
        
        $this->createFunction('fn_acorn_calendar_seed', array(), 'void', array(),
        <<<SQL
            -- Default calendars, with hardcoded ids
            if not exists(select * from acorn_calendar_calendars where "id" = 'ceea8856-e4c8-11ef-8719-5f58c97885a2'::uuid) then
                insert into acorn_calendar_calendars(id, "name", "system") 
                    values('ceea8856-e4c8-11ef-8719-5f58c97885a2'::uuid, 'Default', true);
            end if;
            if not exists(select * from acorn_calendar_calendars where "id" = 'f3bc49bc-eac7-11ef-9e4a-1740a039dada'::uuid) then
                insert into acorn_calendar_calendars(id, "name", "system") 
                    values('f3bc49bc-eac7-11ef-9e4a-1740a039dada'::uuid, 'Activity Log', true);
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
            -- Types for each table in the activity log are lazy created
            if not exists(select * from acorn_calendar_event_types where "id" = '2f766546-e4c9-11ef-be8c-1f2daa98a10f'::uuid) then
                insert into acorn_calendar_event_types(id, "name", "system", "colour", "style") 
                    values('2f766546-e4c9-11ef-be8c-1f2daa98a10f'::uuid, 'Normal', TRUE, '#091386', 'color:#fff');
            end if;
            if not exists(select * from acorn_calendar_event_types where "name" = 'Meeting') then
                insert into acorn_calendar_event_types("name", "system", "colour", "style") 
                    values('Meeting', TRUE, '#C0392B', 'color:#fff');
            end if;

            -- Activity log statuses: TG_OP / Soft DELETE
            if not exists(select * from acorn_calendar_event_statuses where "id" = '7b432540-eac8-11ef-a9bc-434841a9f67b'::uuid) then
                insert into acorn_calendar_event_statuses(id, "name", "system", "style") 
                    values('7b432540-eac8-11ef-a9bc-434841a9f67b'::uuid, 'acorn.calendar::lang.models.general.insert', TRUE, 'color:#fff');
            end if;
            if not exists(select * from acorn_calendar_event_statuses where "id" = '7c18bb7e-eac8-11ef-b4f2-ffae3296f461'::uuid) then
                insert into acorn_calendar_event_statuses(id, "name", "system", "style") 
                    values('7c18bb7e-eac8-11ef-b4f2-ffae3296f461'::uuid, 'acorn.calendar::lang.models.general.update', TRUE, 'color:#fff');
            end if;
            -- Soft DELETE (Actually an UPDATE TG_OP)
            if not exists(select * from acorn_calendar_event_statuses where "id" = '7ceca4c0-eac8-11ef-b685-f7f3f278f676'::uuid) then
                insert into acorn_calendar_event_statuses(id, "name", "system", "style") 
                    values('7ceca4c0-eac8-11ef-b685-f7f3f278f676'::uuid, 'acorn.calendar::lang.models.general.soft_delete', TRUE, 'color:#fff');
            end if;
            if not exists(select * from acorn_calendar_event_statuses where "id" = 'f9690600-eac9-11ef-8002-5b2cbe0c12c0'::uuid) then
                insert into acorn_calendar_event_statuses(id, "name", "system", "style") 
                    values('f9690600-eac9-11ef-8002-5b2cbe0c12c0'::uuid, 'acorn.calendar::lang.models.general.soft_undelete', TRUE, 'color:#fff');
            end if;
SQL
        );

        // Useful for trigger for created_at_event_id|updated_at_event_id
        // especially when simple seeding data
        $this->createFunction('fn_acorn_calendar_trigger_created_at_event', [], 'trigger', array(
                'name_optional character varying(2048)',
                'soft_delete_optional boolean = false',

                'table_comment character varying(2048)',
                'table_title character varying(1024)',
                'title character varying(1024)',
                'event_time timestamp = now()',
                'owner_user_id uuid',
                'calendar_id uuid',
                'new_event_id uuid',
                'event_type_id uuid',
                'event_status_id uuid',
            ), <<<SQL
                -- This trigger function should only be used on final content tables
                -- This is a generic trigger. Some fields are required, others optional
                -- We use PG system catalogs because they are faster
                -- // TODO: Process name-object linkage
                -- // TODO: CompleteCreatedAtEvent should be removed if using this function
                -- // TODO: External URL
                -- // TODO: Allow control from the table comment over event creation
                table_comment := obj_description(concat(TG_TABLE_SCHEMA, '.', TG_TABLE_NAME)::regclass, 'pg_class');
                
                -- Required fields
                -- created_at_event_id
                -- updated_at_event_id
                owner_user_id := NEW.created_by_user_id; -- NOT NULL
                table_title   := replace(replace(TG_TABLE_NAME, 'acorn_', ''), '_', ' ');
                title         := TG_OP || ' ' || table_title;

                -- Optional fields
                if exists(SELECT * FROM pg_attribute WHERE attrelid = TG_RELID AND attname = 'name') then name_optional := NEW.name; end if;
                if not name_optional is null then title = title || ':' || name_optional; end if;
                if exists(SELECT * FROM pg_attribute WHERE attrelid = TG_RELID AND attname = 'deleted_at') then soft_delete_optional := true; end if;

                -- Calendar (system): acorn.justice::lang.plugin.activity_log
                calendar_id   := 'f3bc49bc-eac7-11ef-9e4a-1740a039dada';
                
                -- Type: lang TG_TABLE_SCHEMA.TG_TABLE_NAME, acorn.justice::lang.models.related_events.label
                select into event_type_id id from acorn_calendar_event_types where activity_log_related_oid = TG_RELID;
                if event_type_id is null then
                    -- TODO: Colour?
                    -- TODO: acorn.?::lang.models.?.label
                    insert into public.acorn_calendar_event_types(name, activity_log_related_oid) values(table_title, TG_RELID) returning id into event_type_id;
                end if;

                -- Scenarios
                case 
                    when TG_OP = 'INSERT' then
                        -- Just in case the framework has specified it
                        if NEW.created_at_event_id is null then
                            -- Create event
                            event_status_id := '7b432540-eac8-11ef-a9bc-434841a9f67b'; -- INSERT
                            insert into public.acorn_calendar_events(calendar_id, owner_user_id) values(calendar_id, owner_user_id) returning id into new_event_id;
                            insert into public.acorn_calendar_event_parts(event_id, type_id, status_id, name, start, "end") 
                                values(new_event_id, event_type_id, event_status_id, title, event_time, event_time);
                            NEW.created_at_event_id = new_event_id;
                        end if;
                    when TG_OP = 'UPDATE' then 
                        event_status_id := '7c18bb7e-eac8-11ef-b4f2-ffae3296f461'; -- UPDATE
                        if soft_delete_optional then
                            if not NEW.deleted_at = OLD.deleted_at then
                                case
                                    when not NEW.deleted_at is null then event_status_id := '7ceca4c0-eac8-11ef-b685-f7f3f278f676'; -- Soft DELETE
                                    else                                 event_status_id := 'f9690600-eac9-11ef-8002-5b2cbe0c12c0'; -- Soft un-DELETE
                                end case;
                            end if;
                        end if;
                        
                        if NEW.updated_at_event_id is null then
                            -- Update event
                            insert into public.acorn_calendar_events(calendar_id, owner_user_id) values(calendar_id, owner_user_id) returning id into new_event_id;
                            insert into public.acorn_calendar_event_parts(event_id, type_id, status_id, name, start, "end") 
                                values(new_event_id, event_type_id, event_status_id, title, event_time, event_time);
                            NEW.updated_at_event_id = new_event_id;
                        else
                            update public.acorn_calendar_event_parts set 
                                "start"   = event_time, 
                                "end"     = event_time,
                                status_id = event_status_id,
                                "name"    = title
                                where event_id = NEW.updated_at_event_id;
                        end if;
                end case;

                return NEW;
SQL
        );
    }

    public function down()
    {
        Schema::dropIfExists('fn_acorn_calendar_trigger_created_at_event');
        Schema::dropIfExists('fn_acorn_calendar_seed');
    }
}
