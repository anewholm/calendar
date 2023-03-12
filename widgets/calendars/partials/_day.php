<div class="layout-cell calendar-day <?= implode(' ', $classes) ?>" style="<?= implode(';', $styles) ?>">
    <div class="layout-row calendar-day-header">
        <?php if ($range == 'in'): ?>
            <a href="#" class="event-add"
                data-handler="onOpenDay"
                data-request-data="path:'<?= $date->format('Y-m-d') ?>', objectType:'Event'"
                data-control="popup"
                title="Add new event"
            >+</a>
        <?php endif ?>
        <span class="title"><?= $title ?></span>
        <?= $date->format($format) ?>
    </div>
    <ul>
        <?php $e = 0; while (isset($events[$e])) print($this->makePartial('instance', [
            'instance'  => $events[$e],
            'eventPart' => $events[$e++]->eventPart,
            'tag'       => 'li'
        ])); ?>
    </ul>
</div>
