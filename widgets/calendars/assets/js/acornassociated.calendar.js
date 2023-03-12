var acornassociated_nextData = true;
var toDate = new Date("2023-04-12");

$(document.body).on('touchmove', onExploreScroll); // for mobile
$(window).on('scroll', onExploreScroll);

function onExploreScroll() {
    if ($(window).scrollTop() >= ($(document).height() - $(window).height()) * 0.9) {
        if (acornassociated_nextData) {
            acornassociated_nextData = false;
            alert(toDate.toLocaleDateString('UTC'));
            $.ajax({
                type: 'POST',
                data: {
                   scopeName:'date',
                   options: '{"dates":[null,"2023-09-31 23:59:59"]}',
                },
                headers: {
                    'X-WINTER-REQUEST-HANDLER': 'instanceFilter::onFilterUpdate',
                    'X-WINTER-REQUEST-PARTIALS': '',
                },

                success: function (data) {
                    console.log(data);
                    // var xWinterAssets = data['X_WINTER_ASSETS'];
                    var calendarsInstanceHTML = data['#Calendars-instance'];
                    $('#Calendars').replaceWith($(calendarsInstanceHTML));
                    acornassociated_nextData = true;
                },
                error: function (data) {
                    acornassociated_nextData = false;
                }
            });
        }
    }
}
