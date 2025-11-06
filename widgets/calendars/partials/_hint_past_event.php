<div class="layout-row min-size">
    <div class="callout callout-warning past-event-warning">
        <div class="header">
            <button type="button" class="close">×</button>
            <i class="icon-warning"></i>
            <h3><?= e(trans('acorn.calendar::lang.models.event.past_event')) ?></h3>
            <p><?= e(trans($canPast
                ? 'acorn.calendar::lang.models.event.can_change_past'
                : 'acorn.calendar::lang.models.event.past_premissions_needed'
            )) ?></p>
        </div>
    </div>
</div>
