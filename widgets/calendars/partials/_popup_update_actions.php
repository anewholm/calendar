<!-- TODO: Update logic based on which instance is selected -->

<div class='btn-group dropdown dropdown-fixed'>
    <button type='submit' class='btn btn-primary wn-icon-send'
        data-load-indicator='Updating'
        data-request='onUpdateEventFromInstance'
        data-request-update="calendar: '#Calendars'"
        data-request-form='.control-popup form'
        data-dismiss='popup'
    ><?= e(trans('Update from')) ?> <span class="from-date"><?= $instanceStart ?></span></button>
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
                    data-load-indicator='Updating'
                    data-request='onUpdateEventInstanceOnly'
                    data-request-update="calendar: '#Calendars'"
                    data-request-form='.control-popup form'
                    data-dismiss='popup'
                    ><?= e(trans('Update only')) ?> <span class="from-date"><?= $instanceStart ?></span></a>
                </li>
                <li><a href="javascript:;" class="oc-icon-user-plus"
                    data-load-indicator='Updating'
                    data-request='onUpdateEventWholeSeries'
                    data-request-update="calendar: '#Calendars'"
                    data-request-form='.control-popup form'
                    data-dismiss='popup'
                    >Update whole series</a>
                </li>
            </ul>
        </li>
    </ul>
</div>

<div class='btn-group dropdown dropdown-fixed'>
    <button type='submit' class='btn btn-danger wn-icon-send'
        data-load-indicator='Deleting'
        data-request='onDeleteEventAfter'
        data-request-update="calendar: '#Calendars'"
        data-confirm="Delete the event after <?= $instanceStart ?>?"
        data-request-form='.control-popup form'
        data-dismiss='popup'
    ><?= e(trans('Delete from')) ?> <span class="from-date"><?= $instanceStart ?></span></button>
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
                    data-load-indicator='Deleting'
                    data-request='onDeleteEventInstanceOnly'
                    data-request-update="calendar: '#Calendars'"
                    data-confirm="Delete just <?= $instanceStart ?>?"
                    data-request-form='.control-popup form'
                    data-dismiss='popup'
                    ><?= e(trans('Delete only')) ?> <span class="from-date"><?= $instanceStart ?></a>
                </li>
                <li><a href="javascript:;" class="oc-icon-trash-o"
                    data-load-indicator='Deleting'
                    data-request='onDeleteEventWholeSeries'
                    data-request-update="calendar: '#Calendars'"
                    data-confirm="Delete whole series?"
                    data-request-form='.control-popup form'
                    data-dismiss='popup'
                    ><?= e(trans('Delete whole series')) ?></a>
                </li>
                <li><a href="javascript:;" class="oc-icon-user-plus"
                    data-load-indicator='Deleting'
                    data-request='onReInstateDeletedInstances'
                    data-request-update="calendar: '#Calendars'"
                    data-confirm="UnDelete all instances?"
                    data-request-form='.control-popup form'
                    data-dismiss='popup'
                    ><?= e(trans('UnDelete all instances')) ?></a>
                </li>
            </ul>
        </li>
    </ul>
</div>

<button type='button' class='btn btn-default' data-dismiss='popup'><?= e(trans('Close')) ?></button>

