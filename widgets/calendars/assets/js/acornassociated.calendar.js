var acornassociated_dataLock = false;
var filterWidget;

$(document).ready(function(){
    filterWidget = $('#Filter-instanceFilter').data()['oc.filterwidget'];
    filterWidget.staticTop   = filterWidget.$el.offset().top;
    filterWidget.staticWidth = filterWidget.$el.outerWidth();

    // Attach events
    filterWidget.$el.find('[data-scope-name=date]').on('change.oc.filterScope', acornassociated_onPushOptionsSuccess);
    $('#previous-month').click(acornassociated_onPreviousClick);
    $(document.body).on('touchmove', acornassociated_onExploreScroll); // for mobile
    $(window).on(       'scroll',    acornassociated_onExploreScroll);

    $(document).on('change', function(){
        // Callouts (hints) close button
        // NOTE: Not using data-dismiss="callout" because we want a cross and a slideUp effect
        $('.callout .close').on('click', function(){
            $(this).closest('.callout').slideUp();
        });
    });

    // https://octobercms.com/docs/ui/drag-sort
    $('.drop-target').mouseover(function(){
        // Move the placholder to drop-targets
        if ($(document.body).hasClass('dragging')) {
            if (!$(this).children('.placeholder').length) {
                $(this).append($('.sortable .placeholder'));
            }
        }
    });
    $('.sortable').sortable({ // e.g. .calendar
        useAnimation: true,
        usePlaceholderClone: true, // .placeholder will appear under the .sortable
        onDrop: function(jElement, jContainer, func, e){
            // Standard onDrop
            jElement.removeClass('dragged').removeAttr('style');
            $(document.body).removeClass('dragging');

            // Custom processing: sub-drop targets
            var jDroppable = $(e.target).filter('.drop-target');
            jDroppable.append(jElement);

            // Server request to change instance attributes
            var fDataRequestDrop = jDroppable.attr('data-request-drop');
            if (fDataRequestDrop) window[fDataRequestDrop].call(jDroppable, jElement, e);
        },
        exclude: ':has(.can-write.value-false)', // Cannot drag things without write permission
    });
});

function acornassociated_dataRequestDrop(jElement, e) {
    var dataDate = $(this).attr('data-request-drop-id');

    jElement.request('onChangeDate', {
        data:{
            instanceID:jElement.attr('data-request-id'),
            newDate:dataDate,
        },
        //update: {'calendar': '#Calendar'},
    });
}

function acornassociated_onPushOptionsSuccess(e) {
    var dateFilter = $(e.target);
    var dates      = filterWidget.scopeValues.date.dates;
    if (dates.length) {
        var fromDate   = new Date(dates[0]);
        var toDate     = new Date(dates[1]);
        var fromDateString = fromDate.toLocaleString().replace(/[ ,].*$/, '');
        var toDateString   = toDate.toLocaleString().replace(/[ ,].*$/, '');

        filterWidget.updateScopeSetting(dateFilter, fromDateString + ' → ' + toDateString);
        $('.filter-scope-date').data({scopeData:{dates:dates}});
    }

    acornassociated_dataLock = false;
}

function acornassociated_onPreviousClick() {
    if (!acornassociated_dataLock) {
        acornassociated_dataLock = true;

        // TODO: organise this better: only use the filter-scope-date?
        var filterDate = $('.filter-scope-date').data();
        var dates      = filterDate.scopeData.dates;
        var fromDate = new Date(dates[0]);
        var toDate   = new Date(dates[1]);
        fromDate.setMonth(fromDate.getMonth()-1);

        acornassociated_pushOptions(fromDate, toDate);
    }

    return false;
}

function acornassociated_onExploreScroll() {
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
        if (!acornassociated_dataLock) {
            acornassociated_dataLock = true;

            var filterDate = $('.filter-scope-date').data();
            var dates      = filterDate.scopeData.dates;
            var fromDate   = new Date(dates[0]);
            var toDate     = new Date(dates[1]);
            toDate.setMonth(toDate.getMonth()+1);

            acornassociated_pushOptions(fromDate, toDate);
        }
    }
}

function acornassociated_formatDate(date) {
    return (date ? date.toISOString().replace(/T|\.[0-9]+Z$/g, ' ').trim() : null);
}

function acornassociated_pushOptions(fromDate, toDate) {
    var dates = {'dates':[
        acornassociated_formatDate(fromDate),
        acornassociated_formatDate(toDate)
    ]};

    filterWidget.scopeValues['date'] = dates;
    filterWidget.isActiveScopeDirty  = true;
    filterWidget.pushOptions('date'); // AJAX instanceFilter::onFilterUpdate()
}
