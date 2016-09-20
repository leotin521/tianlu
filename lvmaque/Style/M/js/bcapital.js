itz.userIndex = {},
itz.userIndex.init = function(indexData) {
    this.creatChart(indexData, 3)
    this.bindEvents(indexData)
},
itz.userIndex.bindEvents = function(userIndexData) {
   $(".pro_tips-1").tip({
        words_per_line: 1e3
    }),
   this.lineChartMonthSwitch(userIndexData),
    $(".icon-xiala").poshytip({
        alignY: "bottom",
        showTimeout: 100,
        liveEvents: !0,
        content: function(updateCallback) {
            var $this = $(this),
            transid = $this.attr("_transid"),
            type = $this.attr("_type"),
            cid = $this.attr("_cid");
            return $.ajax({
                url: userIndexData.tipAjaxUrl + "?transid=" + transid + "&type=" + type + "&cid=" + cid,
                dataType: "html",
                success: function(data1) {
                    updateCallback(data1)
                }
            }),
            "拼命加载中..."
        }
    })
},
itz.userIndex.creatChart = function(userIndexData, type) {
    var iIsnull = function(data) {
        return data && data.length > 0 ? !1 : !0
    };
    if (iIsnull(userIndexData.line1) && iIsnull(userIndexData.line2)) return $("#container img").remove(), 
    $("#container").addClass("user-jilu0"),
    void 0;
  /*待还*/
 var lineDaihaiOption = {
        credits: {
            enabled: !1
        },
        chart: {
            renderTo: "container"
        },
        title: {
            text: ""
        },
        xAxis: {
            type: "datetime",
            minRange: 2592e6,
            minTickInterval: 2592e6,
            labels: {
                style: {
                    color: "#bfbfbf"
                },
                formatter: function() {
                    return Highcharts.dateFormat("%y年%w月", this.value)
                },
                x: -5,
                y: 20
            }
        },
        yAxis: {
            title: {
                text: ""
            },
            labels: {
                style: {
                    color: "#bfbfbf"
                },
                formatter: function() {
                    return Highcharts.numberFormat(this.value, 0, ".") + "元"
                },
                y: -2
            },
            gridLineWidth: 0,
            lineWidth: 1,
            tickWidth: 1,
            min: 0
        },
        tooltip: {
            formatter: function() {
                var typeStr = "";
                switch (type) {
                case 1:
                    typeStr = "收益总额";
                    break;
                case 2:
                    typeStr = "还本总额";
                    break;
                case 3:
                    typeStr = "待还总额"
                }
                return Highcharts.dateFormat("%y年%w月", this.x) + "<br/>" + typeStr + '<span style="color:#fe6e00">' + Highcharts.numberFormat(this.y, 2, ".") + "</span>元"
            }
        },
        legend: {
            enabled: !0,
            layout: "horizontal",
            align: "center",
            verticalAlign: "top",
            x: 270,
            y: -3,
            borderWidth: 0,
            symbolWidth: 30,
            itemStyle: {
                cursor: "default"
            }
        },
        series: 
            [	
                {
                    name: "待还",
                    color: "#fe6e00",
                    marker: {
                        radius: 3,
                        symbol: "circle"
                    },
                    pointStart: Date.UTC(2015, 1, 10),
                    pointInterval: 3600 * 1000*24*30,
                    data: userIndexData.line2
                }
            ] 
    };
/*待还*/
    Highcharts.dateFormats = {
        w: function(timestamp) {
            var date = new Date(timestamp),
            month = date.getMonth() + 1;
            return month
        }
    },
     new Highcharts.Chart(lineDaihaiOption);
}
