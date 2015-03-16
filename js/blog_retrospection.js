function checkEvents() {
    jQuery('input[type="checkbox"]').change(function (event) {
        console.log(jQuery(this));
    });
}

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
    checkEvents();
}

function postCountChart(timeSegments, postCount, pageCount) {
    jQuery('#chartPostCount').empty();
    stackedBarChart(timeSegments, [postCount, pageCount], 'chartPostCount', 'Number of Blogposts', ['Posts', 'Pages'])
}


function postsPerMonthChart(timeSegments, postsPerMonth) {
    var arrXyValues = [];
    for (var i = 0; i < postsPerMonth.length; i++) {
        switch (parseInt(postsPerMonth[i].postmonth)) {
            case 1:
                arrXyValues[i] = ["January", postsPerMonth[i].count];
                break;
            case 2:
                arrXyValues[i] = ["February", postsPerMonth[i].count];
                break;
            case 3:
                arrXyValues[i] = ["March", postsPerMonth[i].count];
                break;
            case 4:
                arrXyValues[i] = ["April", postsPerMonth[i].count];
                break;
            case 5:
                arrXyValues[i] = ["May", postsPerMonth[i].count];
                break;
            case 6:
                arrXyValues[i] = ["June", postsPerMonth[i].count];
                break;
            case 7:
                arrXyValues[i] = ["July", postsPerMonth[i].count];
                break;
            case 8:
                arrXyValues[i] = ["August", postsPerMonth[i].count];
                break;
            case 9:
                arrXyValues[i] = ["September", postsPerMonth[i].count];
                break;
            case 10:
                arrXyValues[i] = ["October", postsPerMonth[i].count];
                break;
            case 11:
                arrXyValues[i] = ["November", postsPerMonth[i].count];
                break;
            case 12:
                arrXyValues[i] = ["December", postsPerMonth[i].count];
                break;
        }
    }
    jQuery('#chartPostsPerMonth').empty();
    barChart('chartPostsPerMonth', arrXyValues, 'Number of Posts per Month', ['Posts'])
}

function barChart(strDivId, arrXyValues) {

    jQuery.jqplot(strDivId, [arrXyValues], {
        series: [{renderer: jQuery.jqplot.BarRenderer}],
        axesDefaults: {
            tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer,
            tickOptions: {
                angle: -30,
                fontSize: '10pt'
            }
        },
        axes: {
            xaxis: {
                renderer: jQuery.jqplot.CategoryAxisRenderer
            },
            yaxis: {
                min: 0,
                max: getMaxOfSeries(arrXyValues, 1),
                tickOptions: {formatString: '%d'},
                pad: 1.15

            }
        }
    });
}

function stackedBarChart(timeSegments, arrSeries, strDivId, strTitle, arrLabels) {
    jQuery(function ($) {

        $.jqplot(strDivId, arrSeries, {
            // The "seriesDefaults" option is an options object that will
            // be applied to all series in the chart.
            title: strTitle,
            stackSeries: true,
            seriesDefaults: {
                renderer: $.jqplot.BarRenderer,
                pointLabels: {show: true},
                rendererOptions: {
                    barMargin: 60,
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
            axes: {
                // Use a category axis on the x axis and use our custom ticks.
                xaxis: {
                    min: 0,
                    renderer: $.jqplot.CategoryAxisRenderer,
                    pad: 1.15,
                    ticks: timeSegments
                },
                // Pad the y axis just a little so bars can get close to, but
                // not touch, the grid boundaries.  1.2 is the default padding.
                yaxis: {
                    min: 0,
                    //max: intMax ,
                    pad: 1.15,
                    tickOptions: {formatString: '%d'}
                }
            }
        });
    });
}

function getMaxOfSeries(arrSeries, intNumberIndex) {
    var intMax = 0;
    for (var i = 0; i < arrSeries.length; i++) {

        var maxOfSeries = Math.max(parseInt(arrSeries[i][intNumberIndex]));
        if (maxOfSeries > intMax) {
            intMax = maxOfSeries;
        }
    }
    return (intMax + intMax * 0.25);
}