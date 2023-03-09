<div class="layout-row calendar-week">
    <?php for ($d = 0; $d < 7; $d++) print($this->makePartial('day', $week[$d])); ?>
</div>
