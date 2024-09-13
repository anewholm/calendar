<?php

use Carbon\Carbon;
use Acorn\Calendar\Models\Instance;
use Acorn\Calendar\Widgets\CalendarCell;

// This partial is designed to be referenced from *other* plugins
// e.g. $this: is the foreign [Messaging] controllers/Conversations
// TODO: Re-do this by instantiating a Acorn\Calendar\Widgets\Calendars
// for the defineCalendarCells() => CalendarCells to show which columns etc.
// and then $calendars->render()
$widgetDir = str_replace(app()->basePath(), '', dirname(dirname(__FILE__)));
$this->addCss("$widgetDir/assets/css/acorn.calendar.css");
$this->addViewPath("~$widgetDir/partials");
$this->vars['columns'] = array();

// $field->options[x]
$date      = new Carbon();
$enddate   = $date->clone()->add(7, 'days');

$columns = array(
    new CalendarCell('name'),
    new CalendarCell('instance_start'),
    new CalendarCell('instance_end'),
    new CalendarCell('users'),
);

print('<ul class="layout-row">');
$instances = Instance::where('instance_start', '>=', $date)
    ->where('instance_start', '<=', $enddate)
    ->get();

if (count($instances)) {
    foreach ($instances as $instance) {
        $id        = $instance->id;
        $eventPart = &$instance->eventPart;
        $for       = "instance-$id";
        $start     = $instance->instance_start->format('H:i');
        $checked   = ($value && in_array($id, $value) ? "checked='1'" : '');
        print("<li><input id='$for' name='instances[]' $checked value='$instance->id' type='checkbox'></input>&nbsp;<label for='$for'>$start $eventPart->name</label></li>");
    }

    /* TODO: Use day partial
    while ($date < $enddate) {
        print($this->makePartial('day', [
            'date'    => $date,
            'title'   => $date->format('d'),
            'format'  => 'd',
            'classes' => array(),
            'styles'  => array(),
            'range'   => 'in',
            'events'  => $instances,
            'columns' => $columns,
        ]));
        $date->add(1, 'day');
    }
        */
    print('</ul>');
} else {
    print($this->makePartial('hint_calendar_selector_empty'));
}
