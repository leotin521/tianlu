itz.recharge = {},
itz.recharge.init = function(rechargeData) {
    if (1 != window.User.real_status || 1 != window.User.phone_status || 1 != window.User.payPwd_status) {
        var staticTxt = [];
        1 != window.User.real_status && staticTxt.push("实名认证"),
        1 != window.User.phone_status && staticTxt.push("手机认证"),
        1 != window.User.payPwd_status && staticTxt.push("支付密码设置")
    }
    jQuery.validator.addMethod("toFixed2",
    function(value, element) {
        return this.optional(element) || /^\d+(.\d{1,2})?$/.test(value)
    },
    "小数点后至多两位"),
    $(".user-pay .user-pay-list ul li").hover(function() {
        $(this).addClass("hover")
    },
    function() {
        $(this).removeClass("hover")
    }),
    jQuery.validator.addMethod("vcode",
    function(value) {
        return /^[\da-zA-Z]{4}$/.test(value)
    },
    "请输入正确的验证码"),
    $("#valicodeImg").click(function() {
        $("#valicodeImg").show()[0].src = "/Member/Withdraw/verify?t=" + Math.random()
    }),
    $('[name="valicode"]').one("focus",
    function() {
        $(this).removeAttr("placeholder"),
        $("#valicodeImg").show()[0].src = "/Member/Withdraw/verify?t=" + Math.random()
    }),
    this.rechargeForm(),
    this.bindBtns(rechargeData)
},
$.validator.setDefaults({
    errorClass: "form-style-2-error"
}),
itz.recharge.bindBtns = function(rechargeData) {
		var $selectThird = $("#selectThird"),
		$latestRecharge = $("#latestRecharge"),
		$selectBank = $("#selectBank");
	    $quotaContainer = $("#quotaContainer");
		$latestRecharge.find("li label").click(function() {
        var $this = $(this);
        $quotaContainer.hide(),
        $("#selectBank").slideUp(),
        $("#selectBank").find(".selected").removeClass("selected"),
        $selectThird.find(".selected").removeClass("selected"),
        $this.parent().addClass("selected"),
        $this.attr("_bankId") ? ($("#payment_id").val(""), $("#bank_payment_id").val($("#selectBank .selected").attr("_bankId"))) : ($("#bank_payment_id").val(""), $("#payment_id").val($this.attr("_thirdId"))),
        $("#bankError").hide()
    }),
    $selectThird.delegate("label", "click",
    function() {
        var $this = $(this),
        $selectBank = $("#selectBank");
        $selectThird.find(".selected").removeClass("selected"),
        $latestRecharge.find("li").removeClass("selected"),
        $(this).parent().addClass("selected"),
        !("payoff" === $this.attr("id")) ? ($selectBank.slideDown(), $("#bank_payment_id").val($("#selectBank .selected").attr("_bankId")), $("#payment_id").val($this.attr("_thirdId")), $selectBank.find("li").removeClass("selected"), currentBank = "") : ($selectBank.slideUp(), $selectBank.find("li").removeClass("selected"), $("#payment_id").val($this.attr("_thirdId")), $("#bank_payment_id").val(""), $("#bankError").hide())
    }),
    $("#moreSelectBtn").click(function() {
        $(this).hide(),
        $selectBank.slideDown(),
        $selectThird.slideDown(),
        $latestRecharge.find("li").removeClass("selected"),
        $("#bank_payment_id").val(""),
        $("#payment_id").val("")
    }),
    $("#moreBankBtn").click(function() {
        var $this = $(this);
        $this.parent().find("li:hidden").fadeIn(),
        $this.hide()
    });
    var currentBank, $rechargeForm = $("#rechargeForm");
    $rechargeForm.delegate(".isBank label", "click",
    function() {
        var $this = $(this);
        $this.attr("_bankId") === currentBank || $this.attr("_latest") || (currentBank = $this.attr("_bankId"))
    }),
    $selectBank.delegate("label", "click",
    function() {
        var $this = $(this);
        $selectBank.find(".selected").removeClass("selected"),
        $selectThird.find(".selected.ispayment").removeClass("selected"),
        $latestRecharge.find(".selected").removeClass("selected"),
        $this.parent().addClass("selected"),
        $("#bank_payment_id").val($this.attr("_bankId")),
        $("#payment_id").val($("#selectThird .selected label").attr("_thirdId")),
        $("#bankError").hide()
    })
},
itz.recharge.rechargeForm = function() {
    $("#rechargeForm").validate({
        errorPlacement: function(error, element) {
            element.parents(".form-style-1-item").find(".form-style-1-error").html(error)
        },
        submitHandler: function(form) {
            var pagefn, $submitBtn = $("#rechargeSubmit"),
            $payPrompt = $("#Pay_prompt");
            return $("#payment_id").val() || $("#bank_payment_id").val() ? ($submitBtn.attr("disabled", !0).val("充值中..."), pagefn = doT.template(document.getElementById("promptTmpl").text, void 0), $payPrompt.html(pagefn([])), $payPrompt.dialog({
                dialogClass: "clearPop pop-style-1",
                bgiframe: !0,
                modal: !0,
                resizable: !1,
                width: 460,
                close: function() {
                    $submitBtn.removeAttr("disabled").val("充值"),
                    $("#rechargeForm input[name=valicode]").val(""),
                    $("#valicodeImg").show()[0].src = "/Member/Withdraw/verify?t=" + Math.random()
                }
            }), form.submit(), void 0) : ($("#bankError").text("请选择一种充值方式~").show(), !1)
        },
        rules: {
            payment: {
                required: !0
            },
            bank_id: {
                required: !0
            },
            money: {
                required: !0,
                number: !0,
                toFixed2: !0,
                min: .01,
                max: 5000000
            },
            valicode: {
                required: !0,
                vcode: !0
            },
            importantInfo: {
                required: !0
            }
        },
        messages: {
            payment: {
                required: "* 请选择支付方式"
            },
            bank_id: {
                required: "* 请选择银行"
            },
            money: {
                required: "* 请填写充值金额",
                number: "* 金额必须为数字",
                toFixed2: "* 充值金额最多2位小数",
                min: "* 充值金额需要大于0.01",
                max: "* 不能超过最大充值金额5000000"
            },
            valicode: {
                required: "* 请填写验证码"
            },
            importantInfo: {
                required: "* 请阅读下方提示并勾选"
            }
        }
    }),
    $("#pay-question").click(function() {
        $("#after-submit-tips").dialog("close")
    }),
    $("body").delegate(".ps1-close", "click",
    function() {
        var id = $(this).parents(".ui-dialog-content").attr("id");
        $("#" + id).dialog("close")
    })
};