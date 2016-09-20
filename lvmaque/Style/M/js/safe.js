itz.safe = {
    init: function(safeData) {
        $.validator.setDefaults({
            errorClass: "form-style-1-error"
        }),
        $.validator.methods.phone = function(value) {
          var patten = new RegExp(/^1\d{10}$/);
            return patten.test(value)
        },
        $.validator.methods.idCard = function(value) {
            var patten = new RegExp(/^\d{17}[0-9xX]$/);
            return patten.test(value)
        },
        $.validator.methods.chinese = function(value) {
            var patten = new RegExp(/^[·\u4e00-\u9fa5]+$/);
            return patten.test(value)
        },
        $.validator.methods.idCard2 = function(value) {
            if (1 == $("#card_type").val()) {
                var patten = new RegExp(/^\d{17}[0-9xX]$/);
                return patten.test(value)
            }
            return ! 0
        },
        $.validator.methods.isDifferent = function(value, element, param) {
            return $.trim($(param).val()) === $.trim(value) ? !1 : !0
        },
        $.validator.addMethod("trimEmail",
        function(value, element) {
            var value = $.trim(value);
            return this.optional(element) || /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i.test(value)
        },
        "邮箱格式错误，请重新输入"),
        this.user = safeData.user,
        this.bindBtns(safeData),
        $("body").delegate(".ps1-close", "click",
        function() {
            var id = $(this).parents(".ui-dialog-content").attr("id");
            $("#" + id).dialog("close")
        });
        var curMod = getQueryString("curMod");
        if (curMod) switch (curMod) {
        case "identity": //原：realname
            $('#realbtn').trigger('click');
            //"0" == safeData.user.realStatus && (this.identity.bind(safeData), this.bindedObj.identity = 1, this.identity.open());


            break;
        case "phone":
            this.phone.bind(safeData),
            this.bindedObj.phone = 1,
            this.phone.open();
            break;
        case "mail": // 原：email
            var te = this.email;
            te.step1(safeData.emailUrl),
            te.step2(safeData.emailUpdateUrl),
            this.bindedObj.email = 1,
            te.open();
            break;
        case "pwd":
            this.pwd.bind(safeData),
            this.bindedObj.pwd = 1,
            this.pwd.open();
            break;
        case "password": // 原：payPwd
            var tp = this.payPwd;
            safeData.user.paypassword || this.payPwd.step1(safeData.payPwdUrl),
            tp.step2(safeData.payPwdUrl),
            tp.step3(safeData),
            this.bindedObj.payPwd = 1,
            tp.open();
            break;
        case "forgetPayPwd":
            var tp = this.payPwd;
            safeData.user.paypassword || this.payPwd.step1(safeData.payPwdUrl),
            tp.step2(safeData.payPwdUrl),
            tp.step3(safeData),
            this.bindedObj.payPwd = 1,
            tp.forgetOpen();
            break;
        case "question":
            var tq = this.question;
            tq.step1(safeData.questionUrl),
            tq.step2(),
            tq.step3(safeData.questionUrl),
            this.bindedObj.question = 1,
            tq.open();
            break;
        case "questionnaire":
            this.questionnaire(safeData),
            this.bindedObj.questionnaire = 1;
            var $q = $("#index_questionnaire");
            $q.dialog({
                dialogClass: "clearPop pop-style-1",
                resizable: !1,
                modal: !0,
                width: 650,
                close: function() {
                    $q.find(":radio").removeAttr("checked"),
                    $(this).find("dt b").remove(),
                    $("#diaochashengming").hide(),
                    safeData.ajaxVar && safeData.ajaxVar.abort(),
                    $q.find(".ps2-txt-con #loginloding").remove()
                }
            })
        }
    },
    user: {},
    bindedObj: {},
    manageObj: {},
    bindBtns: function(safeData) {
       var objThis = this,
        util = itz.util;
        $("#saftyPageList").delegate(".user-safety-options-state", "click",
        function() {
            var $that = $(this);
            if (!$that.hasClass(".user-safety-options-state-false")) {
                var binded = objThis.bindedObj;
                switch ($that.attr("id")) {
                case "realnameBtn2":
                    util.promptA("Drawings_prompt", "promptTmpl", ["实名认证提示", "修改实名认证需要联系客服", '您可以联系 <a href="http://qiao.baidu.com/v3/?module=default&amp;controller=webim&amp;action=index&amp;siteid=2486763&amp;groupid=0&amp;groupname=%E6%8A%95%E8%B5%84%E5%92%A8%E8%AF%A2" target="_blank">【在线客服】</a><br/>或拨打：'+kePhone+'，联系客服修改', 2]);
                    break;
                case "realnameBtn1":
                    binded.realName || (objThis.realName.bind(safeData), binded.realName = 1),
                    objThis.basicBtnEvent($that,
                    function() {
                        objThis.realName.open()
                    },
                    function() {
                        objThis.realName.close()
                    });
                    break;
                case "phoneBtn1":
                case "phoneBtn3":
                case "phoneBtn2":
                    binded.phone || (objThis.phone.bind(safeData), binded.phone = 1),
                    objThis.basicBtnEvent($that,
                    function() {
                        objThis.phone.open()
                    },
                    function() {
                        objThis.phone.close()
                    });
                    break;
                case "emailBtn1":
                    binded.email || (objThis.email.step1(safeData.emailUrl), objThis.email.step2(safeData.emailUpdateUrl), binded.email = 1),
                    objThis.basicBtnEvent($that,
                    function() {
                        objThis.email.open()
                    },
                    function() {
                        objThis.email.close()
                    });
                    break;
                case "emailBtn2":
                    binded.email || (objThis.email.step2(safeData.emailUpdateUrl), binded.email = 1),
                    objThis.basicBtnEvent($that,
                    function() {
                        objThis.email.open()
                    },
                    function() {
                        objThis.email.close()
                    });
                    break;
                case "pwdBtn1":
                    binded.pwd || (objThis.pwd.bind(safeData.pwdUrl), binded.pwd = 1),
                    objThis.basicBtnEvent($that,
                    function() {
                        objThis.pwd.open()
                    },
                    function() {
                        objThis.pwd.close()
                    });
                    break;
                case "payPwdBtn1":
                    binded.payPwd || (objThis.payPwd.step1(safeData.payPwdUrl), objThis.payPwd.step2(safeData.payPwdUrl), objThis.payPwd.step3(safeData), binded.payPwd = 1),
                    objThis.basicBtnEvent($that,
                    function() {
                        objThis.payPwd.open()
                    },
                    function() {
                        objThis.payPwd.close()
                    });
                    break;
                case "payPwdBtn2":
                    binded.payPwd || (objThis.payPwd.step2(safeData.payPwdUrl), objThis.payPwd.step3(safeData), binded.payPwd = 1),
                    objThis.basicBtnEvent($that,
                    function() {
                        objThis.payPwd.open()
                    },
                    function() {
                        objThis.payPwd.close()
                    });
                    break;
                case "questionBtn1":
                    binded.question || (objThis.question.step1(safeData.questionUrl), objThis.question.step2(), objThis.question.step3(safeData.questionUrl), binded.question = 1),
                    objThis.basicBtnEvent($that,
                    function() {
                        objThis.question.open()
                    },
                    function() {
                        objThis.question.close()
                    });
                    break;
                case "questionBtn2":
                    binded.question || (objThis.question.step1(safeData.questionUrl), objThis.question.step2(), objThis.question.step3(safeData.questionUrl), binded.question = 1),
                    objThis.basicBtnEvent($that,
                    function() {
                        objThis.question.open()
                    },
                    function() {
                        objThis.question.close()
                    });
                    break;
                case "Index_questionnaire_btn":
                    binded.questionnaire || (objThis.questionnaire(safeData), binded.questionnaire = 1),
                    objThis.basicBtnEvent($that);
                    var $q = $("#index_questionnaire");
                    $q.dialog({
                        dialogClass: "clearPop pop-style-1",
                        resizable: !1,
                        modal: !0,
                        width: 650,
                        close: function() {
                            $q.find(":radio").removeAttr("checked"),
                            $(this).find("dt b").remove(),
                            $("#diaochashengming").hide(),
                            safeData.ajaxVar && safeData.ajaxVar.abort(),
                            $q.find(".ps2-txt-con #loginloding").remove()
                        }
                    })
                }
            }
        }),
        $("#realApiCon").parents(".user-safety-options-edit").find(".switchUploadMode").click(function() {
            $("#realApiCon").is(":hidden") ? ($("#realUploadCon").hide(), $("#realApiCon").show()) : ($("#realApiCon").hide(), $("#realUploadCon").show())
        })
    },
    realName: {
        bind: function(safeData) {
            var that = itz.safe,
            $realApiCon = $("#realApiCon"),
            $realUploadCon = $("#realUploadCon"),
            $uploadBtn1 = ($("#switchUpload"), $("#uploadBtn1")),
            $cardUpload1 = $("#cardUpload1"),
            $uploadBtn2 = $("#uploadBtn2"),
            $cardUpload2 = $("#cardUpload2"),
            uploadStatus1 = 0,
            uploadStatus2 = 0;
            $("#card_type").change(function() {
                var $error = $realUploadCon.find("#idcardError");
                "1" != $(this).val && $error.empty()
            }),
            $realApiCon.find("form").validate({
                errorPlacement: function(error, element) {
                    element.parent().next().html(error)
                },
                submitHandler: function(form) {
                    var promptA = itz.util.promptA,
                    $submitBtn = $("#realSubmitBtn1"),
                    cardValue = $realApiCon.find("input[name=card_id]").val(),
                    idCardInfo = that.getInfoByIdCard(cardValue, safeData.st);
                    return 0 !== idCardInfo.code ? (promptA("Drawings_prompt", "promptTmpl", ["实名认证提示", "您的身份证号不合法~", "请仔细检查并认真输入~<br/>", 0]), void 0) : idCardInfo.data.if18 ? ($submitBtn.attr("disabled", !0).val("提交中..."), $.ajax({
                        url: safeData.realnameApi,
                        type: "post",
                        dataType: "json",
                        data: $(form).serialize() + "&realname_type=1&sex=" + idCardInfo.data.sex + "&birthday=" + idCardInfo.data.birthday,
                        success: function(data) {
                            if (0 === data.code) {
                                var $item = $realApiCon.parents(".user-safety-options-item");
                                $item.find(".icon-tanhao").removeClass("icon-tanhao").addClass("icon-check"),
                                $item.find(".user-safety-options-value").text(data.data.maskcard),
                                $item.find("#realnameBtn1").text("收起"),
                                $realApiCon.hide(),
                                itz.safe.statusShow($("#successCon"), "恭喜您实名认证成功"),
                                itz.safe.user.realStatus = 1
                            } else promptA("Drawings_prompt", "promptTmpl", ["实名认证提示", "实名认证失败~", "原因：" + data.info + "<br/>", 0]);
                            $submitBtn.removeAttr("disabled").val("提交实名认证")
                        },
                        error: function() {
                            promptA("Drawings_prompt", "promptTmpl", ["实名认证提示", "由于网络原因，验证失败！", "您可以点击按钮重试，或联系客服："+kePhone+"<br/>", 0]),
                            $submitBtn.removeAttr("disabled").val("提交实名认证")
                        }
                    }), void 0) : (promptA("Drawings_prompt", "promptTmpl", ["实名认证提示", "您未满18周岁，暂不可以投资", "满18周岁后再来吧~<br/>", 0]), void 0)
                },
                rules: {
                    realname: {
                        required: !0,
                        rangelength: [2, 15],
                        chinese: !0
                    },
                    card_id: {
                        required: !0,
                        idCard: !0
                    },
                    serviceAgreement: {
                        required: !0
                    },
                    mandateAgreement: {
                        required: !0
                    }
                },
                messages: {
                    realname: {
                        required: "请输入您的真实姓名~",
                        rangelength: "名字请在2-15位之间呦~",
                        chinese: "请输中文名字~"
                    },
                    card_id: {
                        required: "请输入身份证号码~",
                        idCard: "身份证号码需要是18位的标准格式~"
                    },
                    serviceAgreement: {
                        required: "请同意绿麻雀服务协议~"
                    },
                    mandateAgreement: {
                        required: "请同意委托收付资金协议~"
                    }
                }
            }),
            $realUploadCon.find("form").validate({
                errorPlacement: function(error, element) {
                    element.parent().next().html(error)
                },
                submitHandler: function(form) {
                    var idCardInfo, promptA = itz.util.promptA,
                    $submitBtn = $("#realSubmitBtn2"),
                    cardValue = $realUploadCon.find("input[name=card_id]").val(),
                    cardType = $realUploadCon.find("#card_type").val();
                    if ("1" == cardType) {
                        if (idCardInfo = that.getInfoByIdCard(cardValue, safeData.st), 0 !== idCardInfo.code) return promptA("Drawings_prompt", "promptTmpl", ["实名认证提示", "您的身份证号不合法~", "请仔细检查并认真输入~<br/>", 0]),
                        void 0;
                        if (!idCardInfo.data.if18) return promptA("Drawings_prompt", "promptTmpl", ["实名认证提示", "您未满18周岁，暂不可以投资", "满18周岁后再来吧~<br/>", 0]),
                        void 0
                    }
                    return $("#card_pic1").val() ? $("#card_pic2").val() ? uploadStatus1 || uploadStatus2 ? (promptA("Drawings_prompt", "promptTmpl", ["实名认证提示", "正在上传图片中……", "请稍后再提交~<br/>", 0]), void 0) : ($submitBtn.attr("disabled", !0).val("提交中..."), $.ajax({
                        url: safeData.realnameApi,
                        type: "post",
                        dataType: "json",
                        data: $(form).serialize() + "&realname_type=2" + (idCardInfo ? "&sex=" + idCardInfo.data.sex + "&birthday=" + idCardInfo.data.birthday: ""),
                        success: function(data) {
                            if (0 === data.code) {
                                var $item = $realApiCon.parents(".user-safety-options-item");
                                $item.find("#realnameBtn1").text("收起"),
                                $realUploadCon.hide(),
                                itz.safe.statusShow($("#successCon"), "上传资料成功，请等待审核"),
                                itz.safe.user.realStatus = 2
                            } else promptA("Drawings_prompt", "promptTmpl", ["实名认证提示", "上传资料失败~", "原因：" + data.info + "<br/>", 0]);
                            $submitBtn.removeAttr("disabled").val("提交实名认证")
                        },
                        error: function() {
                            promptA("Drawings_prompt", "promptTmpl", ["实名认证提示", "由于网络原因，验证失败！", "您可以点击按钮重试，或联系客服："+kePhone+"<br/>", 0]),
                            $submitBtn.removeAttr("disabled").val("提交实名认证")
                        }
                    }), void 0) : ($("#cardError2").text("请上传证件的背面~"), void 0) : ($("#cardError1").text("请上传证件的正面~"), void 0)
                },
                rules: {
                    realname: {
                        required: !0,
                        rangelength: [2, 15],
                        chinese: !0
                    },
                    card_id: {
                        required: !0,
                        idCard2: !0
                    },
                    serviceAgreement: {
                        required: !0
                    },
                    mandateAgreement: {
                        required: !0
                    }
                },
                messages: {
                    realname: {
                        required: "请输入您的真实姓名~",
                        rangelength: "名字请在2-15位之间呦~",
                        chinese: "请输中文名字~"
                    },
                    card_id: {
                        required: "请输入证件号码~",
                        idCard2: "身份证号码需要是18位的标准格式~"
                    },
                    serviceAgreement: {
                        required: "请同意绿麻雀服务协议~"
                    },
                    mandateAgreement: {
                        required: "请同意委托收付资金协议~"
                    }
                }
            }),
            $cardUpload1.fileupload({
                url: safeData.uploadUrl + "?type=card",
                dataType: "json",
                add: function(e, data) {
                    if (1 !== uploadStatus1) {
                        var thisFile = data.originalFiles[0],
                        $cardError1 = $("#cardError1");
                        if (!/(\.|\/)(gif|jpe?g|png)$/i.test(thisFile.name)) return $cardError1.text("上传失败，只能上传后缀为gif|jpeg|png的图片"),
                        void 0;
                        if (thisFile.size > 2097152) return $cardError1.text("上传失败，文件超过2m了"),
                        void 0;
                        $cardError1.html('<img src="' + safeData.loading + '">'),
                        uploadStatus1 = 1,
                        $uploadBtn1.find("em").text("上传中..."),
                        data.submit().success(function(result) {
                            0 == result.code ? ($("#cardUploadImg1 img").attr("src", result.data.file_domain + result.data.file_src), $cardError1.text(""), $("#card_pic1").val(result.data.file_src)) : $cardError1.text("上传失败，请重新尝试~")
                        }).error(function() {
                            $cardError1.text("上传失败，请重新尝试~")
                        }).complete(function() {
                            $uploadBtn1.find("em").text("上传图片"),
                            uploadStatus1 = 0
                        })
                    }
                },
                done: function() {}
            }),
            $cardUpload2.fileupload({
                url: safeData.uploadUrl + "?type=card",
                dataType: "json",
                add: function(e, data) {
                    if (1 !== uploadStatus2) {
                        var thisFile = data.originalFiles[0],
                        $cardError2 = $("#cardError2");
                        if (!/(\.|\/)(gif|jpe?g|png)$/i.test(thisFile.name)) return $cardError2.text("上传失败，只能上传后缀为gif|jpeg|png的图片"),
                        void 0;
                        if (thisFile.size > 2097152) return $cardError2.text("上传失败，文件超过2m了"),
                        void 0;
                        $cardError2.html('<img src="' + safeData.loading + '">'),
                        uploadStatus2 = 1,
                        $uploadBtn2.find("em").text("上传中..."),
                        data.submit().success(function(result) {
                            0 == result.code ? ($("#cardUploadImg2 img").attr("src", result.data.file_domain + result.data.file_src), $cardError2.text(""), $("#card_pic2").val(result.data.file_src)) : $cardError2.text("上传失败，请重新尝试~")
                        }).error(function() {
                            $cardError2.text("上传失败，请重新尝试~")
                        }).complete(function() {
                            $uploadBtn2.find("em").text("上传图片"),
                            uploadStatus2 = 0
                        })
                    }
                },
                done: function() {}
            })
        },
        open: function() {
            if ($("#realWrapper").slideDown(), itz.safe.manageObj.realName = 1, location.href.match(/'newuser\/index\/regSuccess'/gi)) {
                var $btn = $("#realnameBtn1"),
                temp = $btn.text();
                $btn.text($btn.attr("switchtext")).attr("switchtext", temp).addClass("safe-off")
            } else $("#realnameBtn1").addClass("safe-off").attr("switchtext", "认证").text("取消认证")
        },
        close: function() {
            $("#realWrapper").slideUp().find("form").each(function() {
                this.reset()
            });
            var real = itz.safe.user.realStatus;
            if (itz.safe.manageObj.realName = 0, location.href.match(/'newuser\/index\/regSuccess'/gi)) {
                var $btn = $("#realnameBtn1"),
                temp = $btn.text();
                $btn.text($btn.attr("switchtext")).attr("switchtext", temp).addClass("safe-off")
            }
            if ($("#successCon").hide(), "2" == real) $("#realnameBtn1").removeAttr("id").removeClass("safe-off").text("审核中").addClass("user-safety-options-state-false");
            else if ("0" == real || "3" == real) if (location.href.match(/'newuser\/index\/regSuccess'/gi)) {
                var $btn = $("#realnameBtn1"),
                temp = $btn.text();
                $btn.text($btn.attr("switchtext")).attr("switchtext", temp).addClass("safe-off")
            } else $("#realnameBtn1").removeClass("safe-off").attr("switchtext", "取消认证").text("认证");
            else $("#realnameBtn1").attr("id", "realnameBtn2").removeClass("safe-off").attr("switchtext", "取消修改").text("修改")
        }
    },
    phone: {
        bind: function(safeData) {

            var tempUrl, $cDown, $phoneCon1 = $("#phoneCon1"), $phoneCon2 = $("#phoneCon2"),
            $sendBtn = $("#sendSmsBtn");
            tempUrl = "0" == itz.safe.user.phoneStatus ? safeData.phoneUrl: safeData.getSmsVcode,
			$cDown = $sendBtn.itzCutDownBtn($("#phoneNum"), tempUrl),
			$phoneCon1.find("form").validate({
				errorPlacement: function(error, element) {
				element.parents(".form-style-1-item").find(".form-style-1-error").html(error)
                },
                submitHandler: function(form) {
                    var url, $submitBtn = $("#phoneSubmitBtn"),
                    $step = $phoneCon1.attr("_step"),
                    params = "";
                    $submitBtn.attr("disabled", !0).val("请稍后..."),
                    "0" == itz.safe.user.phoneStatus ? url = safeData.phoneUrl: (url = safeData.phoneChange, "2" == $step ? params = "&step=1": "3" == $step && (params = "&step=2")),
                    $.ajax({
                        url: url,
                        type: "post",
                        dataType: "json",
                        data: $(form).serialize() + params,
                        success: function(data) {
                            var $statusSpan = $("#phoneStatus1 span");
                            if (0 === data.code) {
                                var $item = $phoneCon1.parents(".user-safety-options-item"),
                                safe = itz.safe;
                                "1" == $step ? ($item.find(".icon-tanhao").removeClass("icon-tanhao").addClass("icon-check"), $item.find(".user-safety-options-value").text("13954081913"), $item.find("#phoneBtn1").text("收起"), $phoneCon1.hide(), safe.statusShow($("#phoneCon_succ"), "恭喜您成功认证手机号"), $item.find("#phoneNewText").text("原手机号码"), $phoneCon1.attr("_step", "2"), safe.user.phoneStatus = 1, $cDown.reset(), $sendBtn.unbind("click"), $cDown = $sendBtn.itzCutDownBtn($("#phoneNum"), safeData.getSmsVcode)) : "2" == $step ? (safe.GoStep($statusSpan, 2), $item.find("form")[0].reset(), $item.find("#phoneNewText").text("新手机号码"), $item.find(".js_vnum").val("2"), $phoneCon1.attr("_step", "3"), $cDown.reset()) : (safe.GoStep($statusSpan, 3), $item.find(".user-safety-options-value").text("13954081913"), $phoneCon1.hide(), safe.statusShow($("#phoneCon_succ"), "恭喜您成功认证新手机"), $item.find("#phoneBtn2").text("收起"), $item.find("#phoneNewText").text("原手机号码"), $phoneCon1.attr("_step", "2"), $cDown.reset())
                            } else itz.util.promptA("Drawings_prompt", "promptTmpl", ["手机认证提示", "验证失败！", "原因是：" + data.info + "<br/>", 0]),
                            $phoneCon1.find("input[name=sms_vcode]").val("");
                            $submitBtn.removeAttr("disabled").val("下一步")
                        },
                        error: function() {

//仅供静态测试，做程序时请删除
                           var $statusSpan = $("#phoneStatus1 span");
                            if (1) {
                                var $item = $phoneCon1.parents(".user-safety-options-item"),
                                safe = itz.safe;
                                "1" == $step ? ($item.find(".icon-tanhao").removeClass("icon-tanhao").addClass("icon-check"), $item.find(".user-safety-options-value").text("13954081913"), $item.find("#phoneBtn1").text("收起"), $phoneCon1.hide(), safe.statusShow($("#phoneCon_succ"), "恭喜您成功认证手机号"), $item.find("#phoneNewText").text("原手机号码"), $phoneCon1.attr("_step", "2"), safe.user.phoneStatus = 1, $cDown.reset(), $sendBtn.unbind("click"), $cDown = $sendBtn.itzCutDownBtn($("#phoneNum"), safeData.getSmsVcode)) : "2" == $step ? (safe.GoStep($statusSpan, 2), $item.find("form")[0].reset(), $item.find("#phoneNewText").text("新手机号码"), $item.find(".js_vnum").val("2"), $phoneCon1.attr("_step", "3"), $cDown.reset()) : (safe.GoStep($statusSpan, 3), $item.find(".user-safety-options-value").text("13954081913"), $phoneCon1.hide(), safe.statusShow($("#phoneCon_succ"), "恭喜您成功认证新手机"), $item.find("#phoneBtn2").text("收起"), $item.find("#phoneNewText").text("原手机号码"), $phoneCon1.attr("_step", "2"), $cDown.reset())
                            } else itz.util.promptA("Drawings_prompt", "promptTmpl", ["手机认证提示", "验证失败！", "原因是：" + data.info + "<br/>", 0]),
                            $phoneCon1.find("input[name=sms_vcode]").val("");
                            $submitBtn.removeAttr("disabled").val("下一步")
//仅供静态测试，做程序时请删除




                          itz.util.promptA("Drawings_prompt", "promptTmpl", ["手机认证提示", "由于网络原因，提交失败！", "您可以点击添加重试，或联系客服："+kePhone+"<br/>", 0]),
                           $submitBtn.removeAttr("disabled").val("下一步")
                        }
                    })
                },

                rules: {
                    phone: {
                        required: !0,
                        phone: !0
                    },
                    sms_vcode: {
                        required: !0,
                        digits: !0,
                        rangelength: [6, 6]
                    }
                },
                messages: {
                    phone: {
                        required: "请输入手机号",
                        phone: "请输入正确的手机号"
                    },
                    sms_vcode: {
                        required: "请输入手机验证码",
                        digits: "验证码必须为数字",
                        rangelength: "验证码为6位数字"
                    }
                }
            })
            $phoneCon2.find("form").validate({
				errorPlacement: function(error, element){
				element.parents(".form-style-1-item").find(".form-style-1-error").html(error)
                },
                submitHandler: function(form) {
                    var url, $submitBtn = $("#que_SubmitBtn"),
                    $step = $phoneCon1.attr("_step"),
                    params = "";
                    $submitBtn.attr("disabled", !0).val("请稍后..."),
                    "0" == itz.safe.user.phoneStatus ? url = safeData.phoneUrl: (url = safeData.phoneChange, "2" == $step ? params = "&step=1": "3" == $step && (params = "&step=2")),
                    $.ajax({
                        url: url,
                        type: "post",
                        dataType: "json",
                        data: $(form).serialize() + params,
                        success: function(data) {
                            var $statusSpan = $("#phoneStatus1 span");
                            if (0 === data.code) {
                                var $item = $phoneCon1.parents(".user-safety-options-item"),
                                safe = itz.safe;
                                "1" == $step ? ($item.find(".icon-tanhao").removeClass("icon-tanhao").addClass("icon-check"), $item.find(".user-safety-options-value").text("13954081913"), $item.find("#phoneBtn1").text("收起"), $phoneCon1.hide(), safe.statusShow($("#phoneCon_succ"), "恭喜您成功认证手机号"), $item.find("#phoneNewText").text("原手机号码"), $phoneCon1.attr("_step", "2"), safe.user.phoneStatus = 1, $cDown.reset(), $sendBtn.unbind("click"), $cDown = $sendBtn.itzCutDownBtn($("#phoneNum"), safeData.getSmsVcode)) : "2" == $step ? (safe.GoStep($statusSpan, 2), $item.find("form")[0].reset(), $item.find("#phoneNewText").text("新手机号码"), $item.find(".js_vnum").val("2"), $phoneCon1.attr("_step", "3"), $cDown.reset()) : (safe.GoStep($statusSpan, 3), $item.find(".user-safety-options-value").text("13954081913"), $phoneCon1.hide(), safe.statusShow($("#phoneCon_succ"), "恭喜您成功认证新手机"), $item.find("#phoneBtn2").text("收起"), $item.find("#phoneNewText").text("原手机号码"), $phoneCon1.attr("_step", "2"), $cDown.reset())
                            } else itz.util.promptA("Drawings_prompt", "promptTmpl", ["手机认证提示", "验证失败！", "原因是：" + data.info + "<br/>", 0]),
                            $phoneCon1.find("input[name=sms_vcode]").val("");
                            $submitBtn.removeAttr("disabled").val("下一步")
                        },
                        error: function() {

//仅供静态测试，做程序时请移到success: function(data) {}里
                           var $statusSpan = $("#phoneStatus1 span");
                            if (1) {
								$phoneCon2.hide();
								$phoneCon1.fadeIn();
                                var $item = $phoneCon1.parents(".user-safety-options-item"),
                                safe = itz.safe;
                                "1" == $step ? ($item.find(".icon-tanhao").removeClass("icon-tanhao").addClass("icon-check"), $item.find(".user-safety-options-value").text("13954081913"), $item.find("#phoneBtn1").text("收起"), $phoneCon1.hide(), safe.statusShow($("#phoneCon_succ"), "恭喜您成功认证手机号"), $item.find("#phoneNewText").text("原手机号码"), $phoneCon1.attr("_step", "2"), safe.user.phoneStatus = 1, $cDown.reset(), $sendBtn.unbind("click"), $cDown = $sendBtn.itzCutDownBtn($("#phoneNum"), safeData.getSmsVcode)) : "2" == $step ? (safe.GoStep($statusSpan, 2), $item.find("form")[0].reset(), $item.find("#phoneNewText").text("新手机号码"), $item.find(".js_vnum").val("2"), $phoneCon1.attr("_step", "3"), $cDown.reset()) : (safe.GoStep($statusSpan, 3), $item.find(".user-safety-options-value").text("13954081913"), $phoneCon1.hide(), safe.statusShow($("#phoneCon_succ"), "恭喜您成功认证新手机"), $item.find("#phoneBtn2").text("收起"), $item.find("#phoneNewText").text("原手机号码"), $phoneCon1.attr("_step", "2"), $cDown.reset())
                            } else itz.util.promptA("Drawings_prompt", "promptTmpl", ["手机认证提示", "验证失败！", "原因是：" + data.info + "<br/>", 0]),
                            $phoneCon1.find("input[name=sms_vcode]").val("");
                            $submitBtn.removeAttr("disabled").val("下一步")
//仅供静态测试，做程序时请success: function(data) {}里




                          itz.util.promptA("Drawings_prompt", "promptTmpl", ["手机认证提示", "由于网络原因，提交失败！", "您可以点击添加重试，或联系客服："+kePhone+"<br/>", 0]),
                           $submitBtn.removeAttr("disabled").val("下一步")
                        }
                    })
                },

                rules: {
                    answer: {
                        required: !0,
                        rangelength: [1, 20]
                    }
                },
                messages: {
                    answer: {
                        required: "需要输入答案",
                        rangelength: "字数请控制在1-20个以内"
                    }
                }




            })
        },



        open: function() {

            var $w = $("#phoneWrapper"),
            safe = itz.safe;
            /** 手机*/
            if (safe.user.phoneStatus == '3'){
                $("#phoneBtn3").addClass("safe-off").attr("switchtext", "查看").text("收起");
            	$w.find("#phoneCon_succ").show();
            }
            /***/
            if ($w.find("#phoneCon1").show(), "0" == safe.user.phoneStatus) if (location.href.match(/'newuser\/index\/regSuccess'/gi)) {
                var $btn = $("#phoneBtn1"),
                temp = $btn.text();
                $btn.text($btn.attr("switchtext")).attr("switchtext", temp).addClass("safe-off")
            } else $("#phoneBtn1").addClass("safe-off").attr("switchtext", "认证").text("取消认证");
            /** 未通过再次认证 */
            if ($w.find("#phoneCon1").show(), "2" == safe.user.phoneStatus) if (location.href.match(/'newuser\/index\/regSuccess'/gi)) {
                var $btn = $("#phoneBtn1"),
                temp = $btn.text();
                $btn.text($btn.attr("switchtext")).attr("switchtext", temp).addClass("safe-off")
            } else $("#phoneBtn1").addClass("safe-off").attr("switchtext", "认证").text("取消认证");
            /***/
            else {
            	$w.find("#phoneStatus1").show(),
            	$w.find("#phoneCon2").hide(),
            	$w.find("#phoneCon3").hide(),
            	$("#phoneBtn2").addClass("safe-off").attr("switchtext", "修改").text("取消修改");
                $w.find("#phoneCon_succ").show();
            }
            $w.slideDown(),
            safe.manageObj.phone = 1
        },
        close: function() {
            var $w = $("#phoneWrapper"),
            $con = $w.find("#phoneCon1");
            /** 手机*/
            if (itz.safe.user.phoneStatus == '3'){
            	$("#phoneBtn3").removeClass("safe-off").attr("switchtext", "收起").text("查看");
            }
            /***/
            if ($w.slideUp().find("form").each(function() {
                this.reset()
            }), $con.hide(), $("#phoneCon_succ").hide(), "0" == itz.safe.user.phoneStatus) $("#phoneBtn1").removeClass("safe-off").attr("switchtext", "取消认证").text("认证");
            /** 未通过再次认证 */
            else if($w.slideUp().find("form").each(function() {
                this.reset()
            }), $con.hide(), $("#phoneCon_succ").hide(), "2" == itz.safe.user.phoneStatus) $("#phoneBtn1").removeClass("safe-off").attr("switchtext", "取消认证").text("认证");
            /***/
            else {
                var $btn1 = $("#phoneBtn1");
                $btn1.length ? $btn1.attr("id", "phoneBtn2").removeClass("safe-off").attr("switchtext", "取消修改").text("修改") : $("#phoneBtn2").removeClass("safe-off").attr("switchtext", "取消修改").text("修改"),
                itz.safe.GoStep($w.find("#phoneStatus1 span"), 1),
                $con.attr("_step", "2")
            }
            itz.safe.manageObj.phone = 0
        }
    },
    email: {
        step1: function(url) {
            var $emailCon1 = $("#emailCon1"),
            that = this;
            $emailCon1.find("form").validate({
                submitHandler: function(form) {
                    var $submitBtn = $("#emailSendSubmit1");
                    $submitBtn.attr("disabled", !0).val("发送中..."),
                    $.ajax({
                        url: url,
                        type: "post",
                        dataType: "json",
                        data: $(form).serialize(),
                        success: function(data) {
                            0 === data.code ? ($emailCon1.find("#emailCon11").hide(), $("#gotoEmailBtn1").attr("href", that.getEmailUrl($(form).find("input[name=email]").val())), $emailCon1.find("#emailCon12").fadeIn(), $("#emailBtn1").text("收起")) : itz.util.promptA("Drawings_prompt", "promptTmpl", ["邮箱验证提示", "邮箱验证失败", "原因是：" + data.info + "<br/>", 0]),
                            $submitBtn.removeAttr("disabled").val("发送验证邮件")
                        },
                        error: function() {
                            itz.util.promptA("Drawings_prompt", "promptTmpl", ["邮箱验证提示", "由于网络原因，提交失败！", "您可以点击添加重试，或联系客服："+kePhone+"<br/>", 0]),
                            $submitBtn.removeAttr("disabled").val("发送验证邮件")
                        }
                    })
                },
                rules: {
                    email: {
                        required: !0,
                        trimEmail: !0,
                        rangelength: [5, 50]
                    }
                },
                messages: {
                    email: {
                        required: "请输入邮箱",
                        trimEmail: "邮箱格式不正确",
                        rangelength: "邮箱长度请控制在5-50位~"
                    }
                }
            })
        },
        step2: function(url) {
            var $emailCon2 = $("#emailCon2"),
            that = this;
            $emailCon2.find("form").validate({
                submitHandler: function(form) {
                    var $submitBtn = $("#emailSendSubmit2");
                    $submitBtn.attr("disabled", !0).val("发送中..."),
                    $.ajax({
                        url: url,
                        type: "post",
                        dataType: "json",
                        data: $(form).serialize(),
                        success: function(data) {
//                            0 === data.code ? ($emailCon2.parents(".user-safety-options-item"), $emailCon2.find("#emailCon21").hide(), $("#gotoEmailBtn2").attr("href", that.getEmailUrl($(form).find("input[name=email]").val())), $emailCon2.find("#emailCon22").fadeIn(), $("#emailBtn2").text("收起")) : itz.util.promptA("Drawings_prompt", "promptTmpl", ["邮箱验证提示", "爱亲，邮箱验证失败了！", "原因是：" + data.info + "<br/>", 0]),
                            $submitBtn.removeAttr("disabled").val("发送验证邮件")
                        },
                        error: function() {
                        	/*
                             1 ? ($emailCon2.parents(".user-safety-options-item"), $emailCon2.find("#emailCon21").hide(),  $emailCon2.find("#emailCon22").fadeIn(), $("#emailBtn2").text("收起")) : itz.util.promptA("Drawings_prompt", "promptTmpl", ["邮箱验证提示", "爱亲，邮箱验证失败了！", "原因是：" + data.info + "<br/>", 0]),
//                           itz.util.promptA("Drawings_prompt", "promptTmpl", ["邮箱验证提示", "爱亲，由于网络原因，提交失败！", "您可以点击添加重试，或联系客服：400-600-4300<br/>", 0]),*/
                            $submitBtn.removeAttr("disabled").val("发送验证邮件")
                        }
                    })
                },
                rules: {
                    email: {
                        required: !0,
                        trimEmail: !0,
                        rangelength: [5, 50],
                        isDifferent: "#emailCon2 input[name=oldemail]"
                    },
                    oldemail: {
                        required: !0,
                        trimEmail: !0,
                        rangelength: [5, 50]
                    }
                },
                messages: {
                    email: {
                        required: "请输入邮箱",
                        trimEmail: "邮箱格式不正确",
                        rangelength: "邮箱长度请控制在5-50位",
                        isDifferent: "新邮箱和原邮箱不能一样"
                    },
                    oldemail: {
                        required: "请输入原邮箱",
                        trimEmail: "邮箱格式不正确",
                        rangelength: "邮箱长度请控制在5-50位"
                    }
                }
            })
        },
        open: function() {
            var $w = $("#emailWrapper"),
            safe = itz.safe;
            $w.slideDown(),
            safe.manageObj.email = 1,
            "0" == safe.user.emailStatus ? ($w.find("#emailCon1").show(), $("#emailBtn1").addClass("safe-off").attr("switchtext", "验证").text("取消验证")) : ($w.find("#emailCon21").show(), $w.find("#emailCon2").show(), $("#emailBtn2").addClass("safe-off").attr("switchtext", "修改").text("取消修改"))
        },
        close: function() {
            var $w = $("#emailWrapper"),
            safe = itz.safe;
            $w.slideUp().find("form").each(function() {
                this.reset()
            }),
            safe.manageObj.email = 0,
            "0" == safe.user.emailStatus ? ($("#emailBtn1").removeClass("safe-off").attr("switchtext", "取消验证").text("验证"), $w.find("#emailCon1").hide(), $w.find("#emailCon11").show(), $w.find("#emailCon12").hide()) : ($w.find("#emailCon2").hide(), $w.find("#emailCon22").hide(), $("#emailBtn2").removeClass("safe-off").attr("switchtext", "取消修改").text("修改"))
        },
        getEmailUrl: function(email) {
            if (email) {
                var temp = email.split("@");
                return "gmail.com" === temp[1] ? "http://www.gmail.com": "http://mail." + temp[1]
            }
        }
    },
    pwd: {
        bind: function(url) {
            //var $pwdCon1 = $("#pwdCon1");
            //$pwdCon1.find("form").validate({
            //    submitHandler: function(form) {
            //        var $submitBtn = $("#pwdSubmit");
            //        $submitBtn.attr("disabled", !0).val("修改中..."),
            //        $.ajax({
            //            url: url,
            //            type: "post",
            //            dataType: "json",
            //            data: $(form).serialize(),
            //            success: function(data) {
            //                0 === data.code ? ($pwdCon1.parents(".user-safety-options-item"), $pwdCon1.hide(), itz.safe.statusShow($("#pwdCon2"), "恭喜您成功修改密码"), $("#pwdBtn1").text("收起")) : itz.util.promptA("Drawings_prompt", "promptTmpl", ["登录密码提示", "修改失败！", "原因：" + data.info + "<br/>", 0]),
            //                $submitBtn.removeAttr("disabled").val("修改登录密码")
            //            },
            //            error: function() {
            //                itz.util.promptA("Drawings_prompt", "promptTmpl", ["登录密码提示", "由于网络原因，提交失败！", "您可以点击添加重试，或联系客服："+kePhone+"<br/>", 0]),
            //                $submitBtn.removeAttr("disabled").val("修改登录密码")
            //            }
            //
            //        })
            //    },
            //    rules: {
            //        oldpassword: {
            //            required: !0,
            //            rangelength: [6, 16]
            //        },
            //        newpassword: {
            //            required: !0,
            //            rangelength: [6, 16],
            //            isDifferent: "#pwdCon1 input[name=oldpassword]"
            //        },
            //        newpassword1: {
            //            equalTo: "#pwdpas"
            //        }
            //    },
            //    messages: {
            //        oldpassword: {
            //            required: "请输入原登录密码",
            //            rangelength: "密码请控制在6-16位以内"
            //        },
            //        newpassword: {
            //            required: "请输入新登录密码~",
            //            rangelength: "密码请控制在6-16位以内",
            //            isDifferent: "新密码不能和原密码一样"
            //        },
            //        newpassword1: {
            //            equalTo: "两次输入的新密码不一致"
            //        }
            //    }
            //})
        },
        open: function() {
            $("#pwdWrapper").slideDown(),
            itz.safe.manageObj.pwd = 1,
            $("#pwdCon1").show(),
            $("#pwdBtn1").addClass("safe-off").attr("switchtext", "修改").text("取消修改")
        },
        close: function() {
            $("#pwdWrapper").slideUp(function() {
                $("#pwdCon2").hide()
            }).find("form").each(function() {
                this.reset()
            }),
            itz.safe.manageObj.pwd = 0,
            $("#pwdBtn1").removeClass("safe-off").attr("switchtext", "取消修改").text("修改")
        }
    },
    payPwd: {
        step1: function(url) {
            var $ppCon1 = $("#payPwdCon1");
            $ppCon1.find("form").validate({
                submitHandler: function(form) {
                    var $submitBtn = $("#ppSubmit1");
                    $submitBtn.attr("disabled", !0).val("设置中..."),
                    $.ajax({
                        url: url,
                        type: "post",
                        dataType: "json",
                        data: $(form).serialize(),
                        success: function(data) {
                            if (0 === data.code) {
                                var $item = $ppCon1.parents(".user-safety-options-item");
                                $ppCon1.hide(),
                                itz.safe.statusShow($("#payPwdCon4"), "恭喜您成功设置支付密码"),
                                $item.find(".icon-tanhao").removeClass("icon-tanhao").addClass("icon-check"),
                                $item.find(".user-safety-options-value-false").removeClass("user-safety-options-value-false").text("已设置"),
                                $item.find("#payPwdBtn1").attr("id", "payPwdBtn2").text("收起"),
                                itz.safe.user.paypassword = 1
                            } else itz.util.promptA("Drawings_prompt", "promptTmpl", ["设置支付密码提示", "设置失败！", "原因：" + data.info + "<br/>", 0]);
                            $submitBtn.removeAttr("disabled").val("设置支付密码")
                        },
                        error: function() {
                            itz.util.promptA("Drawings_prompt", "promptTmpl", ["设置支付密码提示", "由于网络原因，提交失败！", "您可以点击添加重试，或联系客服："+kePhone+"<br/>", 0]),
                            $submitBtn.removeAttr("disabled").val("设置支付密码")
                        }
                    })
                },
                rules: {
                    newpassword: {
                        required: !0,
                        rangelength: [6, 16],
                        isDifferent: "#payPwdCon1 input[name=oldpassword]"
                    },
                    newpassword1: {
                        equalTo: "#pas1"
                    }
                },
                messages: {
                    newpassword: {
                        required: "请输入支付密码~",
                        rangelength: "密码请控制在6-16位以内",
                        isDifferent: "新密码不能和登录密码一样"
                    },
                    newpassword1: {
                        equalTo: "请和上一次输入的密码保持一致"
                    }
                }
            })
        },
        step2: function(url) {
            var $ppCon2 = $("#payPwdCon2");
            $ppCon2.find("#findPayPwdBtn").click(function() {
                return "0" == itz.safe.user.phoneStatus ? (itz.util.promptA("Drawings_prompt", "promptTmpl", ["找回密码提示", "请您先进行手机认证！", '点击进行<a href="/newuser/main/safe?curMod=phone">手机认证</a><br/>', 2]), void 0) : ($ppCon2.hide(), $("#payPwdBtn2").text("收起"), $("#payPwdCon3").fadeIn().find(".yuyin-channel").hide(), void 0)
            }),
            $ppCon2.find("form").validate({
                submitHandler: function(form) {
                    var $submitBtn = $("#ppSubmit2");
                    $submitBtn.attr("disabled", !0).val("修改中..."),
                    $.ajax({
                        url: url,
                        type: "post",
                        dataType: "json",
                        data: $(form).serialize(),
                        success: function(data) {
                            0 === data.code ? ($ppCon2.parents(".user-safety-options-item"), $ppCon2.hide(), itz.safe.statusShow($("#payPwdCon4"), "恭喜您成功修改支付密码"), $("#payPwdBtn2").text("收起")) : itz.util.promptA("Drawings_prompt", "promptTmpl", ["修改支付密码提示", "设置失败！", "原因：" + data.info + "<br/>", 0]),
                            $submitBtn.removeAttr("disabled").val("修改支付密码")
                        },
                        error: function() {
                            itz.util.promptA("Drawings_prompt", "promptTmpl", ["修改支付密码提示", "由于网络原因，提交失败！", "您可以点击添加重试，或联系客服："+kePhone+"<br/>", 0]),
                            $submitBtn.removeAttr("disabled").val("修改支付密码")
                        }
                    })
                },
                rules: {
                    oldpassword: {
                        required: !0,
                        rangelength: [6, 16]
                    },
                    newpassword: {
                        required: !0,
                        rangelength: [6, 16],
                        isDifferent: "#payPwdCon2 input[name=oldpassword]"
                    },
                    newpassword1: {
                        equalTo: "#pas2"
                    }
                },
                messages: {
                    oldpassword: {
                        required: "请输入原支付密码",
                        rangelength: "密码请控制在6-16位以内"
                    },
                    newpassword: {
                        required: "请输入新支付密码~",
                        rangelength: "密码请控制在6-16位以内",
                        isDifferent: "新密码不能和原密码一样"
                    },
                    newpassword1: {
                        equalTo: "两次输入的新密码不一致"
                    }
                }
            })
        },
        step3: function(safeData) {
            var $ppCon3 = $("#payPwdCon3");
            $ppCon3.find("#sendSmsBtn3").itzCutDownBtn(void 0, safeData.findPayPwdPhoneUrl),
            $ppCon3.find("form").validate({
                errorPlacement: function(error, element) {
                    element.parents(".form-style-1-item").find(".form-style-1-error").html(error)
                },
                submitHandler: function(form) {
                    var $submitBtn = $("#ppSubmit3");
                    $submitBtn.attr("disabled", !0).val("修改中..."),
                    $.ajax({
                        url: safeData.findPayPwdUrl,
                        type: "post",
                        dataType: "json",
                        data: $(form).serialize(),
                        success: function(data) {
                            0 === data.code ? ($ppCon3.parents(".user-safety-options-item"), $ppCon3.hide(), itz.safe.statusShow($("#payPwdCon4"), "恭喜您成功修改支付密码")) : itz.util.promptA("Drawings_prompt", "promptTmpl", ["修改支付密码提示", "设置失败！", "原因：" + data.info + "<br/>", 0]),
                            $submitBtn.removeAttr("disabled").val("修改支付密码")
                        },
                        error: function() {
                            itz.util.promptA("Drawings_prompt", "promptTmpl", ["修改支付密码提示", "由于网络原因，提交失败！", "您可以点击添加重试，或联系客服："+kePhone+"<br/>", 0]),
                            $submitBtn.removeAttr("disabled").val("修改支付密码")
                        }
                    })
                },
                rules: {
                    sms_vcode: {
                        required: !0,
                        digits: !0,
                        rangelength: [6, 6]
                    },
                    newpassword: {
                        required: !0,
                        rangelength: [6, 16],
                        isDifferent: "#payPwdCon3 input[name=oldpassword]"
                    },
                    newpassword1: {
                        equalTo: "#pas3"
                    }
                },
                messages: {
                    sms_vcode: {
                        required: "请输入手机验证码",
                        digits: "验证码必须为数字",
                        rangelength: "验证码为6位数字"
                    },
                    newpassword: {
                        required: "请输入新支付密码",
                        rangelength: "密码请控制在6-16位以内",
                        isDifferent: "新密码不能和原密码一样"
                    },
                    newpassword1: {
                        equalTo: "请和上一次输入的密码保持一致"
                    }
                }
            })
        },
        open: function() {
            var $w = $("#payPwdWrapper"),
            safe = itz.safe;
            $w.slideDown(),
            safe.manageObj.payPwd = 1,
            safe.user.paypassword==1 ? ($w.find("#payPwdCon2").show(), $("#payPwdBtn2").addClass("safe-off").attr("switchtext", "修改").text("取消修改")) : ($w.find("#payPwdCon1").show(), $("#payPwdBtn1").addClass("safe-off").attr("switchtext", "设置").text("取消设置"))
            //$("#payPwdBtn1").addClass("safe-off").attr("switchtext", "修改").text("取消修改")
        },
        forgetOpen: function() {
            if ("0" == itz.safe.user.phoneStatus) return itz.util.promptA("Drawings_prompt", "promptTmpl", ["找回密码提示", "请您先进行手机认证！", '点击进行<a href="/newuser/main/safe?curMod=phone">手机认证</a><br/>', 2]),
            void 0;
            var $w = $("#payPwdWrapper"),
            safe = itz.safe;
            $w.slideDown(),
            safe.manageObj.payPwd = 1,
            safe.user.paypassword ? ($w.find("#payPwdCon3").show(), $("#payPwdBtn2").addClass("safe-off").attr("switchtext", "").text("收起")) : ($w.find("#payPwdCon1").show(), $("#payPwdBtn1").addClass("safe-off").attr("switchtext", "设置").text("取消设置"))
        },
        close: function() {
            var $w = $("#payPwdWrapper"),
            safe = itz.safe;
            $w.slideUp().find("form").each(function() {
                this.reset()
            }),
            $("#payPwdBtn1").removeClass("safe-off").attr("switchtext", "取消修改").text("修改")
            safe.manageObj.payPwd = 0,
            safe.user.paypassword==1 ? ($w.find("#payPwdCon3").hide(), $w.find("#payPwdCon4").hide(), $("#payPwdBtn2").removeClass("safe-off").attr("switchtext", "取消修改").text("修改")) : ($("#payPwdBtn1").removeClass("safe-off").attr("switchtext", "取消设置").text("设置"), $w.find("#payPwdCon1").hide(), $w.find("#payPwdCon3").hide())
        }
    },
    question: {
        step1: function(url) {
            var $qCon1 = $("#questionCon1");
            $qCon1.find("form").validate({
                submitHandler: function(form) {
                    var $submitBtn = $("#qSetSubmit");
                    $submitBtn.attr("disabled", !0).val("设置中..."),
                    $.ajax({
                        url: url,
                        type: "post",
                        dataType: "json",
                        data: $(form).serialize() + "&type=2",
                        success: function(data) {
                            if (0 === data.code) {
                                var $item = $qCon1.parents(".user-safety-options-item");
                                $qCon1.hide(),
                                itz.safe.statusShow($("#questionCon4"), "恭喜您成功设置密码保护"),
                                $item.find(".icon-tanhao").removeClass("icon-tanhao").addClass("icon-check"),
                                $item.find(".user-safety-options-value-false").removeClass("user-safety-options-value-false").text("已设置");
                                var $b = $item.find("#questionBtn1");
                                $b.length ? $b.attr("id", "questionBtn2").text("收起") : $item.find("#questionBtn2").text("收起");
                                var selectedText = $("#question option:selected").text();
                                $("#currentQues1").text(selectedText),
                                $("#currentQues2").text(selectedText),
                                itz.safe.user.question = 1,
                                $item.find("form").each(function() {
                                    this.reset()
                                })
                            } else itz.util.promptA("Drawings_prompt", "promptTmpl", ["密码保护提示", "设置失败！", "原因：" + data.info + "<br/>", 0]);
                            $submitBtn.removeAttr("disabled").val("设置")
                        },
                        error: function() {


                                var $item = $qCon1.parents(".user-safety-options-item");
                                $qCon1.hide(),
                                itz.safe.statusShow($("#questionCon4"), "恭喜您成功设置密码保护"),
                                $item.find(".icon-tanhao").removeClass("icon-tanhao").addClass("icon-check"),
                                $item.find(".user-safety-options-value-false").removeClass("user-safety-options-value-false").text("已设置");
                                var $b = $item.find("#questionBtn1");
                                $b.length ? $b.attr("id", "questionBtn2").text("收起") : $item.find("#questionBtn2").text("收起");
                                var selectedText = $("#question option:selected").text();
                                $("#currentQues1").text(selectedText),
                                $("#currentQues2").text(selectedText),
                                itz.safe.user.question = 1,
                                $item.find("form").each(function() {
                                    this.reset()
                                })


//                            itz.util.promptA("Drawings_prompt", "promptTmpl", ["密码保护提示", "爱亲，由于网络原因，提交失败！", "您可以点击添加重试，或联系客服：400-600-4300<br/>", 0]),
                            $submitBtn.removeAttr("disabled").val("设置")
                        }
                    })
                },
                rules: {
                    answer: {
                        required: !0,
                        rangelength: [1, 20]
                    }
                },
                messages: {
                    answer: {
                        required: "需要输入答案",
                        rangelength: "字数请控制在1-20个以内"
                    }
                }
            })
        },
        step2: function() {
            var $qCon2 = $("#questionCon2");
            $qCon2.find("#resetBtn").click(function() {
                $qCon2.hide(),
                $("#questionCon3").fadeIn()
            })
        },
        step3: function(url) {
            var $qCon3 = $("#questionCon3");
            $qCon3.find("form").validate({
                submitHandler: function(form) {
                    var $submitBtn = $("#qCheckSubmit");
                    $submitBtn.attr("disabled", !0).val("验证中..."),
                    $.ajax({
                        url: url,
                        type: "post",
                        dataType: "json",
                        data: $(form).serialize() + "&type=1",
                        success: function(data) {
                          //  0 === data.code ? ($qCon3.hide(), $("#questionCon1").fadeIn()) : itz.util.promptA("Drawings_prompt", "promptTmpl", ["密码保护提示", "验证失败！", "原因：" + data.info + "<br/>", 0]),
                            $submitBtn.removeAttr("disabled").val("验证当前答案")
                        },
                        error: function() {
                             1 ? ($qCon3.hide(), $("#questionCon1").fadeIn()) : itz.util.promptA("Drawings_prompt", "promptTmpl", ["密码保护提示", "验证失败！", "原因：" + data.info + "<br/>", 0]),
//                           itz.util.promptA("Drawings_prompt", "promptTmpl", ["密码保护提示", "爱亲，由于网络原因，提交失败！", "您可以点击添加重试，或联系客服：400-600-4300<br/>", 0]),
                            $submitBtn.removeAttr("disabled").val("验证当前答案")
                        }
                    })
                },
                rules: {
                    answer: {
                        required: !0,
                        rangelength: [1, 20]
                    }
                },
                messages: {
                    answer: {
                        required: "需要输入答案",
                        rangelength: "字数请控制在1-20个以内"
                    }
                }
            })
        },
        open: function() {
            var $w = $("#quesWrapper"),
            safe = itz.safe;
            $w.slideDown(),
            safe.manageObj.question = 1,
            safe.user.question==1 ? ($w.find("#questionCon2").show(), $("#questionBtn2").addClass("safe-off").attr("switchtext", "修改").text("取消修改")) : ($w.find("#questionCon1").show(), $("#questionBtn1").addClass("safe-off").attr("switchtext", "设置").text("取消设置"))
        },
        close: function() {
            var $w = $("#quesWrapper"),
            safe = itz.safe;
            $w.slideUp().find("form").each(function() {
                this.reset()
            }),
            safe.manageObj.question = 0,
            safe.user.question==1 ? ($w.find("#questionCon3").hide(), $w.find("#questionCon4").hide(), $("#questionBtn2").removeClass("safe-off").attr("switchtext", "取消修改").text("修改")) : $("#questionBtn1").removeClass("safe-off").attr("switchtext", "取消设置").text("设置")
        }
    },
    basicBtnEvent: function($t, fn1, fn2) {
        $t.hasClass("safe-off") ? fn2 && fn2() : ($.each(this.manageObj,
        function(key, val) {
            if(dd==false){
                $('#realbtn').trigger('click');}else if(flag==false){
                $("#urgentBtn").trigger('click');
            }
            1 === val && eval("itz.safe." + key + ".close()")
        }), fn1 && fn1())
    },
    GoStep: function($spanArray, num) {
        num = num || 1,
        $spanArray.each(function(index, ele) {
            return $ele = $(ele),
            index > num - 1 && $ele.hasClass("options-step-current") ? ($ele.removeClass("options-step-current"), !0) : (num - 1 >= index && !$ele.hasClass("options-step-current") && $ele.addClass("options-step-current"), void 0)
        })
    },
    statusShow: function($statusCon, text, fn) {
        $statusCon.find(".user-safety-options-succeed-txt").text(text),
        $statusCon.fadeIn(function() {
            fn && fn()
        })
    },
    getInfoByIdCard: function(sId, serverTime) {
        var iSum = 0;
        if (!/^\d{17}(\d|x)$/i.test(sId)) return {
            code: 1,
            info: "请填写有效并且是18位的身份证"
        };
        sId = sId.replace(/x$/i, "a"),
        sBirthday = sId.substr(6, 4) + "-" + Number(sId.substr(10, 2)) + "-" + Number(sId.substr(12, 2));
        var d = new Date(sBirthday.replace(/-/g, "/"));
        if (sBirthday != d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate()) return {
            code: 1,
            info: "请填写有效的身份证号码"
        };
        for (var i = 17; i >= 0; i--) iSum += Math.pow(2, i) % 11 * parseInt(sId.charAt(17 - i), 11);
        if (1 != iSum % 11) return {
            code: 1,
            info: "请填写有效的身份证号码"
        };
        var curTime = serverTime ? new Date(1e3 * serverTime) : new Date,
        if18 = !1;
        return curTime.getFullYear() >= d.getFullYear() + 18 && (curTime.getFullYear() == d.getFullYear() + 18 ? curTime.getMonth() >= d.getMonth() && (curTime.getMonth() == d.getMonth() ? curTime.getDate() >= d.getDate() ? if18 = !0 : 2 == curTime.getMonth() + 1 && 29 == d.getDate() && (if18 = !0) : if18 = !0) : if18 = !0),
        {
            code: 0,
            data: {
                birthday: sBirthday,
                if18: if18,
                sex: sId.substr(16, 1) % 2 ? 1 : 2
            }
        }
    },
    questionnaire: function(safeData, fn) {
        var index_questionnaire = $("#index_questionnaire"),
        index_questionnaire_win = $("#index_questionnaire_win");
        $("#index_questionnaire_real_name").text(safeData.userName),
        index_questionnaire.find(":radio").click(function() {
            $(this).parents("dl").find("dt b").remove()
        }),
        index_questionnaire.find("form").submit(function() {
            var eleRedio = ($(this), null),
            state = !0,
            arr = {};
            safeData.ajaxVar && safeData.ajaxVar.abort();
            for (var i = 1; 11 > i; i++) eleRedio = $(this["questionnaire" + i]),
            eleRedioDt = eleRedio.parents("dl").find("dt"),
            eleRedio.is(":checked") ? (eleRedioDt.find("b").length > 0 && eleRedioDt.find("b").remove(), arr["questionnaire" + i] = function() {
                for (var x = 0; x < eleRedio.length; x++) if ($(eleRedio[x]).is(":checked")) return eleRedio[x].value
            } ()) : (eleRedioDt.find("b").length || eleRedioDt.append("<b>此项未选择</b>"), state = !1);
            if (!state) return ! 1;
            var ajaxData = arr;
            return safeData.ajaxVar = $.ajax({
                url: "/newuser/ajax/addQn",
                type: "POST",
                dataType: "json",
                data: ajaxData,
                timeout: 1e4,
                beforeSend: function() {
                    $("#index_questionnaire .ps2-txt-con").append('<img src="' + safeData.loading + '" style="margin-top:82px;position:absolute;top:698px; right:200px" id="loginloding"/>')
                },
                error: function(jqXHR, textStatus) {
                    "timeout" == textStatus && $("#index_questionnaire .ps2-txt-con #loginloding").remove()
                },
                success: function(data) {
                    $("#index_questionnaire .ps2-txt-con #loginloding").remove(),
                    0 == data.code ? (index_questionnaire.find(".ps1-error").hide(), data.data.score < 50 && $("#diaochashengming").show(), setTimeout(function() {
                        index_questionnaire.dialog("close"),
                        index_questionnaire_win.dialog({
                            dialogClass: "clearPop box-shadow-5",
                            resizable: !1,
                            modal: !0,
                            width: 500,
                            open: function() {
                                $("#wenjuandefen").text(data.data.score),
                                window.User.qn_score = data.data.score,
                                $("#index_questionnaire_html").find(".user-safety-options-value").removeClass("user-safety-options-value-false").text("已设置"),
                                $("#index_questionnaire_html i").addClass("icon-check").removeClass("icon-tanhao"),
                                $("#Index_questionnaire_btn").remove()
                            }
                        })
                    },
                    1e3)) : index_questionnaire.find(".ps1-error").text(data.info).show()
                }
            }),
            !1
        }),
        index_questionnaire_win.find("input").click(function() {
            return index_questionnaire_win.dialog("close"),
            fn && fn(window.User.qn_score),
            !1
        })
    }
};