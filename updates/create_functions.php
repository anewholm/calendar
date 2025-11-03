<?php namespace Acorn\Calendar\Updates;

use Schema;
use \Acorn\Migration;
use DB;

class CreateFunctions extends Migration
{
    public function up()
    {
        $this->createFunction('fn_acorn_calendar_seed', 
            array(), 
            'void', 
            array(),
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

        $this->createFunction('fn_acorn_calendar_create_event', array(
                'p_calendar_id uuid',
                'p_owner_user_id uuid',
                'p_type_id uuid',
                'p_status_id uuid',
                'p_name character varying',
                'p_date_from timestamp without time zone',
                'p_date_to timestamp without time zone',
                'p_container_event_id uuid DEFAULT NULL'
            ), 
            'uuid', 
            array('p_new_event_id uuid'),
        <<<SQL
            insert into acorn_calendar_events(calendar_id, owner_user_id) 
                values(p_calendar_id, p_owner_user_id) returning id into p_new_event_id;
            insert into acorn_calendar_event_parts(event_id, type_id, status_id, name, start, "end", parent_event_part_id) 
                values(p_new_event_id, p_type_id, p_status_id, p_name, p_date_from, p_date_to, p_container_event_id);
            return p_new_event_id;
SQL
        );

        $this->createFunction('fn_acorn_calendar_create_event', array(
                'p_calendar_id uuid',
                'p_owner_user_id uuid',
                'p_type_id uuid',
                'p_status_id uuid',
                'p_name character varying',
                'p_container_event_id uuid DEFAULT NULL'
            ), 
            'uuid', 
            array(),
        <<<SQL
            return public.fn_acorn_calendar_create_event(p_calendar_id, p_owner_user_id, p_type_id, p_status_id, p_name, now()::timestamp without time zone, now()::timestamp without time zone, p_container_event_id);
SQL
        );

        $this->createFunction('fn_acorn_calendar_lazy_create_event', array(
                'p_calendar_name character varying',
                'p_owner_user_id uuid',
                'p_type_name character varying',
                'p_status_name character varying',
                'p_event_name character varying'
            ), 
            'uuid', 
            array(
                'p_event_calendar_id uuid',
                'p_event_type_id uuid',
                'p_event_status_id  uuid'
            ),
        <<<SQL
            -- Lazy creates
            select into p_event_calendar_id id from acorn_calendar_calendars where name = p_calendar_name;
            if p_event_calendar_id is null then
                insert into acorn_calendar_calendars(name) values(p_calendar_name) returning id into p_event_calendar_id;
            end if;
        
            select into p_event_type_id id from acorn_calendar_event_types where name = p_type_name;
            if p_event_type_id is null then
                insert into acorn_calendar_event_types(name, calendar_id) values(p_type_name, p_event_calendar_id) returning id into p_event_type_id;
            end if;
        
            select into p_event_status_id id from acorn_calendar_event_statuses where name = p_status_name;
            if p_event_status_id is null then
                insert into acorn_calendar_event_statuses(name, calendar_id) values(p_status_name, p_event_calendar_id) returning id into p_event_status_id;
            end if;
        
            return public.fn_acorn_calendar_create_event(p_event_calendar_id, p_owner_user_id, p_event_type_id, p_event_status_id, p_event_name);
SQL
        );

        /*
        // TODO: Old upcreated_at_event_id trigger based system
        $this->createFunction('fn_acorn_calendar_create_activity_log_event', array(
                'owner_user_id uuid',
                'type_id uuid',
                'status_id uuid',
                'name character varying'
            ), 
            'uuid', 
            array(
                'calendar_id uuid'
            ),
        <<<SQL
            -- Calendar (system): acorn.justice::lang.plugin.activity_log
            -- Type: indicates the Model
            -- Status: indicates the action: create, update, delete, etc.
            calendar_id   := 'f3bc49bc-eac7-11ef-9e4a-1740a039dada';
            if not exists(select * from acorn_calendar_calendars where "id" = 'f3bc49bc-eac7-11ef-9e4a-1740a039dada'::uuid) then
                -- Just in case database seeding is happening before calendar seeding, or the system types have been deleted
                perform public.fn_acorn_calendar_seed();
            end if;
	
            return public.fn_acorn_calendar_create_event(calendar_id, owner_user_id, type_id, status_id, name);
SQL
        );

        $this->createFunction('fn_acorn_calendar_trigger_activity_event', array(),
            'trigger', 
            array(
                'name_optional character varying(2048)',
                'soft_delete_optional boolean = false',
                'table_comment character varying(16384)',
                'type_name character varying(1024)',
                'title character varying(1024)',
                'owner_user_id uuid',
                'event_type_id uuid',
                'event_status_id uuid',
                "activity_log_calendar_id uuid = 'f3bc49bc-eac7-11ef-9e4a-1740a039dada'"
            ),
        <<<SQL
            -- See also: fn_acorn_calendar_create_activity_log_event()
            -- Calendar (system): acorn.justice::lang.plugin.activity_log
            -- Type: indicates the Plugin & Model, e.g. "Criminal Trials"
            -- Status: indicates the action: INSERT, UPDATE, DELETE, or other custom

            -- This trigger function should only be used on final content tables
            -- This is a generic trigger. Some fields are required, others optional
            -- We use PG system catalogs because they are faster
            -- TODO: Process name-object linkage
            
            if not exists(select * from acorn_calendar_calendars where "id" = 'f3bc49bc-eac7-11ef-9e4a-1740a039dada'::uuid) then
                -- Just in case database seeding is happening before calendar seeding, or the system types have been deleted
                perform public.fn_acorn_calendar_seed();
            end if;
            
            -- Required fields
            -- created_at_event_id
            -- updated_at_event_id
            owner_user_id := NEW.created_by_user_id; -- NOT NULL
            type_name     := initcap(replace(replace(TG_TABLE_NAME, 'acorn_', ''), '_', ' '));
            title         := initcap(TG_OP) || ' ' || type_name;
			if owner_user_id is null then
                raise exception '% on %, created_by_user_id was NULL, and thus owner_user_id during fn_acorn_calendar_trigger_activity_event() auto-create', TG_OP, TG_TABLE_NAME;
			end if;

            -- Optional fields
            if exists(SELECT * FROM pg_attribute WHERE attrelid = TG_RELID AND attname = 'name') then name_optional := NEW.name; end if;
            if not name_optional is null then title = title || ':' || name_optional; end if;
            if exists(SELECT * FROM pg_attribute WHERE attrelid = TG_RELID AND attname = 'deleted_at') then soft_delete_optional := true; end if;

            -- TODO: Allow control from the table comment over event creation
            table_comment := obj_description(concat(TG_TABLE_SCHEMA, '.', TG_TABLE_NAME)::regclass, 'pg_class');

            -- Type: lang TG_TABLE_SCHEMA.TG_TABLE_NAME, acorn.justice::lang.models.related_events.label
            select into event_type_id id from acorn_calendar_event_types 
                where activity_log_related_oid = TG_RELID;
            if event_type_id is null then
                -- TODO: Colour?
                -- TODO: acorn.?::lang.models.?.label
                insert into public.acorn_calendar_event_types(name, activity_log_related_oid, calendar_id) 
                    values(type_name, TG_RELID, activity_log_calendar_id) returning id into event_type_id;
            end if;

            -- Scenarios
            case 
                when TG_OP = 'INSERT' then
                    -- Just in case the framework has specified it
                    if NEW.created_at_event_id is null then
                        -- Create event
                        event_status_id         := '7b432540-eac8-11ef-a9bc-434841a9f67b'; -- INSERT
                        NEW.created_at_event_id := public.fn_acorn_calendar_create_activity_log_event(owner_user_id, event_type_id, event_status_id, title);
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
                    
                    -- Update event
                    if NEW.updated_at_event_id is null then
                        -- Create the initial Update event for this item
                        NEW.created_at_event_id := public.fn_acorn_calendar_create_activity_log_event(owner_user_id, event_type_id, event_status_id, title);
                    else
                        -- Add a new event part to the same updated event
                        insert into public.acorn_calendar_event_parts(event_id, type_id, status_id, name, start, "end")
                            select event_id, type_id, status_id, name, now(), now() 
                            from public.acorn_calendar_event_parts 
                            where event_id = NEW.updated_at_event_id limit 1;
                    end if;
            end case;

            return NEW;
SQL
        );
        */
    }

    public function down()
    {
        Schema::dropIfExists('fn_acorn_calendar_seed');
        
        // TODO: Include parameters in drop logic
        // Schema::dropIfExists('fn_acorn_calendar_create_event');
        // Schema::dropIfExists('fn_acorn_calendar_create_event');
        // Schema::dropIfExists('fn_acorn_calendar_lazy_create_event');
        
        // Schema::dropIfExists('fn_acorn_calendar_create_activity_log_event');
        // Schema::dropIfExists('fn_acorn_calendar_trigger_activity_event');
    }
}
