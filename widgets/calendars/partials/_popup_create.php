<div class='modal-header calendar'>
    <button type='button' class='close' data-dismiss='popup'>&times;</button>
    <h4 class='modal-title'><?= $name ?></h4>
    <?= $this->makePartial('form_toolbar') ?>
    <?= implode('', $hints) ?>
</div>

<div class='modal-body calendar'>
    <?= $this->makePartial('form_event', [
        'form'          => $form,
        'templateType'  => $templateType,
        'templateTheme' => $templateTheme,
        'templateMtime' => $templateMtime,
    ]);
    ?>
</div>

<div class='modal-footer calendar'>
    <?= $this->makePartial('popup_create_actions') ?>
</div>
