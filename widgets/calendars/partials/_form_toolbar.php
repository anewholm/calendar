<div class="layout-row min-size manage-toolbar">
<span class="manage"><?= e(strtolower(trans('acorn.calendar::lang.models.calendar.manage'))) ?></span>:
    <a href="/backend/acorn/calendar/eventtypes"><?= e(strtolower(trans('acorn.calendar::lang.models.eventtype.label_plural'))) ?></a> |
    <a href="/backend/acorn/calendar/eventstatuses"><?= e(strtolower(trans('acorn.calendar::lang.models.eventstatus.label_plural'))) ?></a> |
    <a href="/backend/acorn/location/locations"><?= e(strtolower(trans('acorn.calendar::lang.models.general.locations'))) ?></a> |
    <a href="/backend/acorn/user/users"><?= e(strtolower(trans('acorn.calendar::lang.models.eventpart.groups'))) ?> &amp; <?= e(strtolower(trans('acorn.calendar::lang.models.eventpart.people'))) ?></a> |
    <a href="/backend/acorn/calendar/calendars"><?= e(strtolower(trans('acorn.calendar::lang.models.calendar.label_plural'))) ?></a> |
    <a href="/backend/system/settings/update/acorn/calendar/settings"><?= e(strtolower(trans('acorn.calendar::lang.models.settings.label_short_plural'))) ?></a>
</div>
