$(function(){

    Chart.defaults.global.elements.line.backgroundColor = "rgba(45,138,199,0.2)";
    Chart.defaults.global.elements.point.backgroundColor = "#85b5d6";
    //Chart.defaults.global.elements.line.borderColor = "#78a9e4";
    Chart.defaults.global.elements.point.hitRadius = 15;

    var apiUrl = mw.config.get('wgServer') + mw.config.get('wgScriptPath') + '/api.php?action=metricastat&format=json';
    
    var viewsChart, editsChart, tsStart, tsEnd;
    var inputDateStart = $('#start_date');
    var inputDateEnd = $('#end_date');

    var pikStart = new Pikaday({
        field: inputDateStart.get(0),
        maxDate: moment().subtract(1,'d').toDate()
    });
    
    var pikEnd = new Pikaday({
        field: inputDateEnd.get(0),
        maxDate: moment().toDate()
    });
    
    $('#btn_date').click(function(){
        if( !inputDateEnd.val() && !inputDateStart.val() ) {
            alert('Please select dates range!');
            return false;
        }
        
        if( inputDateStart.val() ) {
            var startDate = pikStart.getMoment();
            tsStart = startDate.startOf('day').utc().unix();
        }
        
        if( inputDateEnd.val() ) {
            var endDate = pikEnd.getMoment();
            tsEnd = endDate.endOf('day').add(2,'h').utc().unix();
        }
        
        redrawStatistics();
    });
    
    $('#btn_date_clear').click(function(){
       inputDateStart.val('');
       inputDateEnd.val('');
       tsStart = null;
       tsEnd = null;
       redrawStatistics();
    });
    
    // A bit of refactoring
    function redrawStatistics() {
        //TODO: update all
        drawViewsGraph();
        drawEditsGraph();
        drawViewsList();
        drawEditsList();
    }
    
    function getTsString() {
        var str = '';
        if( tsEnd || tsStart ) {
            if( tsStart ) {
                str += '&start=' + tsStart
            }
            if( tsEnd ) {
                str += '&end=' + tsEnd
            }
        }
        return str;
    }
    
    function drawViewsGraph() {
        $.get( apiUrl + '&do=page_views' + getTsString() , function(response){

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
            viewsChart.data.datasets[0] = lineChartData.datasets[0];
            viewsChart.data.labels = lineChartData.labels;
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
        $.get( apiUrl + '&do=page_edits' + getTsString(), function(response){

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
            editsChart.data.datasets[0] = lineChartData.datasets[0];
            editsChart.data.labels = lineChartData.labels;
            editsChart.update();
            editsChart.render();
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
        $.get( apiUrl + '&do=page_most_viewed' + getTsString(), function(response){

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
        $.get( apiUrl + '&do=page_most_edited' + getTsString(), function(response){

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