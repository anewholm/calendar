<?php
$external_url = $eventPart->event->external_url;
$class = implode(' ', $eventPart->typeClasses()) . implode(' ', $instance->typeClasses());
$style = implode(';', $eventPart->typeStyle());
?>

<<?= $tag ?> class="layout-row" data-request-id="<?= $instance->id ?>">
    <?php if ($external_url) { ?>
        <a class="layout-relative <?= $class ?>" style="<?= $style ?>" href="<?= $external_url ?>">
    <?php } else { ?>
        <a class="layout-relative <?= $class ?>" style="<?= $style ?>" href="#"
            data-handler="onOpenEvent"
            data-request-data="path:'<?= $instance->id ?>'"
            data-control="popup"
            title="<?= e($instance->bubbleHelp()) ?>"
        >
    <?php } ?>
        <?php foreach ($columns as $column) $column->render($instance); ?>
        <span class="message-count"><?= $instance->messageCount() ?></span>
        <input type="hidden" data-inspector-class value="<?= $eventPart->id ?>">
    </a>
</<?= $tag ?>>

