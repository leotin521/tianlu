itz.account = {},
itz.account.init = function(accountData) {
    var that = this;
    this.pieChart(accountData)
//    this.lineChart(accountData),
//    $("#tab2").itzTabs({
//        clickFn: function(i) {
//            0 === i ? that.bar("bar1", accountData.bar1) : that.bar("bar1", accountData.bar11)
//        }
//    }),
//    $("#tab3").itzTabs({
//        clickFn: function(i) {
//            0 === i ? that.bar("bar2", accountData.bar2) : that.bar("bar2", accountData.bar22)
//        }
//    })
},
itz.account.bindLeftLi = function($li, chartObj, fn1, fn2) {
    $li.mouseenter(function() {
        var $t = $(this);
        "1" != $t.attr("_disabled") && ($t.animate({
            marginLeft: "25px"
        },
        {
            duration: 500,
            queue: !1,
            specialEasing: {
                marginLeft: "easeOutCubic"
            }
        }), fn1 && fn1($t, chartObj))
    }).mouseleave(function() {
        var $t = $(this);
        $t.animate({
            marginLeft: "0px"
        },
        {
            duration: 500,
            queue: !1,
            specialEasing: {
                marginLeft: "easeOutCubic"
            }
        }),
        fn2 && fn2($t, chartObj)
    })
},
itz.account.chartSelect = function($t, pieObj) {
    for (var points = pieObj.series[0].points, len = points.length, i = 0; len > i; i++)"1" != $t.attr("_disabled") && points[i].name === $t.find(".user-property-money-name").text() && points[i].firePointEvent("click")
},
itz.account.lisSelect = function($lis, point) {
    $lis.each(function(index, ele) {
        var $ele = $(ele);
        $ele.find(".user-property-money-name").text() === point.name && "1" != $ele.attr("_disabled") && (point.selected ? $ele.animate({
            marginLeft: "25px"
        },
        {
            duration: 500,
            queue: !1,
            specialEasing: {
                marginLeft: "easeOutCubic"
            }
        }) : $ele.animate({
            marginLeft: "0px"
        },
        {
            duration: 500,
            queue: !1,
            specialEasing: {
                marginLeft: "easeOutCubic"
            }
        }))
    })
},
itz.account.pieChart = function(accountData) {
    for (var static = !1,
    i = 0; i < accountData.pieData.length; i++) for (var j = 0; j < accountData.pieData[i].length; j++) if (0 != accountData.pieData[i][1]) {
        static = !0;
        break
    }
    if (0 == static) return $("#pie").html("<div class='user-value-none-div'>暂无资产总计</div>"),
    !1;
    for (var temp, pieData = accountData.pieData,
    total = 0,
    renderData = [], tooltipData = [], i = 0; i < pieData.length; i++) total += pieData[i][1];
    for (var i = 0; i < pieData.length; i++) temp = pieData[i][1] / total,
    tooltipData.push([pieData[i][0], 100 * temp, pieData[i][1]]),
    temp > 0 && (.006 > temp ? temp = .006 : .01 > temp && (temp = .01)),
    renderData.push([pieData[i][0], temp]);
    var timeout, lisSelect = this.lisSelect,
    pieObj = new Highcharts.Chart({
        credits: {
            enabled: !1
        },
        chart: {
            renderTo: "pie",
            height: 240,
            plotBackgroundColor: null,
            plotBorderWidth: 0,
            plotShadow: !1
        },
        title: {
            text: ""
        },
        tooltip: {
            formatter: function() {
                return this.key + "：" + tooltipData[this.point.x][2] + "元，资产占比：" + tooltipData[this.point.x][1].toFixed(2) + "%"
            }
        },
        plotOptions: {
            pie: {
                animation: !1,
                allowPointSelect: !0,
                stickyTracking: !1,
                colors: ["#FF6E01", "#00B966", "#00A0FE", "#FF002A", "#9B9B9B"],
                center: ["50%", "45%"],
                dataLabels: {
                    enabled: !1
                },
                point: {
                    events: {
                        mouseOver: function() {
                            var point = this;
                            point.selected || (timeout = setTimeout(function() {
                                point.firePointEvent("click"),
                                lisSelect($("#pieUl li"), point)
                            },
                            200))
                        },
                        mouseOut: function() {
                            var point = this;
                            clearTimeout(timeout),
                            point.selected && point.firePointEvent("click"),
                            lisSelect($("#pieUl li"), point)
                        }
                    }
                }
            }
        },
        series: [{
            type: "pie",
            name: "",
            innerSize: "60%",
            data: renderData
        }]
    }),
    that = this;
    that.bindLeftLi($("#pieUl li"), pieObj, that.chartSelect, that.chartSelect)
},
$.fn.itzTabs = function(options) {
    var opt = {};
    options && (opt = $.extend({},
    opt, options)),
    this.each(function() {
        function reset(i) {
            $items.each(function(j, item) {
                var $item = $(item);
                return i === j ? ($item.addClass("current"), opt.clickFn && opt.clickFn(i), void 0) : ($item.removeClass("current"), void 0)
            }),
            $cons.each(function(j, item) {
                var $item = $(item);
                return i === j ? ($item.fadeIn(), void 0) : ($item.hide(), void 0)
            })
        }
        var $this = $(this),
        $items = $this.find("a"),
        $p = $this.parent(),
        $cons = $p.find($this.attr("data-container"));
        $items.each(function(i, item) {
            $(item).click(function() {
                var $t = $(this);
                $t.hasClass("current") || reset(i)
            })
        })
    })
};