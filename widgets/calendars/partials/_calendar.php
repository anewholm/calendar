<div id="Calendars" class="calendar-container">
    <div class="layout calendar">
        <?php if (count($weeks)) print($this->makePartial('calendar_header', ['week' => $weeks[0]])) ?>
        <?php foreach ($weeks as $w => $week) print($this->makePartial('week', ['week' => $week])); ?>
    </div>
</div>
