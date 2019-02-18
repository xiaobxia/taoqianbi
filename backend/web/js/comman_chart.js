function setChart(title, url, documentId, legend, data) {
    // 路径配置
    require.config({
        paths: {
            echarts: url + '/js'
        }
    });
    // 使用
    require(
        [
            'echarts',
            'echarts/theme/macarons',
            'echarts/chart/pie',
            'echarts/chart/funnel'
        ],
        function (ec, theme) {
            // 基于准备好的dom，初始化echarts图表

            var myChart = ec.init(document.getElementById(documentId), theme);

            option = {
                    title : {
                        text: title,
                        //subtext: '纯属虚构',
                        x:'center'
                    },
                    tooltip : {
                        trigger: 'item',
                        formatter: "{a} <br/>{b} {c} ({d}%)"
                    },
                    legend: {
                        orient : 'vertical',
                        x : 'left',
                        data:legend
                    },
                    toolbox: {
                        show : true,
                        feature : {
                            /*mark : {show: true},
                            dataView : {show: true, readOnly: false},*/
                            magicType : {
                                show: true,
                                type: ['pie'],
                                option: {
                                    funnel: {
                                        x: '25%',
                                        width: '50%',
                                        funnelAlign: 'left',
                                        max: 1548
                                    }
                                }
                            },
                            restore : {show: true},
                            saveAsImage : {show: true}
                        }
                    },
                    calculable : true,
                    series : [
                        {
                            name:'数量',
                            type:'pie',
                            radius : '55%',
                            center: ['50%', '60%'],
                            data: data
                        }
                    ]
                };
            // 为echarts对象加载数据
            myChart.setOption(option);
        }
    );
}
function setLineChart(url, documentId, legend, date, series) {
    // 路径配置
    require.config({
        paths: {
            echarts: url + '/js'
        }
    });

    // 使用
    require(
        [
            'echarts',
            'echarts/theme/macarons',
            'echarts/chart/line',
            'echarts/chart/bar',
        ],
        function (ec, theme) {
            // 基于准备好的dom，初始化echarts图表
            var myChart = ec.init(document.getElementById(documentId), theme);

            var option = {
                    tooltip : {
                        trigger: 'axis'
                    },
                    legend: {
                        data: legend
                    },
                    toolbox: {
                        show : true,
                        feature : {
                            /*mark : {show: true},
                            dataView : {show: true, readOnly: false},*/
                            magicType : {show: true, type: ['line', 'bar']},
                            restore : {show: true},
                            saveAsImage : {show: true}
                        }
                    },
                    calculable : true,
                    xAxis : [
                        {
                            type : 'category',
                            boundaryGap : false,
                            data : date
                        }
                    ],
                    yAxis : [
                        {
                            type : 'value'
                        }
                    ],
                    series : series
                };

            // 为echarts对象加载数据
            myChart.setOption(option);
        }
    );
}

function setBarChart(url, documentId, legend, date, series) {
    // 路径配置
    require.config({
        paths: {
            echarts: url + '/js'
        }
    });

    // 使用
    require(
        [
            'echarts',
            'echarts/theme/macarons',
            'echarts/chart/line',
            'echarts/chart/bar',
        ],
        function (ec, theme) {
            // 基于准备好的dom，初始化echarts图表
            var myChart = ec.init(document.getElementById(documentId), theme);

            var option = {
                    tooltip : {
                        trigger: 'axis'
                    },
                    legend: {
                        data: legend
                    },
                    toolbox: {
                        show : true,
                        feature : {
                            mark : {show: true},
                            dataView : {show: true, readOnly: false},
                            magicType: {show: true, type: ['line', 'bar']},
                            restore : {show: true},
                            saveAsImage : {show: true}
                        }
                    },
                    calculable : true,
                    xAxis : [
                        {
                            type : 'value',
                            boundaryGap : [0, 0.01],
                        }
                    ],
                    yAxis : [
                        {
                            type : 'category',
                            data : date
                        }
                    ],
                    series : series
                };

            // 为echarts对象加载数据
            myChart.setOption(option);
        }
    );

}
