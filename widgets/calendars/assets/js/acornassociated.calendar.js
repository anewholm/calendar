var acornassociated_nextData = true;
var toDate = new Date("2023-04-12 23:59:59");

$(document.body).on('touchmove', onExploreScroll); // for mobile
$(window).on('scroll', onExploreScroll);

function onExploreScroll() {
    var toDateString;

    if ($(window).scrollTop() >= ($(document).height() - $(window).height()) * 0.9) {
        if (acornassociated_nextData) {
            acornassociated_nextData = false;
            toDate.setMonth(toDate.getMonth()+1);
            toDateString = toDate.toISOString().replace(/T|\.[0-9]+Z$/g, ' ').trim();

            $.ajax({
                type: 'POST',
                data: {
                   scopeName:'date',
                   options: '{"dates":[null,"' + toDateString + '"]}', //2023-09-31 23:59:59
                },
                headers: {
                    'X-WINTER-REQUEST-HANDLER': 'instanceFilter::onFilterUpdate',
                    'X-WINTER-REQUEST-PARTIALS': '',
                },

                success: function (data) {
                    var calendarsInstanceHTML = data['#Calendars-instance'];
                    $('#Calendars').replaceWith($(calendarsInstanceHTML));
                    acornassociated_nextData = true;
                },
                error: function (data) {
                    acornassociated_nextData = false;
                    if (window.console) console.error(data);
                }
            });
        }
    }
}
