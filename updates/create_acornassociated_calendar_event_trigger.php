<?php namespace Acorn\Calendar\Updates;

use Schema;
use \Acorn\Migration as AcornMigration;
use DB;

class CreateAcornCalendarEventTrigger extends AcornMigration
{
    public function up()
    {
        // TODO: Is this a VOLATILE or LEAKPROOF trigger?
        // TODO: Make this as a view and insert from it, so that out-of-bounds queries are possible
        // TODO: generate series size should be different depending on the repetition period
        //   because currently, a day period will repeat for 2 years, but a year period will repeat for 365*2*1 years

        // Insert & Update triggers
        $window_default_past   = '1 year';
        $window_default_future = '2 years';
        $SQL_day_count       = 'NEW.repeat_frequency * NEW."repeat" * gs.gs';
        $SQL_instance_start  = 'NEW."start" + ' . $SQL_day_count;
        $SQL_instance_end    = 'NEW."end" + '   . $SQL_day_count;
        $SQL_generate_series = 'generate_series(0, days_count)';
        $SQL_mask_check      = "NEW.mask & (2^date_part(NEW.mask_type, $SQL_instance_start))::int != 0";

        // We run after insert or update for the foreign key event_id
        // string $baseName, string $stage, string $action, string $table, bool $forEachRow, array $declares, string $body, ?string $language = 'plpgsql'
        $this->createFunctionAndTrigger('acorn_calendar_event_trigger_insert_function', 'AFTER', 'INSERT OR UPDATE', 'public.acorn_calendar_event_part', TRUE,
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

                -- Check if anything repeaty has changed (not locked_by)
                if     OLD is null
                    or NEW.start  is distinct from OLD.start
                    or NEW."end"  is distinct from OLD."end"
                    or NEW.until  is distinct from OLD.until
                    or NEW.mask   is distinct from OLD.mask
                    or NEW.repeat is distinct from OLD.repeat
                    or NEW.mask_type is distinct from OLD.mask_type
                    or NEW.repeat_frequency     is distinct from OLD.repeat_frequency
                    or NEW.parent_event_part_id is distinct from OLD.parent_event_part_id
                    or NEW.instances_deleted    is distinct from OLD.instances_deleted
                then
                    -- Settings
                    select coalesce((select substring("value" from '"days_before":"([^"]+)"')
                        from system_settings where item = 'acorn_calendar_settings'), '$window_default_past')
                        into days_before;
                    select coalesce((select substring("value" from '"days_after":"([^"]+)"')
                        from system_settings where item = 'acorn_calendar_settings'), '$window_default_future')
                        into days_after;
                    select extract('epoch' from days_before + days_after)/3600/24.0
                        into days_count;
                    select today - days_before
                        into date_start;

                    -- For updates (id cannot change)
                    delete from acorn_calendar_instance where event_part_id = NEW.id;

                    -- For inserts
                    insert into acorn_calendar_instance("date", event_part_id, instance_start, instance_end, instance_id)
                    select date_start + interval '1' day * gs as "date", ev.*
                    from $SQL_generate_series as gs
                    inner join (
                        -- single event
                        select NEW.id as event_part_id,
                            NEW."start" as "instance_start",
                            NEW."end"   as "instance_end",
                            0 as instance_id
                        where NEW.repeat is null
                    union all
                        -- repetition, no parent container
                        select NEW.id as event_part_id,
                            $SQL_instance_start as "instance_start",
                            $SQL_instance_end   as "instance_end",
                            gs.gs as instance_id
                        from $SQL_generate_series as gs
                        where not NEW.repeat is null and NEW.parent_event_part_id is null
                        and (NEW.instances_deleted is null or not gs.gs = any(NEW.instances_deleted))
                        and (NEW.until is null or $SQL_instance_start < NEW.until)
                        and (NEW.mask = 0 or $SQL_mask_check)
                    union all
                        -- repetition with parent_event_part_id container calendar events
                        select NEW.id as event_part_id,
                            $SQL_instance_start as "instance_start",
                            $SQL_instance_end   as "instance_end",
                            gs.gs as instance_id
                        from $SQL_generate_series as gs
                        inner join acorn_calendar_instance pcc on NEW.parent_event_part_id = pcc.event_part_id
                            and (pcc.date, pcc.date + 1)
                            overlaps ($SQL_instance_start, $SQL_instance_end)
                        where not NEW.repeat is null
                        and (NEW.instances_deleted is null or not gs.gs = any(NEW.instances_deleted))
                        and (NEW.until is null or $SQL_instance_start < NEW.until)
                        and (NEW.mask = 0 or $SQL_mask_check)
                    ) ev
                    on  (date_start + interval '1' day * gs, date_start + interval '1' day * (gs+1))
                    overlaps (ev.instance_start, ev.instance_end);

                    -- Recursively update child event parts
                    -- TODO: This could infinetly cycle
                    update acorn_calendar_event_part set id = id
                        where parent_event_part_id = NEW.id
                        and not id = NEW.id;
                end if;

                return NEW;
SQL
        );
    }

    public function down()
    {
        Schema::dropIfExists('acorn_calendar_event_trigger_insert');
        Schema::dropIfExists('acorn_calendar_event_trigger_insert_function');
    }
}
