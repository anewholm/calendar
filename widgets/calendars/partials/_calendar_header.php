<div class="layout-row calendar-header">
    <?php
    $dayNames = trans('acornassociated.calendar::lang.models.calendar.weekdaysShort');
    for ($d = 0; $d < 7; $d++) {
        $date    = &$week[$d]['date'];
        $w       = date_format($date, 'w');
        $dayname = e($dayNames[$w]);
        print("<div class='layout-cell'>$dayname</div>");
    }
    ?>
</div>
