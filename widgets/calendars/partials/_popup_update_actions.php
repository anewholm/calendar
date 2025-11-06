<?php
$isFirstInstance     = ($instance->instance_num == 0);
$isLastInstance      = $instance->isLast();
$eventRepeats        = (bool) $eventPart->repeat;
$instanceStart       = $instance->instance_start->format('M, d');
$hasDeletedInstances = (bool) $eventPart->instances_deleted;
?>

<?php if ($eventRepeats): ?>
    <?php if ($isFirstInstance): ?>
        <?php if ($eventPart->canWrite()): ?>
            <div class='btn-group dropdown dropdown-fixed'>
                <button type='submit' class='btn btn-primary wn-icon-send'
                    data-load-indicator='<?= e(trans('backend::lang.form.saving')) ?>'
                    data-request='onUpdateEventWholeSeries'
                    data-request-update="calendar: '#Calendars'"
                    data-request-form='.control-popup form'
                    data-dismiss='popup'
                ><?= e(trans('backend::lang.form.save')) ?></button>
                <button
                    type="button"
                    class="btn btn-default dropdown-toggle"
                    data-toggle="dropdown">
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" data-dropdown-title="Update">
                    <li class="dropdown-container">
                        <ul>
                            <li class="dropdown-title">Update</li>
                            <li class="first-item"><a href="javascript:;" class="oc-icon-user-plus"
                                data-load-indicator='<?= e(trans('backend::lang.form.saving')) ?>'
                                data-request='onUpdateEventInstanceOnly'
                                data-request-update="calendar: '#Calendars'"
                                data-request-form='.control-popup form'
                                data-dismiss='popup'
                                ><?= e(trans('acorn.calendar::lang.models.eventpart.update_only')) ?>&nbsp;<span class="from-date"><?= $instanceStart ?></span></a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        <?php endif ?>

        <?php if ($eventPart->canDelete()): ?>
            <div class='btn-group dropdown dropdown-fixed'>
                <button type='submit' class='btn btn-danger wn-icon-send'
                    data-load-indicator='<?= e(trans('backend::lang.form.deleting')) ?>'
                    data-request='onDeleteEventInstanceOnly'
                    data-request-update="calendar: '#Calendars'"
                    data-confirm="<?= e(trans('acorn.calendar::lang.models.eventpart.delete_only')) ?> <?= $instanceStart ?>?"
                    data-request-form='.control-popup form'
                    data-dismiss='popup'
                ><?= e(trans('acorn.calendar::lang.models.eventpart.delete_only')) ?> <span class="from-date"><?= $instanceStart ?></span></button>
                <button
                    type="button"
                    class="btn btn-default dropdown-toggle"
                    data-toggle="dropdown">
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" data-dropdown-title="Delete">
                    <li class="dropdown-container">
                        <ul>
                            <li class="dropdown-title">Delete</li>
                            <li><a href="javascript:;" class="oc-icon-trash-o"
                                data-load-indicator='<?= e(trans('backend::lang.form.deleting')) ?>'
                                data-request='onDeleteEventWholeSeries'
                                data-request-update="calendar: '#Calendars'"
                                data-confirm="<?= e(trans('acorn.calendar::lang.models.eventpart.delete_whole_series_question')) ?>"
                                data-request-form='.control-popup form'
                                data-dismiss='popup'
                                ><?= e(trans('acorn.calendar::lang.models.eventpart.delete_whole_series')) ?></a>
                            </li>
                            <?php if ($hasDeletedInstances): ?>
                                <li><a href="javascript:;" class="oc-icon-user-plus"
                                    data-load-indicator='<?= e(trans('acorn.calendar::lang.models.eventpart.undeleting')) ?>'
                                    data-request='onReInstateDeletedInstances'
                                    data-request-update="calendar: '#Calendars'"
                                    data-confirm="<?= e(trans('acorn.calendar::lang.models.eventpart.undelete_all_instances_question')) ?>"
                                    data-request-form='.control-popup form'
                                    data-dismiss='popup'
                                    ><?= e(trans('acorn.calendar::lang.models.eventpart.undelete_all_instances')) ?></a>
                                </li>
                            <?php endif ?>
                        </ul>
                    </li>
                </ul>
            </div>
        <?php endif ?>
    <?php elseif ($isLastInstance): ?>
        <?php if ($eventPart->canWrite()): ?>
            <div class='btn-group dropdown dropdown-fixed'>
                <button type='submit' class='btn btn-primary wn-icon-send'
                    data-load-indicator='<?= e(trans('backend::lang.form.saving')) ?>'
                    data-request='onUpdateEventInstanceOnly'
                    data-request-update="calendar: '#Calendars'"
                    data-request-form='.control-popup form'
                    data-dismiss='popup'
                ><?= e(trans('acorn.calendar::lang.models.eventpart.update_only')) ?> <span class="from-date"><?= $instanceStart ?></span></button>
                <button
                    type="button"
                    class="btn btn-default dropdown-toggle"
                    data-toggle="dropdown">
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" data-dropdown-title="Update">
                    <li class="dropdown-container">
                        <ul>
                            <li class="dropdown-title">Update</li>
                            <li class="first-item"><a href="javascript:;" class="oc-icon-user-plus"
                                data-load-indicator='<?= e(trans('backend::lang.form.saving')) ?>'
                                data-request='onUpdateEventWholeSeries'
                                data-request-update="calendar: '#Calendars'"
                                data-request-form='.control-popup form'
                                data-dismiss='popup'
                                ><?= e(trans('acorn.calendar::lang.models.eventpart.update_whole_series')) ?></a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        <?php endif ?>

        <?php if ($eventPart->canDelete()): ?>
            <div class='btn-group dropdown dropdown-fixed'>
                <button type='submit' class='btn btn-danger wn-icon-send'
                    data-load-indicator='<?= e(trans('backend::lang.form.deleting')) ?>'
                    data-request='onDeleteEventInstanceOnly'
                    data-request-update="calendar: '#Calendars'"
                    data-confirm="<?= e(trans('acorn.calendar::lang.models.eventpart.delete_only')) ?> <?= $instanceStart ?>?"
                    data-request-form='.control-popup form'
                    data-dismiss='popup'
                ><?= e(trans('acorn.calendar::lang.models.eventpart.delete_only')) ?> <span class="from-date"><?= $instanceStart ?></span></button>
                <button
                    type="button"
                    class="btn btn-default dropdown-toggle"
                    data-toggle="dropdown">
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" data-dropdown-title="Delete">
                    <li class="dropdown-container">
                        <ul>
                            <li class="dropdown-title">Delete</li>
                            <li><a href="javascript:;" class="oc-icon-trash-o"
                                data-load-indicator='<?= e(trans('backend::lang.form.deleting')) ?>'
                                data-request='onDeleteEventWholeSeries'
                                data-request-update="calendar: '#Calendars'"
                                data-confirm="<?= e(trans('acorn.calendar::lang.models.eventpart.delete_whole_series_question')) ?>"
                                data-request-form='.control-popup form'
                                data-dismiss='popup'
                                ><?= e(trans('acorn.calendar::lang.models.eventpart.delete_whole_series')) ?></a>
                            </li>
                            <?php if ($hasDeletedInstances): ?>
                                <li><a href="javascript:;" class="oc-icon-user-plus"
                                    data-load-indicator='<?= e(trans('acorn.calendar::lang.models.eventpart.undeleting')) ?>'
                                    data-request='onReInstateDeletedInstances'
                                    data-request-update="calendar: '#Calendars'"
                                    data-confirm="<?= e(trans('acorn.calendar::lang.models.eventpart.undelete_all_instances_question')) ?>"
                                    data-request-form='.control-popup form'
                                    data-dismiss='popup'
                                    ><?= e(trans('acorn.calendar::lang.models.eventpart.undelete_all_instances')) ?></a>
                                </li>
                            <?php endif ?>
                        </ul>
                    </li>
                </ul>
            </div>
        <?php endif ?>
    <?php else: ?>
        <!-- Full repeat, mid-instances
            TODO: These actions need to be affected by event/eventpart/instance->canPast()
            depending on their effect
        -->
        <?php if ($eventPart->canWrite()): ?>
            <div class='btn-group dropdown dropdown-fixed'>
                <button type='submit' class='btn btn-primary wn-icon-send'
                    data-load-indicator='<?= e(trans('backend::lang.form.saving')) ?>'
                    data-request='onUpdateEventFromInstance'
                    data-request-update="calendar: '#Calendars'"
                    data-request-form='.control-popup form'
                    data-dismiss='popup'
                ><?= e(trans('acorn.calendar::lang.models.eventpart.update_from')) ?> <span class="from-date"><?= $instanceStart ?></span></button>
                <button
                    type="button"
                    class="btn btn-default dropdown-toggle"
                    data-toggle="dropdown">
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" data-dropdown-title="Update">
                    <li class="dropdown-container">
                        <ul>
                            <li class="dropdown-title">Update</li>
                            <li class="first-item"><a href="javascript:;" class="oc-icon-user-plus"
                                data-load-indicator='<?= e(trans('backend::lang.form.saving')) ?>'
                                data-request='onUpdateEventInstanceOnly'
                                data-request-update="calendar: '#Calendars'"
                                data-request-form='.control-popup form'
                                data-dismiss='popup'
                                ><?= e(trans('acorn.calendar::lang.models.eventpart.update_only')) ?> <span class="from-date"><?= $instanceStart ?></span></a>
                            </li>
                            <li><a href="javascript:;" class="oc-icon-user-plus"
                                data-load-indicator='<?= e(trans('backend::lang.form.saving')) ?>'
                                data-request='onUpdateEventWholeSeries'
                                data-request-update="calendar: '#Calendars'"
                                data-request-form='.control-popup form'
                                data-dismiss='popup'
                                ><?= e(trans('acorn.calendar::lang.models.eventpart.update_whole_series')) ?></a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        <?php endif ?>

        <?php if ($eventPart->canDelete()): ?>
            <div class='btn-group dropdown dropdown-fixed'>
                <button type='submit' class='btn btn-danger wn-icon-send'
                    data-load-indicator='<?= e(trans('backend::lang.form.deleting')) ?>'
                    data-request='onDeleteEventAfter'
                    data-request-update="calendar: '#Calendars'"
                    data-confirm="<?= e(trans('acorn.calendar::lang.models.eventpart.delete_from')) ?> <?= $instanceStart ?>?"
                    data-request-form='.control-popup form'
                    data-dismiss='popup'
                ><?= e(trans('acorn.calendar::lang.models.eventpart.delete_from')) ?> <span class="from-date"><?= $instanceStart ?></span></button>
                <button
                    type="button"
                    class="btn btn-default dropdown-toggle"
                    data-toggle="dropdown">
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" data-dropdown-title="Delete">
                    <li class="dropdown-container">
                        <ul>
                            <li class="dropdown-title">Delete</li>
                            <li class="first-item"><a href="javascript:;" class="oc-icon-trash-o"
                                data-load-indicator='<?= e(trans('backend::lang.form.deleting')) ?>'
                                data-request='onDeleteEventInstanceOnly'
                                data-request-update="calendar: '#Calendars'"
                                data-confirm="<?= e(trans('acorn.calendar::lang.models.eventpart.delete_only')) ?> <?= $instanceStart ?>?"
                                data-request-form='.control-popup form'
                                data-dismiss='popup'
                                ><?= e(trans('acorn.calendar::lang.models.eventpart.delete_only')) ?> <span class="from-date"><?= $instanceStart ?></a>
                            </li>
                            <li><a href="javascript:;" class="oc-icon-trash-o"
                                data-load-indicator='<?= e(trans('backend::lang.form.deleting')) ?>'
                                data-request='onDeleteEventWholeSeries'
                                data-request-update="calendar: '#Calendars'"
                                data-confirm="<?= e(trans('acorn.calendar::lang.models.eventpart.delete_whole_series_question')) ?>"
                                data-request-form='.control-popup form'
                                data-dismiss='popup'
                                ><?= e(trans('acorn.calendar::lang.models.eventpart.delete_whole_series')) ?></a>
                            </li>
                            <?php if ($hasDeletedInstances): ?>
                                <li><a href="javascript:;" class="oc-icon-user-plus"
                                    data-load-indicator='<?= e(trans('backend::lang.form.deleting')) ?>'
                                    data-request='onReInstateDeletedInstances'
                                    data-request-update="calendar: '#Calendars'"
                                    data-confirm="<?= e(trans('acorn.calendar::lang.models.eventpart.undelete_all_instances_question')) ?>"
                                    data-request-form='.control-popup form'
                                    data-dismiss='popup'
                                    ><?= e(trans('acorn.calendar::lang.models.eventpart.undelete_all_instances')) ?></a>
                                </li>
                            <?php endif ?>
                        </ul>
                    </li>
                </ul>
            </div>
        <?php endif ?>
    <?php endif ?>
