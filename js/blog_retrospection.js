function timeSegmentSelected() {
    var intTimeSegment = jQuery('#timeSegmentDropDown').val();
    var data = {
        action: 'api_getTimeSegmentData',
        timeSegment: intTimeSegment      // We pass php values differently!
    };

    // We can also pass the url value separately from ajaxurl for front end AJAX implementations
    jQuery.post(ajaxurl, data, function (response) {
        var objResponse = jQuery.parseJSON(response);
        postCountChart([intTimeSegment - 1, intTimeSegment],
            [parseInt(objResponse.post_count.comparisonPeriod), parseInt(objResponse.post_count.timeSegment)],
            [parseInt(objResponse.page_count.comparisonPeriod), parseInt(objResponse.page_count.timeSegment)])
        postsPerMonthChart([intTimeSegment], objResponse.postsPerMonth.timeSegment)

    });
    jQuery('#checkboxPostCount').prop('checked', true);
}

function postCountChart(timeSegments, postCount, pageCount) {
    jQuery('#chartPostCount').empty();
    barChart(timeSegments, postCount, pageCount, 'chartPostCount', 'Number of Blogposts', ['Posts', 'Pages'])
}


function postsPerMonthChart(timeSegments, postCount) {
    jQuery('#chartPostsPerMonth').empty();
    barChart(timeSegments, postCount, pageCount, 'chartPostsPerMonth', 'Number of Posts per Month', ['Posts', 'Pages'])
}

function barChart(timeSegments, postCount, pageCount, strDivId, strTitle, arrLabels) {
    jQuery(function ($) {
        //TODO iterate over series
        $.jqplot(strDivId, [postCount, pageCount], {
            // The "seriesDefaults" option is an options object that will
            // be applied to all series in the chart.
            title: strTitle,
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
                {label: arrLabels[0]},
                {label: arrLabels[1]}
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
                    ticks: timeSegments
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