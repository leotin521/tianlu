itz.withdraw = {},
itz.withdraw.init = function(withdrawData) {
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
    "小数点后不能多于两位"),
    jQuery.validator.addMethod("vcode",
    function(value) {
        return /^[\da-zA-Z]{4}$/.test(value)
    },
    "请输入正确的验证码"),
    jQuery.validator.addMethod("select",
    function(value) {
        var tV = $.trim(value);
        return "请选择" === tV || "" === tV ? !1 : !0
    },
    "请选择"),
    this.bindEvents(),
    this.selectOtherBank(withdrawData),
    this.addBankForm(withdrawData),
    this.withdrawalForm(withdrawData),
    this.getSmsVcode(withdrawData),

    $("#valicodeImg").click(function() {
        $("#valicodeImg").show()[0].src = "/Member/Withdraw/verify?t=" + Math.random()
    }),
    $('[name="valicode"]').one("focus",
    function() {
        $(this).removeAttr("placeholder"),
        $("#valicodeImg").show()[0].src = "/Member/Withdraw/verify?t=" + Math.random()
    }),
    this.getProvince(withdrawData),
    this.changeArea(withdrawData),
    $("#withdrawalForm").find(".pro_tips").poshytip({
        alignY: "bottom",
        showTimeout: 10
    })
},
itz.withdraw.getProvince = function(withdrawData) {
    $.ajax({
        url: withdrawData.getAreaUrl,
        type: "post",
        dataType: "json",
        data: {
            pid: "1"
        },
        success: function(data) {
            if (0 == data.code) {
                var pro = $("#province"),
                v = pro.data("v");
                pro.html(data.data),
                v && (setTimeout(function() {
                    pro.val(v)
                },
                0), $.ajax({
                    url: withdrawData.getAreaUrl,
                    type: "post",
                    dataType: "json",
                    data: {
                        pid: v
                    },
                    success: function(da) {
                        var ei = $("#city"),
                        v = ei.data("v");
                        ei.html(da.data),
                        v && setTimeout(function() {
                            ei.val(v)
                        },
                        0)
                    },
                    error: function() {}
                }))
            }
        },
        error: function() {}
    })
},
itz.withdraw.changeArea = function(withdrawData) {
    function ajaxGetArea(pid, element_id) {
        $.ajax({
            url: withdrawData.getAreaUrl,
            type: "post",
            dataType: "json",
            data: {
                pid: pid
            },
            success: function(data) {
                var ei = $(element_id),
                v = ei.data("v");
                ei.html(data.data),
                v && ei.val(v)
            },
            error: function() {}
        })
    }
    $("#province").change(function() {
        var val = $(this).val();
        return "请选择" === val ? ($("#city").html("<option>请选择</option>"), void 0) : (ajaxGetArea(val, "#city"), void 0)
    })
},
itz.withdraw.bindEvents = function() {
    $("#editBankBtn").click(function() {
        $("#latestWithdraw").hide(),
        $("#editBankCard").fadeIn()
    }),
    $("#addBankBtn").click(function() {
        itz.withdraw.resetForm(),
        $("#latestWithdraw").hide(),
        $("#addBankCard").fadeIn()
    }),
    $("#otherBankBtn").click(function() {
        $("#latestWithdraw").hide(),
        $("#selectBank").fadeIn()
    }),
    $("#cancelSelectBtn").click(function() {
        $("#selectBank").hide(),
        $("#latestWithdraw").fadeIn()
    }),
    $("body").delegate(".ps1-close", "click",
    function() {
        var id = $(this).parents(".ui-dialog-content").attr("id"),
        $id = $("#" + id);
        $id.dialog("close"),
        $id.find(".state-prompt-txt-duigou").length && location.reload()
    }),
    $(".user-drawings-bank dd").hover(function() {
        $(this).hasClass("user-drawings-add-bank") ? $(this).addClass("user-drawings-add-bank-hover") : $(this).addClass("hover")
    },
    function() {
        $(this).hasClass("user-drawings-add-bank") ? $(this).removeClass("user-drawings-add-bank-hover") : $(this).removeClass("hover")
    }),
    $("#province").add($("#city")).add($("#bank")).change(function() {
        var wrapper = $("#addBankCard");
        //wrapper.find('input[name="union_bank_id"]').val(""),
        wrapper.find('input[name="branch"]').val("")
    })
},
itz.withdraw.selectOtherBank = function(withdrawData) {
    var $selectBank = $("#selectBank");
    $selectBank.delegate("dd", "click",
    function() {
        var $this = $(this),
        selected = $this.html(),
        $latest = $("#latestWithdraw"),
        $latestCard = $latest.find("#latestCard"),
        cid = $this.attr("_cardId"),
        caccount = $this.find("span").eq(0).html();
        $.ajax({
            url: withdrawData.edtBankUrl,
            dataType: "json",
            data: {
                id: cid
            },
            success: function(msg) {
                if (msg && 0 == msg.code) {
                    var d = msg.data,
                    uid = d.union_bank_id ? !0 : !1,
                    wrap = $("#addBankCard");
                    if (itz.withdraw.tempV = {
                        unionid: uid,
                        branch: d.branch
                    },
                    uid) itz.withdraw.resetForm();
                    else {
                        wrap.find("#bankMPA").hide(),
                        wrap.find("#bankMPE").show(),
                        wrap.find("h3").html("编辑银行卡"),
                        wrap.find(".js_tips").show(),
                        wrap.find(".bankMPspan").html(d.branch),
                        wrap.find("#fbank").length || wrap.find("#bank").parent().append($("<input type=text style=color:#666 id=fbank class=cardNum>")),
                        wrap.find("#frbank").length || wrap.find("form").append($("<input id=frbank type=hidden name=bank>")),
                        wrap.find("#fbank").removeAttr("disabled").show().val(wrap.find('#bank option[value="' + d.bank + '"]').text()),
                        wrap.find("#frbank").removeAttr("disabled").val(d.bank),
                        wrap.find("#bank").attr("disabled", !0).hide(),
                        wrap.find("#cardNum").addClass("cardNum").attr("disabled", !0).val(caccount),
                        wrap.find('input[name="user_id"]').attr("disabled", !0),
                        wrap.find('input[name="id"]').length || wrap.find("form").append($("<input name=id type=hidden>")),
                        wrap.find('input[name="id"]').removeAttr("disabled").val(d.id),
                        wrap.find('input[name="branch"]').attr("placeholder", "请输入开户行关键词"),
                        wrap.find("#addBankSubmit").val("保存"),
                        wrap.find("#cancelAddBank").hide();
                        var pro = wrap.find("#province"),
                        v = d.province,
                        ei = wrap.find("#city");
                        $latest.hide(),
                        $("#addBankCard").fadeIn(),
                        v && (pro.val(v), $.ajax({
                            url: withdrawData.getAreaUrl,
                            type: "post",
                            dataType: "json",
                            data: {
                                pid: v
                            },
                            success: function(da) {
                                ei.html(da.data),
                                ei.val(d.city)
                            }
                        }))
                    }
                }
            }
        }),
        $selectBank.hide(),
        $latestCard.html(selected.replace('<a class="delBtn">删除</a>', "")).show(),
        $latest.fadeIn(),
        $("#bankSelected").val(cid),
        $("#bankError").hide()
    }),
    $selectBank.add($("#latestWithdraw")).find(".delBtn").click(function(e) {
    	var $this=$(this);
        return e.stopPropagation(),
        layer.confirm('您确定删除该银行卡吗？', {icon: 3}, function(index){
            layer.close(index);
            $.ajax({
                url: withdrawData.delBankUrl,
                type: "get",
                dataType: "json",
                data: {
                    id: $this.parent().attr("_cardid"),
                },
                success: function(data) {
                    1 === data.status ? location.reload() : itz.util.promptA("Drawings_prompt", "promptTmpl", ["银行卡提示", "删除失败！", "添加失败！" + (data.info ? "原因：" + data.info: "")], 0)
                },
                error: function() {
                    itz.util.promptA("Drawings_prompt", "promptTmpl", ["银行卡提示", "删除失败！", "添加失败！" + (data.message ? "原因：" + data.message: "")], 0)
                }
            }), !0
        });
        /*
        confirm("您确定删除该银行卡吗？") ? ($.ajax({
            url: withdrawData.delBankUrl,
            type: "get",
            dataType: "json",
            data: {
                id: $(this).parent().attr("_cardid")
            },
            success: function(data) {
                1 === data.status ? location.reload() : itz.util.promptA("Drawings_prompt", "promptTmpl", ["银行卡提示", "删除失败！", "添加失败！" + (data.info ? "原因：" + data.info: "")], 0)
            },
            error: function() {
                itz.util.promptA("Drawings_prompt", "promptTmpl", ["银行卡提示", "删除失败！", "添加失败！" + (data.message ? "原因：" + data.message: "")], 0)
            }
        }), !0) : void 0
        */
    })
},
itz.withdraw.resetForm = function() {
    var wrap = $("#addBankCard");
    wrap.find(".js_tips").hide(),
    wrap.find("#bankMPA").show(),
    wrap.find("#bankMPE").hide(),
    wrap.find("h3").html("添加银行卡"),
    wrap.find("form").get(0).reset(),
    wrap.find("#fbank").hide(),
    wrap.find("#frbank").attr("disabled", !0),
    wrap.find("#bank").removeAttr("disabled").show(),
    wrap.find("#cardNum").removeClass("cardNum").removeAttr("disabled").val(""),
    wrap.find('input[name="user_id"]').removeAttr("disabled"),
    wrap.find('input[name="id"]').attr("disabled", !0),
    wrap.find('input[name="branch"]').removeAttr("placeholder"),
    wrap.find("#addBankSubmit").val("添加"),
    wrap.find('input[name="union_bank_id"]').val(""),
    wrap.find("#cancelAddBank").show(),
    this.smsCountDown && this.smsCountDown.reset()
},
itz.withdraw.addBankForm = function(withdrawData) {

    var $add = $("#addBankCard");
    $.validator.addMethod("chinese",
    function(v, e) {
        return this.optional(e) || /^[\u4e00-\u9fa5]+$/.test(v)
    },
    "请输入中文");
    var validator = $add.find("form").validate({
        errorPlacement: function(error, element) {
            element.parent().find(".form-style-1-error").html(error)
        },
        submitHandler: function(form) {
            var tv = itz.withdraw.tempV,
            ui = $('input[name="union_bank_id"]');
            tv && "undefined" != typeof tv.unionid && tv.unionid == ui.val() && tv.branch && tv.branch != $('input[name="branch"]').val() && ui.val("");
			
            var $submitBtn = $("#addBankSubmit"),
            bankName = $('input[name="bank"]'),
            sflag = "保存" == $submitBtn.val() ? !1 : !0,
		    bid = $("#union_bank_id");
			bid.val(1);
            //return keyTmp[bankName.val()] && bid.val(keyTmp[bankName.val()]),
            bid.val() ? ($submitBtn.attr("disabled", !0).val(sflag ? "添加中...": "保存中..."), $.ajax({
                url: sflag ? withdrawData.addBankUrl: withdrawData.updBankUrl,
                type: "post",
                dataType: "json",
                //data: $(form).serialize(),
				data:{
				    bank_name: $("#bank").val(), bank_num: $("#cardNum").val(),
					bank_province: $("#province").val(),bank_city: $("#city").val(),
					bank_address: $("#branch").val(), vcode: $("#sms_vcode").val()
				},
                success: function(data) {
                    if (1 === data.status) {
                        var d = data.data;
                        form.reset(),
                        pagefn = doT.template(document.getElementById("cardTmpl").text, void 0),
                        $("#bankSelected").val(d.id),
                        $("#bankError").hide(),
                        $("#addBankCard").hide(),
                        $("#latestWithdraw").show(),
                        sflag && $("#latestCard").html(pagefn([d.account, d.img_src])).fadeIn(),
                        sflag && $("#selectBank").append($('<dd _cardId="' + d.id + '"></dd>').html(pagefn([d.account, d.img_src])));
                        var $otherBankBtn = $("#otherBankBtn").parent();
                        $otherBankBtn.is(":hidden") && $otherBankBtn.fadeIn();
                        window.location.reload();
                    } else itz.util.promptA("Drawings_prompt", "promptTmpl", ["银行卡提示", "添加失败！", "添加失败！" + (data.message ? "原因：" + data.message: "")], 0);
                    $submitBtn.removeAttr("disabled").val(sflag ? "添加": "保存")
                },
                error: function() {
                    itz.util.promptA("Drawings_prompt", "promptTmpl", ["银行卡提示", "添加失败！", "由于网络原因，提交失败！您可以点击添加重试，或联系网站客服~~"], 0),
                    $submitBtn.removeAttr("disabled").val(sflag ? "添加": "保存")
                }
            }), void 0) : (itz.util.promptA("Drawings_prompt", "promptTmpl", ["银行卡提示", $submitBtn.val() + "失败！", $submitBtn.val() + "失败！提示：请您务必在推荐列表中选择开户行，如您未找到符合关键词的支行，请选择列表推荐的开户行所在城市的银行分行，如您还有疑问可致电"+kePhone+"咨询。"], 0), !1)
        },
        rules: {
            account: {
                required: !0,
                digits: !0,
                rangelength: [15, 19]
            },
            city: {
                select: !0
            },
            branch: {
                required: !0,
                chinese: !0
            },
            sms_vcode: {
                required: !0
            }
        },
        messages: {
            account: {
                required: "请填写银行卡号",
                digits: "卡号为数字类型",
                rangelength: "银行卡位数一般为15-19位"
            },
            city: {
                select: "请填写银行卡开户城市"
            },
            branch: {
                required: "请填写开户行"
            },
            sms_vcode: {
                required: "请填写短信验证码"
            }
        }
    });
    $("#cardNum").keyup(function() {
        var $t = $(this),
        tVal = $.trim($t.val()),
        $cf = $("#cardFmt");
        tVal ? ($cf.html(function(v) {
            var l = v.length,
            temp = "";
            if (5 > l) return v;
            for (var i = 0; l > i; i++) {
                if (i + 1 > 19) return temp += "...";
                temp += v.charAt(i),
                0 === (i + 1) % 4 && i != l - 1 && (temp += " ")
            }
            return temp
        } (tVal)), $cf.is(":visible") || $cf.show()) : $cf.hide()
    }).blur(function() {
        $("#cardFmt").hide()
    }).focus(function() {
        $.trim($(this).val()) && $("#cardFmt").show()
    }),
    $("#cancelAddBank").click(function() {
    	window.location.href="/member/bank/";
    	/*
        $("#addBankCard").hide(),
        $("#latestWithdraw").fadeIn(),
        validator.resetForm()
        */
    });
    var khh = $('input[name="branch"]'),
    ubkid = $("#union_bank_id"),
    keyTmp = {};
    khh.length > 0 && khh.autocomplete({
        source: function(req, res) {
            var provi = ($("#addBankCard").find("form"), $("#province").val()),
            cit = $("#city").val(),
            branc = $('input[name="branch"]').val(),
            bnk = ($("#cardNum").val(), $("select#bank").is(":hidden") ? $("input#frbank").val() : $("select#bank").val());
            if ("请选择" == provi || "请选择" == cit) return ! 1;
            var dat = {
                province: provi,
                city: cit,
                branch: branc,
                bank: bnk
            };
            itz.withdraw.tempBranch && itz.withdraw.tempBranch != branc && ubkid.val(""),
            $.ajax({
                url: "/newuser/ajax/sug",
                data: dat,
                success: function(resp) {
                    if (resp && 0 == resp.code && resp.data.length > 0) {
                        var jarr = itz.util.parseArrayToJson(resp.data);
                        keyTmp = jarr.converse,
                        res(jarr.result)
                    }
                },
                error: function() {}
            })
        },
        select: function(e, ui) {
            ubkid.val(keyTmp[ui.item.label]),
            itz.withdraw.tempBranch = ui.item.label
        },
        delay: 300
    }).bind("focus",
    function() {
        $(this).autocomplete("search")
    })
},
itz.withdraw.withdrawalForm = function(withdrawData) {
    var account = withdrawData.account,
    subEnable = 1;
    $("#withdrawalForm").validate({
        errorPlacement: function(error, element) {
            error.appendTo(element.nextAll(".form-style-1-error"))
        },
        submitHandler: function(form) {
            var abc = $("#addBankCard");
            if ($("#bankMo"), !abc.is(":hidden") && abc.find("#cancelAddBank").is(":hidden")) return abc.find("form").submit(),
            !1;
            if ("" == $("#bankSelected").val()) return $("#bankError").text("请选择或添加可以提现的银行卡").show(),
            !1;
            var $submitBtn = $("#withdrawSubmit");
            $submitBtn.attr("disabled", !0).val("提交中..."),
            subEnable && (subEnable = 0, $.ajax({
                url: withdrawData.postUrl,
                type: "post",
                dataType: "json",
                data: {amount : $("#money").val() , pwd : $("#paypassword").val() , bank_id : $("#bankSelected").val(), valicode : $("#valicode").val()} , 
                success: function(data) {
                    1 === data.status ? (form.reset(), itz.util.promptA("Drawings_prompt", "promptTmpl", ["提现提示", "提现申请成功！", '若审核通过，预计第二个工作日24点前到账（双休和法定节假日顺延）', 1])) : ($("#valicodeImg").show()[0].src = "/Member/Withdraw/verify", itz.util.promptA("Drawings_prompt", "promptTmpl", ["提现提示", "提现申请失败！", "提交失败！" + (data.message ? "原因：" + data.message: ""), 0])),
                    $submitBtn.removeAttr("disabled").val("确认提现"),
                    subEnable = 1
                },
                error: function() {
                    $("#valicodeImg").show()[0].src = "/Member/Withdraw/verify",
                    itz.util.promptA("Drawings_prompt", "promptTmpl", ["提现提示", "提现申请失败！", "由于网络原因，提交失败！您可以点击提交重试，或联系网站客服", 0]),
                    $submitBtn.removeAttr("disabled").val("确认提现"),
                    subEnable = 1
                }
            }))
        },
        rules: {
            money: {
                required: !0,
                number: !0,
                min: account.con_lowest_withdraw,
                max: account.use_money,
                toFixed2: !0
            },
            paypassword: "required",
            valicode: {
                required: !0,
                vcode: !0
            },
            importantInfo: {
                required: !0
            }
        },
        messages: {
            money: {
                required: "请填写提现金额",
                number: "提现请填写数字",
                toFixed2: "充值金额最多2位小数",
                min: "提现金额需要大于" + account.con_lowest_withdraw,
                max: "提现金额不能超过可用余额"
            },
            paypassword: "请填写支付密码",
            valicode: {
                required: "* 请填写验证码"
            },
            importantInfo: {
                required: "* 请阅读下方提示并勾选"
            }
        }
    }),
    $("#money").bind("keyup",
    function() {
        var $feeText = $("#feeText"),
        $withdrawTip = $("#withdrawTip");
        if ($feeText.html("0.00"), $withdrawTip.html(""), !$(this).valid()) return ! 1;
        var realMoney = parseFloat(this.value);
        var parseVal = parseFloat(this.value);
        if (parseVal > 0 && this.value <= account.use_money && parseVal >= account.con_lowest_withdraw) if (realMoney = parseVal, realMoney > account.withdraw_free) {
            var fee = Math.ceil(account.min_free * (realMoney - account.withdraw_free)) / 1000;
            var fee1 = Math.ceil(account.back_min_free * account.withdraw_free) / 1000;
            if(fee1 > account.back_max_free){
            	fee1 = account.back_max_free;
            }
            if(fee > account.max_free){
            	fee = account.max_free;
            }
            feeNum = fee1 + fee;
            if(feeNum < account.lowest){
            	feeNum = account.lowest;
            }
            $feeText.html(Math.round(feeNum*100)/100),
            $withdrawTip.html( "<font style='color:green;'>" + Math.round(feeNum*100)/100 + " = " + account.withdraw_free + " x " + (account.back_min_free)/10 +"% + " + (this.value - account.withdraw_free) + " x " + (account.min_free/10) + "%<br>（算法参考温馨提示第一条，保留2位小数）</font>")
        }else if(realMoney = parseVal, realMoney <= account.withdraw_free){
        	if(account.back_min_free > 0){
        		var fee = 0;
            	var feeNum = Math.ceil(account.back_min_free * realMoney) / 1000;
            	if(feeNum > account.back_max_free){
            		feeNum = account.back_max_free;
                }
            	if(feeNum < account.lowest){
                	feeNum = account.lowest;
                }
            	$feeText.html(Math.round(feeNum*100)/100),
                $withdrawTip.html( "<font style='color:green;'>" + Math.round(feeNum*100)/100 + " = " + realMoney + " x " + (account.back_min_free)/10 +"% + " + fee + " x " + (account.min_free/10) + "%<br>（算法参考温馨提示第一条，保留2位小数）</font>");
        	}else{
        		$feeText.html("0.00");
        	}
        	
        } else $feeText.html("0.00"),
        $withdrawTip.html("");
        else $feeText.html("0.00"),
        $withdrawTip.html("")
    })
},
itz.withdraw.getSmsVcode = function(withdrawData) {
    var $sendBtn = $("#sendSmsBtn");
    $sendBtn && (this.smsCountDown = $sendBtn.itzCutDownBtn($("#phoneNum"), withdrawData.getSmsVcodeUrl))
};