<<?= $tag ?> class="layout-row calendar-event">
    <a class="layout-relative <?= implode(' ', $eventPart->typeClasses()) ?> <?= implode(' ', $instance->typeClasses()) ?>" style="<?= implode(';', $eventPart->typeStyle()) ?>" href="#"
        data-handler="onOpenEvent"
        data-request-data="path:<?= $instance->id ?>"
        data-control="popup"
        title="<?= e($instance->bubbleHelp()) ?>"
    >
        <!-- TODO: Apply $this->getVisibleColumns() columns logic -->
        <span class="start"><?= $instance->instance_start->format('H:i') ?></span>
        <span class="name" title="<?= e($eventPart->description) ?>"><?= e($eventPart->name) ?></span>
        <span class="repeat"><?= e($eventPart->repeatWithFrequency()) ?></span>
        <span class="end"><?= $instance->instance_end->format('H:i') ?></span>
        <span class="location"><?= e($eventPart->location ? $eventPart->location->name : '') ?></span>
        <span class="attendees"><?= e($eventPart->attendees()) ?></span>
        <input type="hidden" data-inspector-class value="<?= $eventPart->id ?>">
    </a>
</<?= $tag ?>>