<?php else: ?>
    <?php if ($eventPart->canWrite()): ?>
        <button type='submit' class='btn btn-primary wn-icon-send'
            data-load-indicator='<?= e(trans('backend::lang.form.saving')) ?>'
            data-request='onUpdateEventWholeSeries'
            data-request-update="calendar: '#Calendars'"
            data-request-form='.control-popup form'
            data-dismiss='popup'
        ><?= e(trans('backend::lang.form.update')) ?></button>
    <?php endif ?>

    <?php if ($eventPart->canDelete()): ?>
        <button type='submit' class='btn btn-danger wn-icon-send'
            data-load-indicator='<?= e(trans('backend::lang.form.deleting')) ?>'
            data-request='onDeleteEventWholeSeries'
            data-request-update="calendar: '#Calendars'"
            data-confirm="<?= e(trans('acorn.calendar::lang.models.eventpart.delete_the_event_question')) ?>"
            data-request-form='.control-popup form'
            data-dismiss='popup'
        ><?= e(trans('backend::lang.form.delete')) ?></button>
    <?php endif ?>
<?php endif ?>

<button type='button' class='btn btn-default action-close'
    data-dismiss='popup'
    data-request='onClose'
    data-request-update="calendar: '#Calendars'"
    data-request-form='.control-popup form'
><?= e(trans('backend::lang.form.close')) ?></button>

