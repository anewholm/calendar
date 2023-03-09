<<?= $tag ?> class="layout-row calendar-event">
    <a class="layout-relative <?= implode(' ', $event->typeClasses()) ?> <?= implode(' ', $instance->typeClasses()) ?>" style="<?= implode(';', $event->typeStyle()) ?>" href="#"
        data-handler="onOpenEvent"
        data-request-data="path:<?= $instance->id ?>"
        data-control="popup"
        title="<?= e($instance->bubbleHelp()) ?>"
    >
        <!-- TODO: Apply $this->getVisibleColumns() columns logic -->
        <span class="start"><?= $instance->instance_start->format('H:i') ?></span>
        <span class="name" title="<?= e($event->description) ?>"><?= e($event->name) ?></span>
        <span class="repeat"><?= e($event->repeatWithFrequency()) ?></span>
        <span class="end"><?= $instance->instance_end->format('H:i') ?></span>
        <span class="location"><?= e($event->location ? $event->location->name : '') ?></span>
        <span class="attendees"><?= e($event->attendees()) ?></span>
        <input type="hidden" data-inspector-class value="<?= $event->id ?>">
    </a>
</<?= $tag ?>>

