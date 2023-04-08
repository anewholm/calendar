<div class="layout-row min-size">
    <div class="callout callout-warning">
        <div class="header">
            <button type="button" class="close">×</button>
            <i class="icon-warning"></i>
            <h3><?= e(trans('This is a past event!')) ?></h3>
            <p>
                <?= e(trans($canPast
                    ? 'However, you have permissions to change the past'
                    : 'This event is in the past and needs special permissions to be edited.'
                )) ?>
            </p>
        </div>
    </div>
</div>
