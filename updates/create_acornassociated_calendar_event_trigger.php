<?php namespace AcornAssociated\Calendar\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;
use DB;

class CreateAcornassociatedCalendarEventTrigger extends Migration
{
    public function up()
    {
        // TODO: Update trigger: Support additional updates (as opposed to replacement)?
        // TODO: Is this a VOLATILE or LEAKPROOF trigger?
        // TODO: Make this as a view and insert from it, so that out-of-bounds queries are possible
        // TODO: generate series size should be different depending on the repetition period
        //   because currently, a day period will repeat for 2 years, but a year period will repeat for 365*2*1 years

        // Insert & Update triggers
        $BODY = '$BODY$';

        $window_default_past   = '1 year';
        $window_default_future = '2 years';
        $SQL_day_count       = 'NEW.repeat_frequency * NEW."repeat" * gs.gs';
        $SQL_instance_start  = 'NEW."start" + ' . $SQL_day_count;
        $SQL_instance_end    = 'NEW."end" + '   . $SQL_day_count;
        $SQL_generate_series = 'generate_series(0, days_count)';
        $SQL_mask_check      = "NEW.mask & (2^date_part(NEW.mask_type, $SQL_instance_start))::int != 0";

        DB::unprepared(<<<SQL
            CREATE OR REPLACE FUNCTION public.acornassociated_calendar_event_trigger_insert_function()
                RETURNS trigger
                LANGUAGE 'plpgsql'
                COST 100
                VOLATILE NOT LEAKPROOF
            AS $BODY
            declare
                -- Configurable from WinterCMS settings
                days_before interval;
                days_after interval;
                days_count int;
                today date := now();
                date_start date;
            begin
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
                delete from acornassociated_calendar_instance where event_id = NEW.id;

                -- For inserts
                insert into acornassociated_calendar_instance("date", event_id, part, instance_start, instance_end, instance_id)
                select date_start + interval '1' day * gs as "date", ev.*
                from $SQL_generate_series as gs
                inner join (
                    -- single event
                    select NEW.id as event_id,
						NEW.part as part,
                        NEW."start" as "instance_start",
                        NEW."end"   as "instance_end",
                        0 as instance_id
                    where NEW.repeat is null
                union all
                    -- repetition, no parent container
                    select NEW.id as event_id,
						NEW.part as part,
                        $SQL_instance_start as "instance_start",
                        $SQL_instance_end   as "instance_end",
                        gs.gs as instance_id
                    from $SQL_generate_series as gs
                    where not NEW.repeat is null and NEW.parent_id is null
                    and (NEW.until is null or $SQL_instance_start < NEW.until)
                    and (NEW.mask = 0 or $SQL_mask_check)
                union all
                    -- repetition with parent_id container calendar events
                    select NEW.id as event_id,
						NEW.part as part,
                        $SQL_instance_start as "instance_start",
                        $SQL_instance_end   as "instance_end",
                        gs.gs as instance_id
                    from $SQL_generate_series as gs
                    inner join acornassociated_calendar_instance pcc on NEW.parent_id = pcc.event_id
                        and (pcc.date, pcc.date + 1)
                        overlaps ($SQL_instance_start, $SQL_instance_end)
                    where not NEW.repeat is null
                    and (NEW.until is null or $SQL_instance_start < NEW.until)
                    and (NEW.mask = 0 or $SQL_mask_check)
                ) ev
                on  (date_start + interval '1' day * gs, date_start + interval '1' day * (gs+1))
                overlaps (ev.instance_start, ev.instance_end);
                return NEW;
            end;
            $BODY;
SQL
        );

        // We run afterwards for the foreign key event_id
        DB::unprepared(<<<SQL
            CREATE TRIGGER acornassociated_calendar_event_trigger_insert
                AFTER INSERT OR UPDATE
                ON public.acornassociated_calendar_event
                FOR EACH ROW
                EXECUTE FUNCTION public.acornassociated_calendar_event_trigger_insert_function();
SQL
        );
    }

    public function down()
    {
        Schema::dropIfExists('acornassociated_calendar_event_trigger_insert');
        Schema::dropIfExists('acornassociated_calendar_event_trigger_insert_function');
    }
}
