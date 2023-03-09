<button type='submit' class='btn btn-primary wn-icon-send'
    data-load-indicator='Creating'
    data-request='onCreateEvent'
    data-request-form='.control-popup form'
    data-request-update="calendar: '#Calendars'"
    data-dismiss='popup'
>
    <?= e(trans('Create')) ?>
</button>
<button type='button' class='btn btn-default' data-dismiss='popup'><?= e(trans('Close')) ?></button>
