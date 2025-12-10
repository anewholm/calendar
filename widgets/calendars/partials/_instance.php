<?php
$externalUrl  = $eventPart->event->external_url;
$class        = implode(' ', $eventPart->typeClasses()) . implode(' ', $instance->typeClasses());
$style        = implode(';', $eventPart->typeStyle());
$messageCount = $instance->messageCount();

print("<$tag class='layout-row' data-request-id='$instance->id'>");
if ($externalUrl) {
    print("<a class='layout-relative $class' style='$style' href='$externalUrl'>");
} else { 
    // Using @data-request-url with /update/ to trigger TranslationBehaviour update mode
    $updateUrl  = "?mode=update";
    $bubbleHelp = e($instance->bubbleHelp());
    $dataRequestData = array(
        'path' => $instance->id
    );
    $dataRequestDataString = e(substr(json_encode($dataRequestData), 1, -1));
    print(<<<HTML
        <a class="layout-relative $class" style="$style" href="#"
            data-request-url="$updateUrl"
            data-handler="onOpenEvent"
            data-request-data="$dataRequestDataString"
            data-control="popup"
            title="$bubbleHelp"
        >
HTML
    );
}

foreach ($columns as $column) $column->render($instance);

print(<<<HTML
        <span class="message-count">$messageCount</span>
        <input type="hidden" data-inspector-class value="$eventPart->id">
    </a>
HTML
);
print("</$tag>");

