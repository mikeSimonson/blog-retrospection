function timeSegmentSelected() {

    var intTimeSegment = jQuery('#timeSegmentDropDown').val();

    var data = {
        action: 'api_getTimeSegmentData',
        timeSegment: intTimeSegment      // We pass php values differently!
    };

    // We can also pass the url value separately from ajaxurl for front end AJAX implementations
    jQuery.post(ajaxurl, data, function (response) {
        var objResponse = jQuery.parseJSON(response);
        barChart([intTimeSegment - 1, intTimeSegment],
            [parseInt(objResponse.post_count.comparisonPeriod), parseInt(objResponse.post_count.timeSegment)],
            [parseInt(objResponse.page_count.comparisonPeriod), parseInt(objResponse.page_count.timeSegment)])

    });
}

function barChart(years, postCount, pageCount) {
    jQuery(function ($) {

        // Can specify a custom tick Array.
        // Ticks should match up one for each y value (category) in the series.
        $('#chartPostCount').empty();

        $.jqplot('chartPostCount', [postCount, pageCount], {
            // The "seriesDefaults" option is an options object that will
            // be applied to all series in the chart.
            title: 'Number of Blogposts',
            seriesDefaults: {
                renderer: $.jqplot.BarRenderer,
                pointLabels: {show: true},
                rendererOptions: {
                    barMargin: 10,
                    fillToZero: true,
                    highlightMouseOver: true,
                    varyBarColor: false
                }
            },
            series: [
                {label: 'Posts'},
                {label: 'Pages'}
            ],

            legend: {
                show: true,
                placement: 'outsideGrid'
            },
            /*highlighter: {
             show: true,
             sizeAdjust: 7.5
             },
             cursor: {
             show: false
             },*/
            axes: {
                // Use a category axis on the x axis and use our custom ticks.
                xaxis: {
                    min: 0,
                    max: Math.max(postCount) + Math.max(postCount) * 0.15,
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks: years
                },
                // Pad the y axis just a little so bars can get close to, but
                // not touch, the grid boundaries.  1.2 is the default padding.
                yaxis: {
                    min: 0,
                    pad: 1.05
                    //tickOptions: {formatString: '%d'}
                }
            }
        });
    });
}