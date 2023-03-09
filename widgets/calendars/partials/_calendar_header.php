<div class="layout-row calendar-header">
    <?php
    for ($d = 0; $d < 7; $d++) {
        $date    = &$week[$d]['date'];
        $dayname = e(trans(date_format($date, 'D')));
        print("<div class='layout-cell'>$dayname</div>");
    }
    ?>
</div>
