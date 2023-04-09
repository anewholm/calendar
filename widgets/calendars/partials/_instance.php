<<?= $tag ?> class="layout-row" data-request-id="<?= $instance->id ?>">
    <a class="layout-relative <?= implode(' ', $eventPart->typeClasses()) ?> <?= implode(' ', $instance->typeClasses()) ?>" style="<?= implode(';', $eventPart->typeStyle()) ?>" href="#"
        data-handler="onOpenEvent"
        data-request-data="path:<?= $instance->id ?>"
        data-control="popup"
        title="<?= e($instance->bubbleHelp()) ?>"
    >
        <?php foreach ($columns as $column) $column->render($instance); ?>
        <input type="hidden" data-inspector-class value="<?= $eventPart->id ?>">
    </a>
</<?= $tag ?>>

