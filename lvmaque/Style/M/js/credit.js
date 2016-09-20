itz.credit = {},
itz.credit.init = function(creditData) {
    $("body").delegate(".ps1-close", "click",
    function() {
        var id = $(this).parents(".ui-dialog-content").attr("id");
        $("#" + id).dialog("close")
    }),
    itz.util.ajaxPager({
        pager: creditData.pager,
        ajaxHostUrl: creditData.ajaxHostUrl,
        loadImgPath: creditData.loadImgPath,
        container: "pagerContainer",
        pagination: "pagination1"
    }),
    this.exchangeEvents(creditData),
    this.exchangeCreditForForum()
},
itz.credit.exchangeEvents = function(creditData) {
    var spinnerBinded;
    $("#exchangeList").delegate("input", "click",
    function() {
        var $that = $(this),
        _needCredit = $that.attr("_needCredit"),
        _ownCredit = $that.attr("_ownCredit"),
        exchangeMax = Math.floor(_ownCredit / _needCredit);
        $("#Integral_promp_tname").text($(this).parents(".user-integral-item-con").find(".user-integral-item-name").text()),
        $("#Integral_prompt").dialog({
            dialogClass: "clearPop pop-style-1",
            bgiframe: !0,
            modal: !0,
            resizable: !1,
            width: 460,
            open: function() {
                var $t = $(this),
                $spinner = $t.find("#spinner");
                $spinner.val(1),
                $t.find("#goodid").val($that.attr("_goodId")),
                $t.find("#creditText").html(_needCredit + "积分(您拥有" + _ownCredit + "积分)"),
                spinnerBinded || ($spinner.spinner({}), spinnerBinded = 1),
                $spinner.spinner("option", "max", exchangeMax).spinner("option", "min", "1")
            }
        })
    }),
    $("body").delegate(".promptBtns", "click",
    function() {
        var $t = $(this);    
        switch ($t.attr("id")) {
        case "submitBtn":
            $.ajax({
                url:
                creditData.CreditExchangeAjaxUrl,
                type: "post",
                dataType: "json",
                data: $("#exchangeForm").serialize() + "&confirm=yes",
                beforeSend: function() {
                    $t.attr("disabled", !0).val("兑换中...")
                },
                success: function(data) { 
                    0 === data.code ? (itz.util.promptA("Drawings_prompt", "promptTmpl", ["积分兑换", "恭喜您兑换成功~", "", 3]), location.reload()) : itz.util.promptA("Drawings_prompt", "promptTmpl", ["密码保护提示", "兑换失败！", "原因：" + data.info + "<br/>", 0]),
                    $t.removeAttr("disabled").val("兑换")
                },
                error: function() {
                    alert("由于网络原因，保存失败！您可以点击兑换重试，或联系网站客服"),
                    $t.removeAttr("disabled").val("兑换")
                }
            });
            break;
        case "cancelBtn":
            var id = $t.parents(".ui-dialog-content").attr("id");
            $("#" + id).dialog("close");
            break;
        case "closeBtn":
            var id = $t.parents(".ui-dialog-content").attr("id");
            $("#" + id).dialog("close")
        }
    })
},
itz.credit.exchangeCreditForForum = function() {
    var totalCount = 0,
    leftCount = 0;
    $("#creditForForum").click(function() {
        $("#forum_prompt").dialog({
            dialogClass: "clearPop pop-style-1",
            bgiframe: !0,
            modal: !0,
            resizable: !1,
            width: 460,
            open: function() {
                $("#loadForForum").show(),
                totalCount = 0,
                leftCount = 0,
                $("input.forum_promptBtns#submitBtn").removeAttr("disabled"),
                $("#forum_count,#forum_count_left").html(0),
                $("#spinner_content").html(""),
                $.ajax({
                    url: "/newuser/Ajax/getJbFromForum",
                    type: "GET",
                    cache: !1,
                    success: function(data) {
                        $("#loadForForum").hide();
                        var d = data.data;
                        if (0 == data.code) {
                            var userCredit = parseInt(d.userCredit);
                            if (0 >= userCredit) $("input.forum_promptBtns#submitBtn").attr("disabled", !0),
                            $("#spinner_content").html("<span style='color:red;'>您的金币数量不足，无法兑换</span>");
                            else if (500 > userCredit) $("input.forum_promptBtns#submitBtn").attr("disabled", !0),
                            $("#spinner_content").html("<span style='color:red;'>您的金币不足500，无法兑换</span>"),
                            totalCount = userCredit,
                            leftCount = totalCount,
                            $("#forum_count").html(userCredit),
                            $("#forum_count_left").html(leftCount);
                            else {
                                totalCount = userCredit,
                                leftCount = 0,
                                $("#forum_count").html(userCredit),
                                $("#forum_count_left").html(0),
                                $("#spinner_content").html('<input id="forum_spinner" name="amount" readonly type="text" class="input-text-style-1" style="width:120px;" value="100"/>');
                                var $spinner = $("#forum_spinner");
                                $spinner.val(100 * userCredit),
                                $spinner.spinner({
                                    min: 100,
                                    max: 100 * userCredit,
                                    step: 100,
                                    spin: function(event, ui) {
                                        $("#forum_count_left").html(userCredit - ui.value / 100),
                                        leftCount = userCredit - ui.value / 100
                                    }
                                })
                            }
                        } else alert("由于网络原因，获取论坛金币失败！您可以重试，或联系网站客服")
                    },
                    error: function() {
                        $("#loadForForum").hide(),
                        alert("由于网络原因，获取论坛金币失败！您可以重试，或联系网站客服")
                    }
                })
            }
        })
    }),
    $(".forum_promptBtns").click(function() {
        var $t = $(this);
        switch ($t.attr("id")) {
        case "submitBtn":
            if (0 >= totalCount) break;
            $.ajax({
                url: "/newuser/Ajax/exchangeForForum",
                type: "post",
                data: {
                    minus: totalCount - leftCount
                },
                beforeSend: function() {
                    $("#loadForForum").show(),
                    $t.attr("disabled", !0).val("兑换中...")
                },
                success: function(data) {
                    $("#loadForForum").hide(),
                    0 === data.code ? (itz.util.promptA("Drawings_prompt", "promptTmpl", ["积分兑换", "恭喜您兑换成功~", "", 1]), setTimeout(function() {
                        location.reload()
                    },
                    1e3)) : itz.util.promptA("Drawings_prompt", "promptTmpl", ["积分兑换", "兑换失败！", "原因：" + data.info + "<br/>", 0]),
                    $t.removeAttr("disabled").val("兑换")
                },
                error: function() {
                    $("#loadForForum").hide(),
                    alert("由于网络原因，积分兑换失败！您可以重试，或联系网站客服"),
                    $t.removeAttr("disabled").val("兑换")
                }
            });
            break;
        case "cancelBtn":
            var id = $t.parents(".ui-dialog-content").attr("id");
            $("#" + id).dialog("close")
        }
    })
};