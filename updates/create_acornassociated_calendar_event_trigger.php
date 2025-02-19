<?php namespace AcornAssociated\Calendar\Updates;

use Schema;
use \AcornAssociated\Migration as AcornAssociatedMigration;
use DB;

class CreateAcornassociatedCalendarEventTrigger extends AcornAssociatedMigration
{
    public function up()
    {
        // TODO: Is this a VOLATILE or LEAKPROOF trigger?
        // TODO: Make this as a view and insert from it, so that out-of-bounds queries are possible
        // TODO: generate series size should be different depending on the repetition period
        //   because currently, a day period will repeat for 2 years, but a year period will repeat for 365*2*1 years
        /* Result, after $SQL injected:
        CREATE OR REPLACE FUNCTION public.fn_acornassociated_calendar_generate_event_instances(new_event_part record, old_event_part record)
            RETURNS record
            LANGUAGE 'plpgsql'
            COST 100
            VOLATILE NOT LEAKPROOF
        AS $BODY$
        declare
            days_before interval;
            days_after interval;
            days_count int;
            today date := now();
            date_start date;
        begin
            -- This function creates the individual event instances on specific dates
            -- from event definitions, that can have preiodic repetition
            -- For example, an single event definition that repeats weekly for 2 months
            -- may have 9 individual event instances on specific dates
            -- Declares are configurable from WinterCMS settings
        
            -- Check if anything repeaty has changed (not locked_by_user_id)
            if     old_event_part is null
                or new_event_part.start  is distinct from old_event_part.start
                or new_event_part."end"  is distinct from old_event_part."end"
                or new_event_part.until  is distinct from old_event_part.until
                or new_event_part.mask   is distinct from old_event_part.mask
                or new_event_part.repeat is distinct from old_event_part.repeat
                or new_event_part.mask_type is distinct from old_event_part.mask_type
                or new_event_part.repeat_frequency     is distinct from old_event_part.repeat_frequency
                or new_event_part.parent_event_part_id is distinct from old_event_part.parent_event_part_id
                or new_event_part.instances_deleted    is distinct from old_event_part.instances_deleted
            then
                -- Settings
                select coalesce((select substring("value" from '"days_before":"([^"]+)"')
                    from system_settings where item = 'acornassociated_calendar_settings'), '1 year')
                    into days_before;
                select coalesce((select substring("value" from '"days_after":"([^"]+)"')
                    from system_settings where item = 'acornassociated_calendar_settings'), '2 years')
                    into days_after;
                select extract('epoch' from days_before + days_after)/3600/24.0
                    into days_count;
                select today - days_before
                    into date_start;
        
                -- For updates (id cannot change)
                delete from acornassociated_calendar_instances where event_part_id = new_event_part.id;
                -- TODO: use a sub-ID also for created_at, updated_at etc.
        
                -- For inserts
                insert into acornassociated_calendar_instances("date", event_part_id, instance_start, instance_end, instance_num)
                select date_start + interval '1' day * gs as "date", ev.*
                from generate_series(0, days_count) as gs
                inner join (
                    -- single event
                    select new_event_part.id as event_part_id,
                        new_event_part."start" as "instance_start",
                        new_event_part."end"   as "instance_end",
                        0 as instance_num
                    where new_event_part.repeat is null
                union all
                    -- repetition, no parent container
                    select new_event_part.id as event_part_id,
                        new_event_part."start" + new_event_part.repeat_frequency * new_event_part."repeat" * gs.gs as "instance_start",
                        new_event_part."end" + new_event_part.repeat_frequency * new_event_part."repeat" * gs.gs   as "instance_end",
                        gs.gs as instance_num
                    from generate_series(0, days_count) as gs
                    where not new_event_part.repeat is null and new_event_part.parent_event_part_id is null
                    and (new_event_part.instances_deleted is null or not gs.gs = any(new_event_part.instances_deleted))
                    and (new_event_part.until is null or new_event_part."start" + new_event_part.repeat_frequency * new_event_part."repeat" * gs.gs < new_event_part.until)
                    and (new_event_part.mask = 0 or new_event_part.mask & (2^date_part(new_event_part.mask_type, new_event_part."start" + new_event_part.repeat_frequency * new_event_part."repeat" * gs.gs))::int != 0)
                union all
                    -- repetition with parent_event_part_id container calendar events
                    select new_event_part.id as event_part_id,
                        new_event_part."start" + new_event_part.repeat_frequency * new_event_part."repeat" * gs.gs as "instance_start",
                        new_event_part."end" + new_event_part.repeat_frequency * new_event_part."repeat" * gs.gs   as "instance_end",
                        gs.gs as instance_num
                    from generate_series(0, days_count) as gs
                    inner join acornassociated_calendar_instances pcc on new_event_part.parent_event_part_id = pcc.event_part_id
                        and (pcc.date, pcc.date + 1)
                        overlaps (new_event_part."start" + new_event_part.repeat_frequency * new_event_part."repeat" * gs.gs, new_event_part."end" + new_event_part.repeat_frequency * new_event_part."repeat" * gs.gs)
                    where not new_event_part.repeat is null
                    and (new_event_part.instances_deleted is null or not gs.gs = any(new_event_part.instances_deleted))
                    and (new_event_part.until is null or new_event_part."start" + new_event_part.repeat_frequency * new_event_part."repeat" * gs.gs < new_event_part.until)
                    and (new_event_part.mask = 0 or new_event_part.mask & (2^date_part(new_event_part.mask_type, new_event_part."start" + new_event_part.repeat_frequency * new_event_part."repeat" * gs.gs))::int != 0)
                ) ev
                on  (date_start + interval '1' day * gs, date_start + interval '1' day * (gs+1))
                overlaps (ev.instance_start, ev.instance_end);
        
                -- Recursively update child event parts
                -- TODO: This could infinetly cycle
                update acornassociated_calendar_event_parts set id = id
                    where parent_event_part_id = new_event_part.id
                    and not id = new_event_part.id;
            end if;
        
            return new_event_part;
        end;
        $BODY$
        */
            
        // Insert & Update triggers
        $window_default_past   = '1 year';
        $window_default_future = '2 years';
        $SQL_day_count         = 'NEW.repeat_frequency * NEW."repeat" * gs.gs';
        $SQL_instance_start    = 'NEW."start" + ' . $SQL_day_count;
        $SQL_instance_end      = 'NEW."end" + '   . $SQL_day_count;
        $SQL_generate_series   = 'generate_series(0, days_count)';
        $SQL_mask_check        = "NEW.mask & (2^date_part(NEW.mask_type, $SQL_instance_start))::int != 0";

        // We run after insert or update for the foreign key event_id
        $this->createFunction('fn_acornassociated_calendar_generate_event_instances', 
            [
                'new_event_part record', 
                'old_event_part record'  // Nullable
            ], 
            'record',
            [
                'days_before interval',
                'days_after interval',
                'days_count int',
                'today date := now()',
                'date_start date',
            ],
            <<<SQL
                -- This function creates the individual event instances on specific dates
                -- from event definitions, that can have preiodic repetition
                -- For example, an single event definition that repeats weekly for 2 months
                -- may have 9 individual event instances on specific dates
                -- Declares are configurable from WinterCMS settings

                -- Check if anything repeaty has changed (not locked_by_user_id)
                if     old_event_part is null
                    or new_event_part.start  is distinct from old_event_part.start
                    or new_event_part."end"  is distinct from old_event_part."end"
                    or new_event_part.until  is distinct from old_event_part.until
                    or new_event_part.mask   is distinct from old_event_part.mask
                    or new_event_part.repeat is distinct from old_event_part.repeat
                    or new_event_part.mask_type is distinct from old_event_part.mask_type
                    or new_event_part.repeat_frequency     is distinct from old_event_part.repeat_frequency
                    or new_event_part.parent_event_part_id is distinct from old_event_part.parent_event_part_id
                    or new_event_part.instances_deleted    is distinct from old_event_part.instances_deleted
                then
                    -- Settings
                    select coalesce((select substring("value" from '"days_before":"([^"]+)"')
                        from system_settings where item = 'acornassociated_calendar_settings'), '$window_default_past')
                        into days_before;
                    select coalesce((select substring("value" from '"days_after":"([^"]+)"')
                        from system_settings where item = 'acornassociated_calendar_settings'), '$window_default_future')
                        into days_after;
                    select extract('epoch' from days_before + days_after)/3600/24.0
                        into days_count;
                    select today - days_before
                        into date_start;

                    -- For updates (id cannot change)
                    delete from acornassociated_calendar_instances where event_part_id = new_event_part.id;

                    -- For inserts
                    insert into acornassociated_calendar_instances("date", event_part_id, instance_start, instance_end, instance_num)
                    select date_start + interval '1' day * gs as "date", ev.*
                    from $SQL_generate_series as gs
                    inner join (
                        -- single event
                        select new_event_part.id as event_part_id,
                            new_event_part."start" as "instance_start",
                            new_event_part."end"   as "instance_end",
                            0 as instance_num
                        where new_event_part.repeat is null
                    union all
                        -- repetition, no parent container
                        select new_event_part.id as event_part_id,
                            $SQL_instance_start as "instance_start",
                            $SQL_instance_end   as "instance_end",
                            gs.gs as instance_num
                        from $SQL_generate_series as gs
                        where not new_event_part.repeat is null and new_event_part.parent_event_part_id is null
                        and (new_event_part.instances_deleted is null or not gs.gs = any(new_event_part.instances_deleted))
                        and (new_event_part.until is null or $SQL_instance_start < new_event_part.until)
                        and (new_event_part.mask = 0 or $SQL_mask_check)
                    union all
                        -- repetition with parent_event_part_id container calendar events
                        select new_event_part.id as event_part_id,
                            $SQL_instance_start as "instance_start",
                            $SQL_instance_end   as "instance_end",
                            gs.gs as instance_num
                        from $SQL_generate_series as gs
                        inner join acornassociated_calendar_instances pcc on new_event_part.parent_event_part_id = pcc.event_part_id
                            and (pcc.date, pcc.date + 1)
                            overlaps ($SQL_instance_start, $SQL_instance_end)
                        where not new_event_part.repeat is null
                        and (new_event_part.instances_deleted is null or not gs.gs = any(new_event_part.instances_deleted))
                        and (new_event_part.until is null or $SQL_instance_start < new_event_part.until)
                        and (new_event_part.mask = 0 or $SQL_mask_check)
                    ) ev
                    on  (date_start + interval '1' day * gs, date_start + interval '1' day * (gs+1))
                    overlaps (ev.instance_start, ev.instance_end);

                    -- Recursively update child event parts
                    -- TODO: This could infinetly cycle
                    update acornassociated_calendar_event_parts set id = id
                        where parent_event_part_id = new_event_part.id
                        and not id = new_event_part.id;
                end if;

                return new_event_part;
SQL
        );

        // These functions were split out
        // because we imagined other processes creating instances from virtual records
        // but that was abandoned because the instance.event_part_id is necessary
        // for the name, type, status display information
        // so now, we always create and manage a full event & event_part for all instances
        $this->createFunctionAndTrigger('acornassociated_calendar_events_generate_event_instances', 
            'AFTER', 
            'INSERT OR UPDATE', 
            'public.acornassociated_calendar_event_parts', 
            TRUE, 
            [],
        <<<SQL
            return public.fn_acornassociated_calendar_generate_event_instances(NEW, OLD);
SQL
        );  
    }

    public function down()
    {
        Schema::dropIfExists('tr_acornassociated_calendar_events_generate_event_instances');
        Schema::dropIfExists('fn_acornassociated_calendar_events_generate_event_instances');
        Schema::dropIfExists('fn_acornassociated_calendar_generate_event_instances');
    }
}
