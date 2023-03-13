var acornassociated_dataLock = false;
var filterWidget;

$(document).ready(function(){
    filterWidget = $('#Filter-instanceFilter').data()['oc.filterwidget'];
    filterWidget.staticTop = filterWidget.$el.offset().top;

    // Attach events
    filterWidget.$el.find('[data-scope-name=date]').on('change.oc.filterScope', acornassociated_onPushOptionsSuccess);
    $('#previous-month').click(acornassociated_onPreviousClick);
    $(document.body).on('touchmove', acornassociated_onExploreScroll); // for mobile
    $(window).on(       'scroll',    acornassociated_onExploreScroll);
});

function acornassociated_onPushOptionsSuccess(e) {
    var dateFilter = $(e.target);
    var dates      = filterWidget.scopeValues.date.dates;
    var fromDate   = new Date(dates[0]);
    var toDate     = new Date(dates[1]);

    filterWidget.updateScopeSetting(dateFilter, fromDate.toLocaleString().substr(0,9) + ' → ' + toDate.toLocaleString().substr(0,9));
    $('.filter-scope-date').data({scopeData:{dates:dates}});

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
    if (scrollTop >= filterWidget.staticTop) {
        if (filterWidget.$el.css('position') != 'fixed') filterWidget.$el.css({position:'fixed', top:'0px', 'z-index':1000});
    } else {
        if (filterWidget.$el.css('position') != 'static') filterWidget.$el.css({position:'static', top:'auto', 'z-index':'auto'});
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
