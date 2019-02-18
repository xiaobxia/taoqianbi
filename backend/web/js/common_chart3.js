//if (typeof _ == 'undefined') {
//    alert('lodash required...');
//}

function setChart(title, documentId, legend, data) {
    var myChart = echarts.init(document.getElementById(documentId));

    var option = {
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
            data:['最小占比','最大占比'],
            orient : 'vertical',
            x : 'left',
            data: legend
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
        series : [ {
            name:'数量',
            type:'pie',
            radius : '55%',
            center: ['50%', '60%'],
            data: data
        } ]
    };

    // 为echarts对象加载数据
    myChart.setOption(option);
}

function setLineChart(documentId, legend, data, series) {
    var myChart = echarts.init(document.getElementById(documentId));
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
                data : data,
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

function setLineChart2(documentId, legend, data, series, yMin) {
    var myChart = echarts.init(document.getElementById(documentId));
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
                data : data,
            }
        ],
        yAxis : [
            {
                type : 'value',
                min: yMin
                //max: yMax
            }
        ],
        series : series
    };

    // 为echarts对象加载数据
    myChart.setOption(option);
}

/**
 * 生成 bar 图
 * !!! 需要jQuery 和 lodash 支持 !!!
 * 参考: http://echarts.baidu.com/tutorial.html#ECharts%20%E4%B8%AD%E7%9A%84%E4%BA%8B%E4%BB%B6%E5%92%8C%E8%A1%8C%E4%B8%BA
 * @param string title
 * @param string dom_id
 * @param array data
 * @param object handler 支持 'click', 'dblclick', 'mousedown', 'mousemove', 'mouseup', 'mouseover', 'mouseout'
 * @return object echarts
 */
function setBarChart(title, dom_id, data, handler) {
    var myChart = echarts.init(document.getElementById(dom_id));
    var xAxis_data = _.keys(data);
    var series_data = _.values(data);

    var option = {
        title: {
            text: title
        },
        tooltip: {},
        xAxis: {
            data: xAxis_data,

            axisLabel : {
                rotate:45, //刻度旋转45度角
                textStyle:{
                    fontSize:10,
                }
            }
        },
        yAxis: {},
        series: [{
            name: title,
            type: 'bar',
            data: series_data
        }]
    };

    // 为echarts对象加载数据
    myChart.setOption(option);
    if ($.isPlainObject(handler)) {
        $.each(handler, function(evt, cb) {
            if ($.isFunction(cb) ) {
                myChart.on(evt, cb);
            }
        });
    }

    return myChart;
}

function setBarChart1(title, dom_id, data, handler) {
    var myChart = echarts.init(document.getElementById(dom_id));
    var xAxis_data = _.keys(data);
    var series_data = _.values(data);

    var option = {
        title: {
            text: title
        },
        tooltip: {},
        xAxis: {
            data: xAxis_data,

            axisLabel : {
                rotate:45, //刻度旋转45度角
                textStyle:{
                    fontSize:10,
                }
            }
        },
        yAxis: {},
        series: [{
            name: title,
            type: 'bar',
            barMaxWidth:60,
            data: series_data
        }]
    };

    // 为echarts对象加载数据
    myChart.setOption(option);
    if ($.isPlainObject(handler)) {
        $.each(handler, function(evt, cb) {
            if ($.isFunction(cb) ) {
                myChart.on(evt, cb);
            }
        });
    }

    return myChart;
}
    function setBarChart2(title, dom_id, date, data1, data2, handler) {
        var myChart = echarts.init(document.getElementById(dom_id));
        var data3 = [];
        for (var i = data1.length - 1; i >= 0; i--) {
            data3[i] = (data1[i] - data2[i]).toFixed(2);
        }
        var option = {
            title: {
                text: title
            },
            tooltip : {
                trigger: 'axis',
                axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                    type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
                },
                formatter: function (params){
                    var str = "最小占比：" + (params[0].value - 0).toFixed(2) + "<br/>最大占比：" + (params[1].value - 0 + params[0].value).toFixed(2);
                    return str;
                }
            },
            legend: {
                data: ['最小占比', '最大占比']
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            yAxis:  {
                type: 'value'
            },
            xAxis: {
                type: 'category',
                data: date
            },
            series: [
                {
                    name: '最小占比',
                    type: 'bar',
                    stack: 'sum',
                    label: {
                        normal: {
                            show: true,
                            position: 'inside'
                        }
                    },
                    barMaxWidth:40,
                    data: data2,
                },{
                    name: '最大占比',
                    type: 'bar',
                    stack: 'sum',
                    label: {
                        normal: {
                            show: true,
                            position: 'outside',
                            formatter: function(params){
                                return data1[params['dataIndex']];
                            }
                        }
                    },
                    barMaxWidth:40,
                    data: data3,
                },
            ]
        };
        // 为echarts对象加载数据
        myChart.setOption(option);
        if ($.isPlainObject(handler)) {
            $.each(handler, function(evt, cb) {
                if ($.isFunction(cb) ) {
                    myChart.on(evt, cb);
                }
            });
        }

        return myChart;
    }
/**
 * 生成 对比bar 图
 * !!! 需要jQuery 和 lodash 支持 !!!
 * 参考: http://echarts.baidu.com/tutorial.html#ECharts%20%E4%B8%AD%E7%9A%84%E4%BA%8B%E4%BB%B6%E5%92%8C%E8%A1%8C%E4%B8%BA
 * @param string title
 * @param string dom_id
 * @param array data
 * @param object handler 支持 'click', 'dblclick', 'mousedown', 'mousemove', 'mouseup', 'mouseover', 'mouseout'
 * @return object echarts
 */
function setBatchBarChart(title, dom_id, data, handler) {
    var myChart = echarts.init(document.getElementById(dom_id));
    var legend_data = data.legend;
    var xAxis_data = data.data;
    var series_data = data.series;

    var option = {
        title: {
            text: title
        },
        tooltip : {
            trigger: 'axis',
            axisPointer : { // 坐标轴指示器，坐标轴触发有效
                type : 'shadow' // 默认为直线，可选为：'line' | 'shadow'
            }
        },
        legend: {
            data: legend_data
        },
        xAxis: {
            type : 'category',
            data: xAxis_data,
        },
        yAxis: {},
        series: series_data,
        color: ['#c23531', '#546570', '#c4ccd3', '#ca8622', '#61a0a8', '#2f4554', '#d48265', '#91c7ae', '#749f83', '#bda29a', '#6e7074']
    };

    console.log(option);

    // 为echarts对象加载数据
    myChart.setOption(option);
    if ($.isPlainObject(handler)) {
        $.each(handler, function(evt, cb) {
            if ($.isFunction(cb) ) {
                myChart.on(evt, cb);
            }
        });
    }

    return myChart;
}