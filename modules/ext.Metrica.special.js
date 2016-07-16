$(function(){

    Chart.defaults.global.elements.line.backgroundColor = "rgba(45,138,199,0.2)";
    Chart.defaults.global.elements.point.backgroundColor = "#85b5d6";
    //Chart.defaults.global.elements.line.borderColor = "#78a9e4";
    Chart.defaults.global.elements.point.hitRadius = 15;

    var apiUrl = mw.config.get('wgServer') + mw.config.get('wgScriptPath') + '/api.php?action=metricastat&format=json';
    
    var viewsChart, editsChart, rangeUnix;

    var pik = new Pikaday({
        field: $('#pik_field').get(0),
        bound: false,
        container: $('#pik').get(0),
        onSelect: function(date) {
            $('#pik_field').val( pik.toString() );
            var date = pik.getDate();
            var unix = date.getTime() / 1000;
            console.log(unix);
            rangeUnix = unix;
            redrawStatistics();
        },
        numberOfMonths: 3
    });
    
    // A bit of refactoring
    function redrawStatistics() {
        //TODO: update all
        drawViewsGraph();
        drawEditsGraph();
        drawViewsList();
        drawEditsList();
    }
    
    function drawViewsGraph() {
        $.get( apiUrl + '&do=page_views' + (rangeUnix ? '&range_unix=' + rangeUnix : '') , function(response){

        var graphData = response.metricastat[0];

        if( !graphData ) {
            return true;
        }

        var viewsChartElement = document.getElementById("chartViews").getContext("2d");
        var lineChartData = {
            labels : graphData.labels,
            datasets : [
                {
                    fillColor : "rgba(45,138,199,0.2)",
                    strokeColor : "rgba(220,220,220,1)",
                    pointColor : "rgba(220,220,220,1)",
                    pointStrokeColor : "#fff",
                    pointHighlightFill : "#fff",
                    pointHighlightStroke : "rgba(220,220,220,1)",
                    data : graphData.values
                }
            ]
        };

        if( viewsChart ) {
            viewsChart.data = lineChartData;
            viewsChart.update();
            viewsChart.render();
        }else{
            viewsChart = new Chart(viewsChartElement, {
                type: 'line',
                data: lineChartData,
                options: {
                    title: {
                        display: false
                    },
                    legend: {
                        display: false
                    },
                    responsive: true,
                    tooltips: {
                        callbacks: {
                            label: function(t,d) {
                                return t.yLabel + ' views';
                            }
                        }
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                min: 0/*,
                                stepSize: 1*/
                            }
                        }]
                    }
                }
            });
        }
    });
    }
    
    function drawEditsGraph() {
        $.get( apiUrl + '&do=page_edits' + (rangeUnix ? '&range_unix=' + rangeUnix : ''), function(response){

        var graphData = response.metricastat[0];

        if( !graphData ) {
            return true;
        }

        var editsChartElement = document.getElementById("chartEdits").getContext("2d");
        var lineChartData = {
            labels : graphData.labels,
            datasets : [
                {
                    fillColor : "rgba(220,220,220,0.2)",
                    strokeColor : "rgba(220,220,220,1)",
                    pointColor : "rgba(220,220,220,1)",
                    pointStrokeColor : "#fff",
                    pointHighlightFill : "#fff",
                    pointHighlightStroke : "rgba(220,220,220,1)",
                    data : graphData.values
                }
            ]

        };
        if( editsChart ) {
            editsChart.data = lineChartData;
            editsChart.update();
        }else{
            editsChart = new Chart(editsChartElement, {
                type: 'line',
                data: lineChartData,
                options: {
                    title: {
                        display: false
                    },
                    legend: {
                        display: false
                    },
                    responsive: true,
                    tooltips: {
                        callbacks: {
                            label: function(t,d) {
                                return t.yLabel + ' edits';
                            }
                        }
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                min: 0/*,
                                stepSize: 1*/
                            }
                        }]
                    }
                }
            });
        }

    });
    }
    
    function drawViewsList() {
        $.get( apiUrl + '&do=page_most_viewed' + (rangeUnix ? '&range_unix=' + rangeUnix : ''), function(response){

            var graphData = response.metricastat[0];
    
            if( !graphData ) {
                return true;
            }
    
            var $container = $('#most-viewed-pages');
            var $ul = $('<ul/>');
    
            $.each(graphData, function(i,v){
                var $li = $('<li/>');
                var $span = $('<span/>');
                $span.addClass('badge').addClass('badge-primary');
                $span.html( v.views );
                $li.html( v.link );
                $li.append( $span );
                $ul.append( $li );
            });
    
            $container.html('');
            $container.append( $ul );
    
        });
    }
    
    function drawEditsList() {
        $.get( apiUrl + '&do=page_most_edited' + (rangeUnix ? '&range_unix=' + rangeUnix : ''), function(response){

            var graphData = response.metricastat[0];
    
            if( !graphData ) {
                return true;
            }
    
            var $container = $('#most-edited-pages');
            var $ul = $('<ul/>');
    
            $.each(graphData, function(i,v){
                var $li = $('<li/>');
                var $span = $('<span/>');
                $span.addClass('badge').addClass('badge-primary');
                $span.html( v.edits );
                $li.html( v.link );
                $li.append( $span );
                $ul.append( $li );
            });
    
            $container.html('');
            $container.append( $ul );

        });
    }
    
    redrawStatistics();

});