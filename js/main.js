function barChart(years, postCount) {
    jQuery(function ($) {

        // Can specify a custom tick Array.
        // Ticks should match up one for each y value (category) in the series.


        $.jqplot('chart1', [postCount], {
            // The "seriesDefaults" option is an options object that will
            // be applied to all series in the chart.
            title: 'Number of Blogposts',
            seriesDefaults: {
                renderer: $.jqplot.BarRenderer,
                rendererOptions: {
                    fillToZero: true,
                    highlightMouseOver: true,
                    varyBarColor: true
                }
            },
            axes: {
                // Use a category axis on the x axis and use our custom ticks.
                xaxis: {
                    min: 0,
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks: years
                },
                // Pad the y axis just a little so bars can get close to, but
                // not touch, the grid boundaries.  1.2 is the default padding.
                yaxis: {
                    min: 0,
                    pad: 1.05,
                    //tickOptions: {formatString: '%d'}
                }
            }
        });
    });
}