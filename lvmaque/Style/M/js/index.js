itz.userIndex = {},
itz.userIndex.init = function(indexData) {
//    this.infoList(indexData.infoListUrl),
//    this.protocolTips(indexData.protocolUrl),
//    this.transaction(indexData),
//    this.repay(indexData),
    this.creatChart(indexData, 3)
    this.bindEvents(indexData)
},
itz.userIndex.bindEvents = function(userIndexData) {
//    $.cookie("newUserGuide", "false"),
//    $.cookie("newUserGuide-14314") || $("#NewUserGuide").dialog({
//        dialogClass: "clearPop pop-style-1",
//        bgiframe: !0,
//        modal: !0,
//        resizable: !1,
//        closeOnEscape: !1,
//        show: {
//            effect: "fadeIn",
//            duration: 450
//        },
//        width: 610,
//        height: 510,
//        open: function() {
//            $.cookie("newUserGuide-14314", "true")
//        }
//    }),
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
itz.userIndex.lineChartMonthSwitch = function(userIndexData) {
   var that = this,
    $container = $("#container");
    $("#conLine .classify-selected-1-click a").click(function() {
        var $this = $(this);
       if ($this.hasClass("selected")) return ! 1;
        $("#conLine .classify-selected-1-click a").removeClass("selected"),
        $this.addClass("selected");
        var type = parseInt($this.attr("_type"));
        $.ajax({
            url: userIndexData.lineUrl,
            type: "GET",
            dataType: "json",
            data: {
                type: type
            },
            beforeSend: function() {
                $container.html('<img src="' + userIndexData.loadImgPath + '" style="margin:120px 45%"/>')
            },
            success: function(data) {
                if (0 === data.code) {
                    var d = data.data;
					that.creatChart(d, type)
                } else $container.html('<span style="display: inline-block; width: 199px; margin: 100px 35%;">网络出错啦，请重试</span>')
            },
            error: function() {


//仅供静态测试，做程序时请删除
//					d = {
//								line1 : [{y:14, color:'#E25353', w:20},
//							   {y:52, color:'#FCA352', w:30},
//							   {y:200, color:'#417CA9', w:200},
//							   {y:1, color:'#AA95D5', w:500},
//							   {y:110, color:'#6BB734', w:40}
//					],//已过去的灰色曲线
//								line2 : [           {y:8, color:'#E25353', w:5},
//							   {y:5, color:'#FCA352', w:36},
//							   {y:500, color:'#417CA9', w:60},
//							   {y:1, color:'#AA95D5', w:5},
//							   {y:100, color:'#6BB734', w:2}
//					]},
                    that.creatChart(d, type)
//仅供静态测试，做程序时请删除



            //    $container.html('<span style="display: inline-block; width: 199px; margin: 100px 35%;">网络出错啦，请重试</span>')
            },
            complete: function() {}
        })
    })
},
itz.userIndex.creatChart = function(userIndexData, type) {
    var iIsnull = function(data) {
        return data && data.length > 0 ? !1 : !0
    };
    if (iIsnull(userIndexData.line1) && iIsnull(userIndexData.line2)) return $("#container img").remove(), //`mxl 20150310`hide
	//if (iIsnull(userIndexData.series)) return $("#container img").remove(), //`mxl 20150310`
    $("#container").addClass("user-jilu0"),
    void 0;
    var line1Option = {
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
                    typeStr = "还款总额"
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
        plotOptions: {
            line: {
                point: {
                    events: {
                        click: function() { ! this.y > 0 || (location.href = "/member/borrowin?year=" + new Date(this.x).getFullYear() + "&month=" + (new Date(this.x).getMonth() + 1))
                        }
                    }
                },
                events: {
                    legendItemClick: function() {
                        return ! 1
                    }
                }
            },
            series: {
                lineWidth: 2
            }
        },
        series:  //`mxl 20150310`
		 [{
            name: "已收",
            color: "#c4c4c4",
            marker: {
                radius: 3
            },
			pointStart: Date.UTC(2015, 1, 10),
			pointInterval: 3600 * 1000*24*30,
            data: userIndexData.line1
        }
		,
        {
            name: "待收",
            color: "#fe6e00",
            marker: {
                radius: 3,
                symbol: "circle"
            },
			pointStart: Date.UTC(2015, 1, 10),
			pointInterval: 3600 * 1000*24*30,
            data: userIndexData.line2
        }
		] //`mxl 20150310`hide
    };
    Highcharts.dateFormats = {
        w: function(timestamp) {
            var date = new Date(timestamp),
            month = date.getMonth() + 1;
            return month
        }
    },
    new Highcharts.Chart(line1Option)
},
itz.userIndex.infoList = function(url) {
    $("#User_inform").find(".user-inform-know"),
    $("#User_inform").delegate(".user-inform-know", "click",
    function() {
        var that = $(this);
        return $.ajax({
            url: url + "?message_id=" + that.attr("data-id") || "",
            type: "get",
            dataType: "json",
            success: function(data) {
                that.parent().fadeOut(function() {
                    0 == data.code ? ($("#User_inform").append('<li class="user-inform-message" style="display: none;"><span class="user-inform-con">' + data.data[0].name + '<a href="/newuser/main/message?status=unread&id=' + data.data[0].id + '" target="_blank">查看详细</a></span><a href="###" data-id="' + data.data[0].id + '" class="user-inform-know">我知道了</a></li>'), that.parent().next().fadeIn()) : ($("#User_inform").css({
                        marginBottom: "0px"
                    }), $("#User_inform").animate({
                        height: "0px"
                    },
                    230))
                })
            }
        }),
        !1
    })
},
itz.userIndex.protocolTips = function(url) {
    var protocolTips = $("#User_protocol_tips"),
    endTpl = ['<h1><span class="important-ico"></span>签署成功：</h1><p class="user-protocol-message">您已成功签署《债权转让协议》，协议附件已发送至您的验证邮箱。</p><a class="btn-style-5 user-protocol-submit" href="', "", '">下载协议</a>'];
    protocolTips.delegate(".user-protocol-submit", "click",
    function() {
        if ($("#user-protocol-agree:checked").length) {
            var borrowId = protocolTips.attr("data-borrow_id"),
            $_this = $(this);
            $("#buyBackError").text(""),
            $_this.text("正在签署..."),
            $.ajax({
                url: url,
                type: "post",
                data: {
                    borrow_id: borrowId,
                    status: 1
                },
                dataType: "json",
                success: function(data) {
                    if (0 !== data.code || data.info.length > 1) return alert(data.info),
                    $_this.text("签署协议"),
                    void 0;
                    var url = data.data.url;
                    endTpl[1] = url,
                    protocolTips.removeClass("user-protocol-tips-begin").addClass("user-protocol-tips-end").html(endTpl.join(""))
                }
            })
        } else $("#buyBackError").text("请勾选协议")
    })
},
itz.userIndex.repay = function(userIndexData) {
    var pageFn = doT.template(document.getElementById("userTransactl_1").text, void 0);
    userIndexData.date.day;
    var transactionAjaxA = function(type, dataType, txt) {
        $.ajax({
            url: userIndexData.repayUrl,//"/newuser/ajax/getUserCurrentCase"//改链接 `mxl 20150307`
            type: "POST",
            dataType: "json",
            data: {
                year: userIndexData.date.year,
                month: userIndexData.date.month,
                day: userIndexData.date.day,
				limit: userIndexData.repay_limit//增加 `mxl 20150307`
            },
            success: function(data) {
                var dataValue;
                0 == data.code ? (repayData = data.data, dataValue = repayData.length > 0 ? repayData: ["", txt ? txt + "记录": "还款计划"]) : dataValue = 0,
                $("#userTransactHtml_1").html(pageFn(dataValue)),
                $("#lodingGif_1").length > 0 && $("#lodingGif_1").remove()
            }
        })
    };
    transactionAjaxA("", "json")
},
itz.userIndex.transaction = function(userIndexData) {
    var ajaxFun, transactionData = {},
    pageFn = doT.template(document.getElementById("userTransactl").text, void 0),
    transactionAjax = function(type, dataType, txt) {
        ajaxFun = $.ajax({
            url: userIndexData.transactionUrl,
            type: "GET",
            dataType: "json",
            data: {
                type: type,
                dataType: dataType,
                limit: userIndexData.transaction_limit
            },
            success: function(data) {
                var dataValue;
                0 == data.code ? (transactionData = data.data.accountLogInfo, dataValue = data.data.accountLogInfo.length > 0 ? data.data.accountLogInfo: ["", txt ? txt + "记录": "交易记录"]) : dataValue = 0,
                $("#userTransactHtml").html(pageFn(dataValue)),
                $("#lodingGif").length > 0 && $("#lodingGif").remove()
            }
        })
    };
    $("#conTrade .classify-selected-1-click a").click(function() {
        if ($(this).hasClass("selected")) return ! 1;
        $("#conTrade .classify-selected-1-click a").removeClass("selected");
        var id = $(this).addClass("selected").attr("data-id");
        ajaxFun.abort(),
        transactionAjax(id, "json", $(this).text())
    }),
    transactionAjax("", "json")
};