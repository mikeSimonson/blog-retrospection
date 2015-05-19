function BlogRetrospection() {
    var intTimeSegment;
    var objResponse;
    this.getResponse = getResponse;
    this.setTimeSegment = setTimeSegment;
    this.getTimeSegment = getTimeSegment;
    this.getData = getData;

    function setTimeSegment(timeSegment) {
        intTimeSegment = timeSegment;
        getData();
        return objResponse;
    }

    function setResponse(json) {
        objResponse = json;
    }

    function getTimeSegment() {
        return intTimeSegment;
    }

    function getResponse() {
        return objResponse
    }

    function getData() {
        var data = {
            action: 'api_getTimeSegmentData',
            timeSegment: intTimeSegment      // We pass php values differently!
        };
        jQuery.post(ajaxurl, data, function (response) {
            setResponse(jQuery.parseJSON(response));
            drawSelectedCharts();
        });
    }
}

function drawSelectedCharts() {
    var brData = this.objBR.getResponse();
    var intTimeSegment = this.objBR.getTimeSegment();
    var arrSelectedCharts = getSelectedChartCheckBoxes();
    emptyAllCharts();
    for (var i = 0; i < arrSelectedCharts.length; i++) {
        switch (arrSelectedCharts[i]) {
            case 'checkboxPostCount':
                postCountChart([intTimeSegment - 1, intTimeSegment],
                    [parseInt(brData.post_count.comparisonPeriod), parseInt(brData.post_count.timeSegment)],
                    [parseInt(brData.page_count.comparisonPeriod), parseInt(brData.page_count.timeSegment)]);
                break;
            case 'checkboxPostPerMonth':
                postsPerMonthChart(intTimeSegment, brData.postsPerMonth.timeSegment);

        }
    }
}

function emptyAllCharts() {

    var arrDivContainer = ['#chartPostCount', '#chartPostsPerMonth'];

    for (var i = 0; i < arrDivContainer.length; i++) {
        //remove content from container
        jQuery(arrDivContainer[i]).empty();
        // set size to 0
        jQuery(arrDivContainer[i]).css({
            height: '0px',
            width: '0px'
        });
    }
}


function getSelectedChartCheckBoxes() {
    var selected = [];
    jQuery('#br_check_boxes input:checked').each(function () {
        selected.push(jQuery(this).attr('id'));
        if (selected.length === 0) {
        }
    });
    return selected;
}

function timeSegmentSelected() {
    var intTimeSegment = jQuery('#timeSegmentDropDown').val();
    if (getSelectedChartCheckBoxes().length === 0) {
        jQuery('#checkboxPostCount').prop('checked', true);
    }
    this.objBR = new BlogRetrospection();
    this.objBR.setTimeSegment(intTimeSegment);
}

function postCountChart(timeSegments, postCount, pageCount) {
    jQuery('#chartPostCount').css({
        height: '350px',
        width: '400px'
    });
    stackedBarChart(timeSegments, [postCount, pageCount], 'chartPostCount', 'Number of Blog Posts', ['Posts', 'Pages'])
}

function postsPerMonthChart(timeSegments, postsPerMonth) {
    jQuery('#chartPostsPerMonth').css({
        height: '350px',
        width: '400px'
    });

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
    barChart('chartPostsPerMonth', arrXyValues, 'Number of Posts per Month', 'Posts')
}

function barChart(strDivId, arrXyValues, strTitle, strLegendTitle) {

    jQuery.jqplot(strDivId, [arrXyValues], {
        title: strTitle,
        series: [{
            label: strLegendTitle,
            renderer: jQuery.jqplot.BarRenderer
        }],
        legend: {
            show: true,
            placement: 'outsideGrid'
        },
        highlighter: {
            show: true,
            tooltipAxes: 'y',
            sizeAdjust: 7.5
        },
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
    jQuery.jqplot(strDivId, arrSeries, {
        // The "seriesDefaults" option is an options object that will
        // be applied to all series in the chart.
        title: strTitle,
        stackSeries: true,
        seriesDefaults: {
            renderer: jQuery.jqplot.BarRenderer,
          //  pointLabels: {show: true},
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
        highlighter: {
            show: true,
            tooltipAxes: 'y',
            sizeAdjust: 7.5
        },

        legend: {
            show: true,
            placement: 'outsideGrid'
        },
        axes: {
            // Use a category axis on the x axis and use our custom ticks.
            xaxis: {
                min: 0,
                renderer: jQuery.jqplot.CategoryAxisRenderer,
                pad: 1.15,
                ticks: timeSegments
            },
            // Pad the y axis just a little so bars can get close to, but
            // not touch, the grid boundaries.  1.2 is the default padding.
            yaxis: {
                min: 0,
                pad: 1.15,
                tickOptions: {formatString: '%d'}
            }
        }
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