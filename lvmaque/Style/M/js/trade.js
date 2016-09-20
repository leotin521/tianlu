itz.trade = {},
itz.trade.init = function(tradeData) {
    this.bindDate(),
    $(".icon-xiala").poshytip({
        alignY: "bottom",
        showTimeout: 100,

        content: function() {
	        var $this = $(this),
	        transid = $this.attr("_transid"),
	        type = $this.attr("_type"),
	        cid = $this.attr("_cid");
	        return "<div>交易序号："+ cid + "</div><div>交易类型：" + type + "</div><div>交易对方："+ transid + "</div>";
        }

		/*  //content:"投资项目:22155",--静态测试数据      
			content: function(updateCallback) {
            var $this = $(this),
            transid = $this.attr("_transid"),
            type = $this.attr("_type"),
            cid = $this.attr("_cid");
            return $.ajax({
                url: tradeData.tipAjaxUrl + "?transid=" + transid + "&type=" + type + "&cid=" + cid,
                dataType: "html",
                success: function(data) {
                    updateCallback(data)
                }
            }),
            "拼命加载中..."
        }*/
    })
},
itz.trade.bindDate = function() {
    function sendUrl(fromTime, toTime) {
        if (fromTime && toTime && (fromTime != oFromTime || toTime != oToTime)) {
            var params = ["time=null"],
            type = $.getQueryString("type");
            type && params.push("type=" + type),
            params.push("from_time=" + fromTime),
            params.push("to_time=" + toTime),
            location.href = location.pathname + "?" + params.join("&")
        }
    }
    var $beginDate = $("#beginDate"),
    $endDate = $("#endDate"),
    oFromTime = $beginDate.val(),
    oToTime = $endDate.val();
    $.datepicker.setDefaults({
        dateFormat: "yy-mm-dd"
    }),
    $beginDate.datepicker({
        changeMonth: !0,
        onClose: function(selectedDate) {
            $endDate.datepicker("option", "minDate", selectedDate),
            sendUrl(selectedDate, $endDate.val())
        }
    }),
    $endDate.datepicker({
        changeMonth: !0,
        onClose: function(selectedDate) {
            $beginDate.datepicker("option", "maxDate", selectedDate),
            sendUrl($beginDate.val(), selectedDate)
        }
    })
};