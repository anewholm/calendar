<div class="layout-cell calendar-day <?= implode(' ', $classes) ?>" style="<?= implode(';', $styles) ?>">
    <div class="layout-row calendar-day-header">
        <?php if ($range == 'in'): ?>
            <a href="#" class="event-add"
                data-handler="onOpenDay"
                data-request-data="path:'<?= $date->format('Y-m-d') ?>', objectType:'Event'"
                data-control="popup"
                title="<?= e(trans('acornassociated.calendar::lang.models.calendar.day_add_event')) ?>"
            ><?= e(trans('acornassociated.calendar::lang.models.calendar.day_add_event')) ?></a>
        <?php endif ?>
        <span class="title"><?= $title ?></span>
        <?= $date->format($format) ?>
    </div>
    <ul class="calendar-event-list drop-target" data-request-drop-id="<?= $date->format('Y-m-d') ?>">
        <?php $e = 0; while (isset($events[$e])) print($this->makePartial('instance', [
            'instance'  => $events[$e],
            'eventPart' => $events[$e++]->eventPart,
            'tag'       => 'li'
        ])); ?>
    </ul>
</div>
