<div id="Calendars" class="calendar-container" websocket-listen="calendar" websocket-oncalendar-update="'calendar': '#Calendars-instance'">
    <div class="layout calendar sortable" data-request-drop="onChangeDate">
        <?php if (count($weeks)) print($this->makePartial('calendar_header', ['week' => $weeks[0]])) ?>
        <?php foreach ($weeks as $w => $week) print($this->makePartial('week', ['week' => $week])); ?>
    </div>
</div>
