<?php

use Carbon\Carbon;
use AcornAssociated\Calendar\Models\Instance;
use AcornAssociated\Calendar\Widgets\CalendarCell;

// This partial is designed to be referenced from *other* plugins
// e.g. $this: is the foreign [Messaging] controllers/Conversations
// TODO: Re-do this by instantiating a AcornAssociated\Calendar\Widgets\Calendars
// for the defineCalendarCells() => CalendarCells to show which columns etc.
// and then $calendars->render()
$widgetDir = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname(dirname(__FILE__)));
$this->addCss("$widgetDir/assets/css/acornassociated.calendar.css");
$this->addViewPath("~/$widgetDir/partials");
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

print('<div class="layout-row">');
while ($date < $enddate) {
    $instances = Instance::where('instance_start', '>=', $date)->get();
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
print('</div>');
