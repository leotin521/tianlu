itz.coupon = {},
itz.coupon.init = function(couponData) {
    this.orderby(),
    itz.util.ajaxPager({
        pager: couponData.pager,
        ajaxHostUrl: couponData.ajaxHostUrl,
        paramsUrl: couponData.paramsUrl,
        loadImgPath: couponData.loadImgPath,
        container: "pagerContainer",
        pagination: "pagination"
    }),
    $("body").delegate(".ps1-close", "click",
    function() {
        var id = $(this).parents(".ui-dialog-content").attr("id");
        $("#" + id).dialog("close")
    })
},
itz.coupon.orderby = function() { 
    function sendUrl(order) { 
        if (order) {
            var params = [],
            status = $.getQueryString("status"),
            borrow_type = $.getQueryString("borrow_type");
            status && params.push("status=" + status),
            borrow_type && params.push("borrow_type=" + borrow_type),
            params.push("order=" + order),
            location.href = location.pathname + "?" + params.join("&") + "#coupontop"
        }
    }
    $("#status-orderby").find("a").click(function() { 
        var that = $(this),
        order = 7;   
        order = "获得时间" == ($.trim(that.text())+"") ? that.find(".icon-up-xia").length > 0 ? 7 : 8 : "过期时间" == ($.trim(that.text())+"") ? that.find(".icon-up-xia").length > 0 ? 3 : 4 : that.find(".icon-up-xia").length > 0 ? 5 : 6,  
        sendUrl(order)
    })
};