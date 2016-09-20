itz.withdrawlog = {},
itz.withdrawlog.init = function() {
    this.bindDate(),
    this.cancel()
},
itz.withdrawlog.cancel = function() {
    var ajaxId = 0,
    id = 0;
    $(".closeBtn").click(function() {
        $("#user_drawings_cancel").dialog("close")
    }),
    $("#cancelBtn").click(function() {
        var ele = ($(this), $("[data-id=" + id + "]"));
        ajaxId && ajaxId.abort(),
        ele.text("提交中").attr("data-static", "false"),
        $("#user_drawings_cancel").dialog("close"),
        ajaxId = $.ajax({
            url: "/newuser/ajax/withDrawCancel",
            type: "get",
            dataType: "json",
            data: {
                cash_id: id
            },
            timeout: 2e4,
            success: function(data) {
                0 == data.code ? (ele.text("成功").attr("data-static", "false"), window.location.reload()) : (ele.text(data.info).attr("data-static", "false"), window.location.reload())
            },
            error: function(jqXHR, textStatus) {
                return "timeout" == textStatus ? (ele.text("请求失败").attr("data-static", "true"), void 0) : void 0
            }
        })
    }),
    $(".cancel_withdraw").click(function() {
        var that = $(this);
        return $("#user_drawings_cancel").dialog({
            dialogClass: "clearPop pop-style-1",
            bgiframe: !0,
            modal: !0,
            resizable: !1,
            closeOnEscape: !1,
            show: {
                effect: "fadeIn",
                duration: 450
            },
            open: function() {
                id = that.attr("data-id")
            }
        }),
        !1
    })
},
itz.withdrawlog.bindDate = function() {
    function sendUrl(fromTime, toTime) {
        if (fromTime && toTime && (fromTime != oFromTime || toTime != oToTime)) {
            var params = ["time=null"];
            type = $.getQueryString("type");
            type && params.push("type=" + type),
            params.push("start_time=" + fromTime),
            params.push("end_time=" + toTime),
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