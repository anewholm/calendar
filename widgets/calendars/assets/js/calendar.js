var acorn_dataLock = false;
var filterWidget;

// --------------------------------------------- Popups and forms
$(document).on('change', function(){
    $('.field-datepicker input[data-datepicker]').on('focusin', function(){
        var dValue = ($(this).val() ? new Date($(this).val()) : undefined);
        $(this).data('date', dValue);
    });

    $('#DatePicker-formStart-date-start').change(function(){
        var end    = $('#DatePicker-formEnd-date-end').val();
        var dEnd   = (end   ? new Date(end)   : undefined);
        var start  = $(this).val();
        var dStart = (start ? new Date(start) : undefined);
        var dStartOld = $(this).data('date');
        if (dStart && dStartOld && dEnd) {
            const diffTime   = dEnd - dStartOld;
            dEnd.setTime(dStart.getTime() + diffTime);
            $(this).data('date', dStart);
            $('#DatePicker-formEnd-date-end').val(dEnd.toLocaleDateString());
        }
        // this only works if the warning element was present to begin with... hard to accomplish this by relying on the backend...
        var tStart = dStart.getTime();
        var tNow = new Date().getTime();
        if (document.getElementsByClassName("past-event-warning").length){
            var elem = document.getElementsByClassName("past-event-warning")[0];
            if (tStart > tNow && elem.style.display != "none"){
                elem.style.display = "none";
            }
            else if (tStart <= tNow && elem.style.display != ""){
                elem.style.display = "";
            }
        }
    });
});

// --------------------------------------------- Filters
$(document).ready(function(){
    filterWidget = $('#Filter-instanceFilter').data()['oc.filterwidget'];
    filterWidget.staticTop   = filterWidget.$el.offset().top;
    filterWidget.staticWidth = filterWidget.$el.outerWidth();

    // Attach events
    filterWidget.$el.find('[data-scope-name=date]').on('change.oc.filterScope', acorn_onPushOptionsSuccess);
    $('#previous-month').click(acorn_onPreviousClick);
    $(document.body).on('touchmove', acorn_onExploreScroll); // for mobile
    $(window).on(       'scroll',    acorn_onExploreScroll);
});

function acorn_public_instance(id) {
    // #!/instance/<x> direct open up of an event popup
    if (window.console) console.info('open instance [' + id + ']');
    $('#Calendars').popup({
        handler: 'onOpenEvent',
        extraData: {path: id, type: 'event'}
    });
}

function acorn_public_event(id) {
    // #!/instance/<x> direct open up of an event popup
    if (window.console) console.info('open event [' + id + ']');
    $('#Calendars').popup({
        handler: 'onOpenEvent',
        extraData: {path: id, type: 'single-event'}
    });
}

function acorn_onPushOptionsSuccess(e) {
    var dateFilter = $(e.target);
    var dates      = filterWidget.scopeValues.date.dates;
    var has2Dates  = (dates && dates.length == 2);
    if (has2Dates) {
        var fromDate   = (has2Dates && dates[0] ? new Date(dates[0]) : new Date());
        var toDate     = (has2Dates && dates[1] ? new Date(dates[1]) : null); 
        var fromDateString = fromDate.toLocaleString().replace(/[ ,].*$/, '');
        var toDateString   = toDate.toLocaleString().replace(/[ ,].*$/, '');

        filterWidget.updateScopeSetting(dateFilter, fromDateString + ' → ' + toDateString);
        $('.filter-scope-date').data({scopeData:{dates:dates}});
    } else {
        // This is a filter reset
        $('.filter-scope-date').data({scopeData:{dates:[null, null]}});
    }

    acorn_dataLock = false;
}

function acorn_onPreviousClick() {
    if (!acorn_dataLock) {
        acorn_dataLock = true;

        // TODO: organise this better: only use the filter-scope-date?
        var filterDate = $('.filter-scope-date').data();
        var dates      = filterDate.scopeData.dates;
        // Default to from today if no filter
        var has2Dates  = (dates && dates.length == 2);
        var fromDate   = (has2Dates && dates[0] ? new Date(dates[0]) : new Date());
        var toDate     = (has2Dates && dates[1] ? new Date(dates[1]) : null); 
        // Move 1 month back
        if (!toDate) toDate = new Date(fromDate);
        fromDate.setMonth(fromDate.getMonth()-1);

        acorn_pushOptions(fromDate, toDate);
    }

    return false;
}

