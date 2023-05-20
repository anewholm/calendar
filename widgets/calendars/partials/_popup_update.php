<div class='modal-header calendar editing-eventpart-id-<?= $instance->eventPart->id ?>'>
    <button type='button' class='close'
        data-dismiss='popup'
        data-request='onClose'
        data-request-update="calendar: '#Calendars'"
        data-request-form='.control-popup form'
    >&times;</button>
    <h4 class='modal-title'><?= $name ?></h4>
    <?= $this->makePartial('form_toolbar') ?>
    <?= implode('', $hints) ?>
</div>

<div class='modal-body calendar editing-eventpart-id-<?= $instance->eventPart->id ?>'>
    <?= $this->makePartial('form_event', [
        'form'          => $form,
        'instanceID'    => $instance->id,
        'templateType'  => $templateType,
        'templateTheme' => $templateTheme,
        ])
    ?>
</div>

<div class='modal-footer calendar editing-eventpart-id-<?= $instance->eventPart->id ?>'>
    <?= $this->makePartial('popup_update_actions', [
        'instance'  => $instance,
        'eventPart' => $instance->eventPart,
        ])
    ?>
</div>