function acorn_onExploreScroll() {
    var scrollTop = $(window).scrollTop();

    // Fix the Filter position when scrolling
    // TODO: Check this on mobile
    // TODO: Make this fixed positioning generic,
    // if there is not a generic snowboard version already!
    if (scrollTop >= filterWidget.staticTop) {
        if (filterWidget.$el.css('position') != 'fixed')  filterWidget.$el.css({
            position:'fixed',
            top:'0px',
            width:filterWidget.staticWidth + 'px',
            'z-index':1000
        });
    } else {
        if (filterWidget.$el.css('position') != 'static') filterWidget.$el.css({
            position:'static',
            top:'auto',
            'z-index':'auto'
        });
    }

    // Infinite Scroll
    if (scrollTop >= ($(document).height() - $(window).height()) * 0.9) {
        if (!acorn_dataLock) {
            acorn_dataLock = true;

            var filterDate = $('.filter-scope-date').data();
            var dates      = filterDate.scopeData.dates;
            // Default to from today if no filter
            var has2Dates  = (dates && dates.length == 2);
            var fromDate   = (has2Dates && dates[0] ? new Date(dates[0]) : new Date());
            var toDate     = (has2Dates && dates[1] ? new Date(dates[1]) : null); 
            // Move 1 month forward
            if (!toDate) toDate = new Date(fromDate);
            toDate.setMonth(toDate.getMonth()+1);

            acorn_pushOptions(fromDate, toDate);
        }
    }
}

function acorn_formatDate(date) {
    return (date ? date.toISOString().replace(/T|\.[0-9]+Z$/g, ' ').trim() : null);
}

function acorn_pushOptions(fromDate, toDate) {
    var dates = {'dates':[
        acorn_formatDate(fromDate),
        acorn_formatDate(toDate)
    ]};

    filterWidget.scopeValues['date'] = dates;
    filterWidget.isActiveScopeDirty  = true;
    filterWidget.pushOptions('date'); // AJAX instanceFilter::onFilterUpdate()
}

// --------------------------------------------- Drag drop
$(document).ready(function(){
    // Partial reloading
    $(window).on('ajaxUpdate', function(){
        acorn_assignDragDropEvents();
    });

    // Immediate
    acorn_assignDragDropEvents();
});

function acorn_assignDragDropEvents() {
    $('.sortable').sortable({ // e.g. .calendar
        useAnimation: true,
        usePlaceholderClone: true, // .placeholder will appear under the .sortable. See below
        onDrop: function($item, container, _super, event){
            var result = _super($item, container, undefined, event);

            // Custom processing: sub-drop targets with data-request-drop
            // e.g. onChangeDate
            var jEventTarget = $(event.target);
            var jDroppable   = jEventTarget.filter('.drop-target')
                .add(jEventTarget.closest('.drop-target'))
                .add(jEventTarget.closest(':has(.drop-target)').find('.drop-target'))
                .first();
            var dataRequestDrop = jDroppable.attr('data-request-drop') || jDroppable.closest('.sortable').attr('data-request-drop');
            if (dataRequestDrop) {
                if (window.console) console.info('Calling @data-request-drop ' + dataRequestDrop + '()');
                $item.request(dataRequestDrop, {
                    data:{
                        dataRequestID:    $item.attr(  'data-request-id'),
                        dataRequestDropID:jDroppable.attr('data-request-drop-id'),
                    },
                    // We go for a full refresh because of changed instance_num
                    // and to demonstrate to the user if the process has fully worked or not
                    // update: {'calendar': '#Calendars-instance'},
                });
            }

            return result;
        },
        exclude: ':has(.can-write.value-false)', // Cannot drag things without write permission
        distance:10, // Because we also have a popup onclick
    });

    // Move the placholder to drop-targets
    // https://octobercms.com/docs/ui/drag-sort
    $('.drop-target').mouseenter(function(){
        if ($(document.body).hasClass('dragging')) {
            if (!$(this).children('.placeholder').length) {
                $(this).append($('.sortable .placeholder'));
            }
        }
    });

    if (window.console) console.info('acorn_assignDragDropEvents()');
}

