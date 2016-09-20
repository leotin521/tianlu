var itz = itz || {};
itz.authpop = function(op) {
    "use strict";
    if ("undefined" != typeof jQuery || "undefined" != typeof $) {
        if (! (this instanceof itz.authpop)) return new itz.authpop(op);
        var me = this,
        binded = me.wrapper.data("binded"),
        ctList = [],
        nameTitle = me.wrapper.find("#authSettingTitle"),
        nameContent = me.wrapper.find("#authSettingPop"),
        nameForm1 = nameContent.find("form").eq(0),
        nameForm2 = nameContent.find("form").eq(1),
        nameR1 = nameForm1.find(".js_realname1"),
        nameR2 = nameForm2.find(".js_realname2"),
        nameCard1 = nameForm1.find(".js_card1"),
        nameCard2 = nameForm2.find(".js_card2"),
        nameE1 = nameForm1.find(".js_e1"),
        nameE2 = nameForm1.find(".js_e2"),
        nameE3 = nameForm1.find(".js_e3"),
        nameE4 = nameForm2.find(".js_e4"),
        nameE5 = nameForm2.find(".js_e5"),
        nameE6 = nameForm2.find(".js_e6"),
        nameE7 = nameForm2.find(".js_e7"),
        nameE8 = nameForm2.find(".js_e8"),
        phoneTitle = me.wrapper.find("#authSettingTitle1"),
        phoneContent = me.wrapper.find("#authModifyPop"),
        phoneError1 = phoneContent.find("#errorTips"),
        phoneError2 = phoneContent.find("#errorTipsCode"),
        phoneNum = phoneContent.find("#realTel"),
        phoneCode = phoneContent.find("#realCode"),
        phoneForm = phoneContent.find("form"),
        pwdTitle = me.wrapper.find("#authSettingTitle2"),
        pwdContent = me.wrapper.find("#authPasswordPop"),
        pwdForm = pwdContent.find("form"),
        pwdIn1 = pwdForm.find("#realPass"),
        pwdIn2 = pwdForm.find("#realPass1"),
        pwdp = pwdForm.find("#errorTips1"),
        pwdp1 = pwdForm.find("#errorTips2"),
        pwdp2 = pwdForm.find("#errorTips3"),
        interForm = nameContent.find("#authSettingForm1"),
        uploadForm = nameContent.find("#authSettingForm2"),
        yuyin_wrapper = $("#authPopup .yuyin-channel"),
        yuyin = yuyin_wrapper.find("input"),
        i0 = yuyin_wrapper.find(".js_info0"),
        i1 = yuyin_wrapper.find(".js_info1"),
        i2 = yuyin_wrapper.find(".js_info2"),
        i3 = yuyin_wrapper.find(".js_info3"),
        yct = yuyin_wrapper.find(".yyct"),
        sms = $("#authModifyBtn1"),
        checkPhone = function(value) {
            return /^1\d{10}$/.test(value)
        },
        checkChinese = function(value) {
            return /^[\u4e00-\u9fa5]+$/.test(value) && value.length > 1 && value.length < 7
        },
        checkCode = function(value) {
            return /^\d{6}$/.test(value)
        },
        checkCard = function(value) {
            return /^\d{17}[0-9xX]$/.test(value)
        },
        checkPwd = function(value) {
            var c = value.replace(/^\s|\s$/g, "");
            return c.length > 5 && c.length < 17
        },
        getInfoByIdCard = function(sId, serverTime) {
            var iSum = 0;
            if (!/^\d{17}(\d|x)$/i.test(sId)) return {
              
                code: 1,
                info: "请填写有效并且是18位的身份证"
            };
            var sId = sId.replace(/x$/i, "a"),
            sBirthday = sId.substr(6, 4) + "-" + Number(sId.substr(10, 2)) + "-" + Number(sId.substr(12, 2)),
            d = new Date(sBirthday.replace(/-/g, "/"));
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
        countDown = function(tar, predes, des, description, callback) {
            if (! (this instanceof countDown)) return new countDown(tar, predes, des, description, callback);
            var me = this;
            me.tar = tar,
            me.des = des,
            me.description = description,
            me.callback = callback,
            me.countTag = 0,
            me.pre = predes,
            me.ctd = function() {
                return "INPUT" != me.tar.get(0).tagName ? me.tar.html(me.pre + (me.des >= 0 && me.des < 10 ? "0" + me.des--:me.des--) + me.description) : me.tar.val(me.pre + (me.des >= 0 && me.des < 10 ? "0" + me.des--:me.des--) + me.description),
                me.des <= -1 ? (clearTimeout(me.countTag), me.callback && me.callback(), void 0) : (me.countTag = setTimeout(me.ctd, 1e3), ctList.push(me.countTag), void 0)
            },
            me.ctd()
        },
        clearCountDown = function() {
            for (var l = ctList.length, i = l; i >= 0; i--) clearTimeout(ctList.pop());
            sms.removeAttr("disabled").val("获取短信验证码"),
            yuyin_wrapper.hide()
        };
        if (me.op = $.extend({
            target: ".authpop",
            bgLayer: !0,
            bgColor: "#b2b2b2",
            bgTransparent: .3,
            phone: !0,
            name: !0,
            password: !0,
            user: window.User || {},
            title: "充值前请您完成以下认证",
            csspath: "",
            //fileupload: "/Style/M/js/jquery.fileupload.js",
            //iframetransport: "/Style/M/js/jquery.iframe-transport.js",
            //swfupload: "/Style/M/js/swfupload.js",
            //swfuploadBtn: "/Style/M/js/swfupload.swf",
			fileupload: "/Style/M/js/jquery.fileupload.js",
            iframetransport: "/Style/M/js/jquery.iframe-transport.js",
            swfupload: "/Style/Swfupload/swfupload.js",
            swfuploadBtn: "/Style/Swfupload/swfupload.swf",
			//swfupload: "/Style/M/swfupload/swfupload.js",
            //swfuploadBtn: "/Style/M/swfupload/swfupload.swf",
            paypasswordurl: "/member/user/changepin",
            uploadurl: "/member/verify/uploadImg",
			uploadurl2: "/member/verify/uploadImg2",
			uploadurl3: "/member/charge/uploadimg",
            phonecheckurl: "/member/verify/validatephone",
            getsmsvcodeurl: "/member/verify/sendphone2",
            nameauthbyinter1: "/member/verify/saveid_select",
			nameauthbyinter2: "/member/verify/saveid_select"
        },
        op), me.wrapper.find("h3").html(me.op.title), me.op.fileupload = me.op.csspath + me.op.fileupload, me.op.iframetransport = me.op.csspath + me.op.iframetransport, me.op.swfupload = me.op.csspath + me.op.swfupload, "Array" === Object.prototype.toString.call(me.op.target).slice(8, -1) ? me.op.target.length <= 0 ? me.target = $(".authpop") : (me.target = me.target ? me.target: $([]), $.each(me.op.target,
        function(k, v) {
            "dom" == v ? $(document).ready(function() {
                me.show()
            }) : me.target = 0 == k ? $(v) : me.target.add($(v))
        })) : me.target = $(me.op.target), me.uploadStatus1 = 0, me.uploadStatus2 = 0, me.card1 = "", me.card2 = "", me.show = function() {
            var l = me.op.user; (1 != l.real_status || 1 != l.phone_status || 1 != l.payPwd_status) && (me.reset(), me.op.bgLayer && me.bg.show(), me.wrapper.fadeIn())
        },
        me.close = function() {
            var l = me.op.user;
            1 == l.real_status && 1 == l.phone_status && 1 == l.payPwd_status ? location.reload() : (me.wrapper.fadeOut(), me.op.bgLayer && me.bg.fadeOut(), clearCountDown()),
            "function" == typeof me.op.callback && me.op.callback()
        },
        me.reset = function() {
            me.uploadStatus1 = 0,
            me.card1 = "",
            me.uploadStatus2 = 0,
            me.card2 = "",
            nameTitle.show(),
            phoneTitle.show(),
            pwdTitle.show(),
            nameContent.hide(),
            phoneContent.hide(),
            pwdContent.hide(),
            nameForm1.get(0).reset(),
            nameForm2.get(0).reset(),
            phoneForm.get(0).reset(),
            pwdForm.get(0).reset(),
            me.wrapper.find(".autherror").hide(),
            0 == me.op.name && (nameTitle.remove(), nameContent.remove()),
            0 == me.op.phone && (phoneTitle.remove(), phoneContent.remove()),
            0 == me.op.password && (pwdTitle.remove(), pwdContent.remove());
            var dom = $(document),
            win = $(window),
            osTop = dom.scrollTop(),
            winHeight = win.height(),
            winWidth = win.width(),
            height = dom.height(),
            width = dom.width();
            me.bg.css({
                background: me.op.bgColor,
                width: width + "px",
                height: height + "px",
                opacity: me.op.bgTransparent,
                filter: "alpha(opacity=" + 100 * me.op.bgTransparent + ")"
            }),
            me.wrapper.css({
                top: osTop + winHeight / 2 + "px",
                left: winWidth / 2 + "px",
                "margin-top": -me.wrapper.height() / 2 + "px",
                "margin-left": -me.wrapper.width() / 2 + "px"
            });
            var icon = [],
            btn = [];
            1 == me.op.user.real_status ? (icon.push(nameTitle.find("s")), btn.push(nameTitle.find("#authSetting")), nameContent.hide()) : 3 == me.op.user.real_status && (nameTitle.find("#authSetting").unbind("click").removeClass("authBtn").addClass("authBtn1").html("审核中").css({
                color: "#999",
                cursor: "default"
            }), nameContent.hide()),
            1 == me.op.user.phone_status && (icon.push(phoneTitle.find("s")), btn.push(phoneTitle.find("#authModify")), phoneContent.hide()),
            1 == me.op.user.payPwd_status && (icon.push(pwdTitle.find("s")), btn.push(pwdTitle.find("#authPass")), pwdContent.hide()),
            icon && $.each(icon,
            function(k, v) {
                $(v).removeClass("pop_no").addClass("pop_ok")
            }),
            btn && $.each(btn,
            function(k, v) {
                $(v).unbind("click").removeClass("authBtn").addClass("authBtn1").html("已设置").css("cursor", "default")
            })
        },
        me.target && $.each(me.target,
        function(k, v) {
            var l = me.op.user;
            if (1 != l.real_status || 1 != l.phone_status || 1 != l.payPwd_status) {
                var val = $(v),
                f = val.parents("form");
                val.unbind(),
                f.length > 0 && f.unbind().submit(function() {
                    return ! 1
                }),
                v.onclick = function() {
                    me.show(me.op)
                }
            }
        }), binded) return this;
        me.wrapper.attr("data-binded", !0),
        me.wrapper.find("#authPopupClose").add(me.bg).click(me.close),
        me.wrapper.find(".authBtn").click(function() {
            var that = $(this),
            par = that.parent(),
            next = par.next(),
            contents = next.siblings(".js_content");
            that.parent().hide(),
            contents.slideUp(),
            next.slideDown(),
            contents.prev().slideDown(),
            "authSetting" == that.attr("id") && nameContent.find(".js_username").html(me.op.user.user_name)
            if( reid == 0 ) {
                $('#authUpdateLink1').trigger('click');
                $('#authUpdateLink').remove();
            }else {
                $('#authUpdateLink1').remove();
            }
        }),
        me.wrapper.find(".cancelSetting").click(function() {
            var that = $(this);
            that.parent().slideUp(),
            that.parent().prev().slideDown(),
            that.parent().find("form").get(0).reset(),
            clearCountDown(),
            me.wrapper.find(".autherror").hide()
        }),
        nameContent.find("#authUpdateLink1").click(function() {
            uploadForm.get(0).reset(),
            uploadForm.find(".autherror").hide(),
            me.uploadStatus1 = 0,
            me.card1 = "",
            me.uploadStatus2 = 0,
            me.card2 = "",
            uploadForm.fadeIn(),
            interForm.hide()
        }),
        nameContent.find("#authUpdateLink").click(function() {
            interForm.get(0).reset(),
            interForm.find(".autherror").hide(),
            interForm.fadeIn(),
            uploadForm.hide()
        }),
        pwdForm.find("#authPassBtn").click(function() {
            var inp1v = pwdIn1.val(),
            inp2v = pwdIn2.val();
            return pwdp2.hide(),
            checkPwd(inp1v) ? (pwdp1.hide(), inp1v != inp2v ? (pwdp.show(), void 0) : ($.ajax({
                url: me.op.paypasswordurl,
                type: "post",
                dataType: "json",
                //jsonp: "jsoncallback",
                data: pwdForm.serialize(),
                success: function(msg) {
                    msg && 1 == msg.status ? (pwdp.hide(), pwdp1.hide(), pwdp2.hide(), me.op.user.payPwd_status = 1, me.reset()) : pwdp2.show().html(msg.message)
                },
                error: function(e) {
                    pwdp2.show().html(e.info)
                }
            }), void 0)) : (pwdp1.show().html(), void 0)
        }),
        pwdIn1.blur(function() {
            var that = $(this);
            pwdp2.hide(),
            checkPwd(that.val()) ? pwdp1.hide() : pwdp1.show()
        }),
        pwdIn2.blur(function() {
            var in1 = pwdIn1.val(),
            in2 = pwdIn2.val();
            pwdp2.hide(),
            "" == in1 && "" == in2 || in1 == in2 ? pwdp.hide() : pwdp.show()
        }),
        yuyin.click(function() {
            if (checkPhone(phoneNum.val())) {
                var that = $(this);
                phoneError1.hide(),
                that.attr("disabled", !0).addClass("voicechdis"),
                sms.attr("disabled", !0).css("cursor", "default"),
                $.ajax({
                    url: me.op.getsmsvcodeurl,
                    dataType: "jsonp",
                    jsonp: "jsoncallback",
                    data: {
                        sms: phoneNum.val(),
                        Voice: "true",
                        data_type: "jsonp"
                    },
                    type: "get",
                    success: function(msg) {
                        if (msg && 0 == msg.code) {
                            i0.hide(),
                            i1.show(),
                            i2.show(),
                            i3.show();
                            var now = 60,
                            wo = "秒 后";
                            yct.show().html(" " + now, wo),
                            sms.val(now + "秒后重新获取"),
                            countDown(yct, "在 ", now, wo,
                            function() {
                                yct.hide(),
                                that.removeAttr("disabled").removeClass("voicechdis")
                            }),
                            countDown(sms, "", now, "秒后重新获取",
                            function() {
                                sms.removeAttr("disabled").val("获取短信验证码").css("cursor", "pointer")
                            })
                        } else msg && 5 == msg.code ? (phoneError1.show().html("手机已注册"), sms.removeAttr("disabled").val("获取短信验证码")) : msg && 2 == msg.code ? (phoneError1.show().html("请60秒后重试"), sms.removeAttr("disabled").val("获取短信验证码")) : msg && 4 == msg.code ? (phoneError1.show().html("超出今日发送次数"), sms.removeAttr("disabled").val("获取短信验证码")) : (phoneError1.show().html(msg.info), sms.removeAttr("disabled").val("获取短信验证码")),
                        that.removeAttr("disabled").removeClass("voicechdis")
                    },
                    error: function(msg) {
                        phoneError1.show().html(msg.info),
                        sms.removeAttr("disabled").val("获取短信验证码"),
                        that.removeAttr("disabled").removeClass("voicechdis")
                    }
                })
            } else phoneError1.show().html("手机号码错误")
        }),
        sms.click(function() {
            if (yuyin_wrapper.hide(), checkPhone(phoneNum.val())) {
                var that = $(this);
                phoneError1.hide(),
                that.attr("disabled", !0).val("发送中").css("cursor", "default"),
                yuyin.attr("disabled", !0).addClass("voicechdis"),
                $.ajax({
                    url: me.op.getsmsvcodeurl,
                    dataType: "json",
                    //jsonp: "jsoncallback",
                    data: {
                        cellphone: phoneNum.val()
                        //data_type: "jsonp"
                    },
                    type: "post",
                    success: function(msg) {
                        if (msg && 1 == msg.status) {
                            //yuyin_wrapper.show(),
                            i0.show(),
                            i1.hide(),
                            i3.hide();
                            var now = 60,
                            wo = "秒后重新获取";
                            that.val(now + wo),
                            yct.show().html(" " + now, "秒 后"),
                            countDown(that, "", now, wo,
                            function() {
                                that.removeAttr("disabled").val("获取短信验证码").css("cursor", "pointer")
                            }),
                            countDown(yct, "在 ", now, "秒 后",
                            function() {
                                yuyin.removeAttr("disabled").removeClass("voicechdis"),
                                yct.hide()
                            })
                        } else msg && 2 == msg.status ? (phoneError1.show().html("手机已注册"), that.removeAttr("disabled").val("获取验证码")) : msg && 5 == msg.status ? (phoneError1.show().html("请60秒后重试"), that.removeAttr("disabled").val("获取验证码")) : msg && 4 == msg.status ? (phoneError1.show().html("超出今日发送次数"), that.removeAttr("disabled").val("获取验证码")) : (phoneError1.show().html(msg.message), that.removeAttr("disabled").val("获取验证码")),
                        yuyin.removeAttr("disabled").removeClass("voicechdis")
                    },
                    error: function(msg) {
                        phoneError1.show().html(msg.info),
                        that.removeAttr("disabled").val("获取验证码"),
                        yuyin.removeAttr("disabled").removeClass("voicechdis")
                    }
                })
            } else phoneError1.show().html("手机号码错误")
        }),
        $("#phoneAuth").click(function() {
            if (checkCode(phoneCode.val()) && checkPhone(phoneNum.val())) {
                var that = $(this);
                phoneError2.hide(),
                that.attr("disabled", !0).val("提交中").css("cursor", "default"),
                $.ajax({
                    url: me.op.phonecheckurl,
                    dataType: "json",
                    //jsonp: "jsoncallback",
                    type: "post",
                    //data: phoneForm.serialize() + "&data_type=jsonp",
					data: {
						code: phoneCode.val(),cellphone: phoneNum.val()
					},
                    success: function(msg) {
                        msg && 1 == msg.status ? (me.op.user.phone_status = 1, me.reset()) : phoneError2.show().html(msg.message),
                        that.removeAttr("disabled").val("提交认证").css("cursor", "pointer"),
                        "function" == typeof me.op.callback && me.op.callback()
                    },
                    error: function(e) {
                        phoneError2.show().html(e.info),
                        that.removeAttr("disabled").val("提交认证").css("cursor", "pointer")
                    }
                })
            } else phoneError2.show().html("请输入6位正确的验证码")
        }),
        phoneNum.blur(function() {
            var me = $(this),
            v = me.val();
            checkPhone(v) && phoneError1.hide()
        }),
        phoneCode.blur(function() {
            var me = $(this),
            v = me.val();
            checkCode(v) && phoneError2.hide()
        }),
        $("#nameAuth1").click(function() {
            var flag = !0,
            that = $(this);
            that.attr("disabled", !0).val("提交中..."),
            nameE3.hide(),
            checkChinese(nameR1.val()) ? nameE1.hide() : (nameE1.show().html("请输入2-6位中文姓名"), flag = !1),
            checkCard(nameCard1.val()) ? nameE2.hide() : (nameE2.show().html("身份证号码格式错误"), flag = !1);
            var info = getInfoByIdCard(nameCard1.val());
            return 0 != info.code ? (nameE2.show().html("身份证号不合法"), flag = !1) : info.data.if18 || (nameE2.show().html("满18周岁再来吧"), flag = !1),
            flag ? ($.ajax({
                url: me.op.nameauthbyinter1,
                dataType: "json",
                //jsonp: "jsoncallback",
                type: "post",
                data: nameForm1.serialize() + "&serviceAgreement=on&mandateAgreement=on&realname_type=1&sex=" + info.data.sex + "&birthday=" + info.data.birthday,
                success: function(msg) {
                    msg && 1 == msg.status ? (me.op.user.real_status = 1, me.reset(), "function" == typeof me.op.callback && me.op.callback()) : nameE3.show().html(msg.message),
                    that.removeAttr("disabled").val("提交实名认证")
                },
                error: function(e) {
                    nameE3.show().html(e.message),
                    that.removeAttr("disabled").val("提交实名认证")
                }
            }), void 0) : (that.removeAttr("disabled").val("提交实名认证"), void 0)
        }),
        nameR1.blur(function() {
            checkChinese(nameR1.val()) ? nameE1.hide() : nameE1.show().html("请输入2-6位中文姓名")
        }),
        nameR2.blur(function() {
            checkChinese(nameR2.val()) ? nameE4.hide() : nameE4.show().html("请输入2-6位中文姓名")
        }),
        nameCard1.blur(function() {
            checkCard(nameCard1.val()) ? nameE2.hide() : nameE2.show().html("身份证格式错误")
        }),
        nameCard2.blur(function() {
            checkCard(nameCard2.val()) ? nameE8.hide() : nameE8.show().html("身份证格式错误")
//            var v = nameForm2.find("select"),
//            res = !0,
//            va = nameCard2.val();
//            switch (v.val()) {
//            case "1":
//                res = checkCard(va) ? !0 : !1;
//                break;
//            default:
//                res = va ? !0 : !1
//            }
//            res ? nameE8.hide() : nameE8.show().html("证件号码格式错误")
        }),
        $("#nameAuth2").click(function() {
            var flag = !0,
            that = $(this),
            t = nameForm2.find("select");

            if (that.attr("disabled", !0).val("提交中..."), nameE7.hide(), checkChinese(nameR2.val()) ? nameE4.hide() : (nameE4.show().html("请输入2-6位中文姓名"), flag = !1), 1 == t.val() && !checkCard(nameCard2.val()) || 1 != t.val() && "" == nameCard2.val() ? (nameE8.show().html("证件号码格式错误"), flag = !1) : nameE8.hide(), 1 == t.val()) {
                var info = getInfoByIdCard(nameCard2.val());
                0 != info.code ? (nameE8.show().html("身份证号不合法"), flag = !1) : info.data.if18 || (nameE8.show().html("满18周岁再来吧"), flag = !1)
            }
            return me.uploadStatus1 ? nameE5.hide() : (nameE5.show().html("请上传证件正面照"), flag = !1),
            me.uploadStatus2 ? nameE6.hide() : (nameE6.show().html("请上传证件背面照"), flag = !1),
            checkCard(nameCard2.val()) ? nameE8.hide() : (nameE8.show().html("身份证格式错误"), flag =!1),
            flag ? ($.ajax({
                url: me.op.nameauthbyinter2,
                dataType: "json",
                //jsonp: "jsoncallback",
                data: nameForm2.serialize() + "&realname_type=2&serviceAgreement=on&mandateAgreement=on&sex=" + (info ? info.data.sex: "") + "&birthday=" + (info ? info.data.birthday: "") + "&card_pic1=" + me.card1 + "&card_pic2=" + me.card2,
                type: "post",
                success: function(msg) {
                    msg && 1 == msg.status ? (me.op.user.real_status = 3, me.reset(), "function" == typeof me.op.callback && me.op.callback()) : msg && msg.message.indexOf("此身份证号码已被人使用") >= 0 ? nameE8.show().html("此身份证号码已被人使用") : nameE7.show().html(msg.message),
                    that.removeAttr("disabled").val("提交实名认证")
                },
                error: function(e) {
                    nameE7.show().html(e.info),
                    that.removeAttr("disabled").val("提交实名认证")
                }
            }), void 0) : (that.removeAttr("disabled").val("提交实名认证"), void 0)
        }),
        $.when($.getScript(me.op.swfupload),$.Deferred(function(def) {
            $(def.resolve)
        })).done(function() {
            var tu1 = ($("#loadingGif"), $("#upload1"), $("#upload2"), $("#authUpdate")),
            tu2 = $("#authUpdate1");
            new SWFUpload({
                upload_url: me.op.uploadurl,
				post_params: {"PHPSESSID": session_temp},
                flash_url: me.op.swfuploadBtn,
                button_placeholder_id: "upload11",
                file_types: "*.jpg;*.png;*.gif;*.jpeg",
                button_width: "77",
                button_height: "27",
                button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
                file_queued_handler: function(file) {
                    nameE5.hide(),
                    $("#againUpdate").hide(),
                    $("#loadingGif1").show();
                    var thisFile = file;
                    return /(\.|\/)(gif|jpe?g|png)$/i.test(thisFile.name) ? thisFile.size > 2097152 ? (nameE5.show().html("上传失败，文件超过2m了"), void 0) : void 0 : (nameE5.show().html("上传失败，只能上传后缀为gif|jpeg|png的图片"), void 0)
                },
                file_dialog_complete_handler: function(count) {
                    1 == count && (tu1.val("上传中").css("background", "#d8d8d8"), this.startUpload())
                },
                upload_error_handler: function() {
                    nameE5.show().html("上传失败，请重新尝试"),
                    tu1.val("上传图片")
                },
                upload_success_handler: function(file, data) {
                    var r = $.parseJSON(data);
                    0 == r.code ? ($("#againUpdate").show(), me.uploadStatus1 = 1, me.card1 = r.data.file_src, tu1.val("已上传"), $(".authup1 object").css("left", "240px")) : ($("#loadingGif1").hide(), nameE5.show().html("上传失败，请重新尝试"), tu1.val("上传图片"))
                },
                upload_complete_handler: function() {
                    tu1.css("background", "#E25353"),
                    $("#loadingGif1").hide()
                }
            }),
            new SWFUpload({
                upload_url: me.op.uploadurl2,
				post_params: {"PHPSESSID": session_temp},
                flash_url: me.op.swfuploadBtn,
                button_placeholder_id: "upload22",
                file_types: "*.jpg;*.png;*.gif;*.jpeg",
                button_width: "77",
                button_height: "27",
                button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
                file_queued_handler: function(file) {
                    nameE6.hide(),
                    $("#againUpdate1").hide(),
                    $("#loadingGif2").show();
                    var thisFile = file;
                    return /(\.|\/)(gif|jpe?g|png)$/i.test(thisFile.name) ? thisFile.size > 2097152 ? (nameE6.show().html("上传失败，文件超过2m了"), void 0) : void 0 : (nameE6.show().html("上传失败，只能上传后缀为gif|jpeg|png的图片"), void 0)
                },
                file_dialog_complete_handler: function(count) {
                    1 == count && (tu2.val("上传中").css("background", "#d8d8d8"), this.startUpload())
                },
                upload_error_handler: function() {
                    nameE6.show().html("上传失败，请重新尝试"),
                    tu2.val("上传图片")
                },
                upload_success_handler: function(file, data) {
                    var r = $.parseJSON(data);
                    0 == r.code ? ($("#againUpdate1").show(), me.uploadStatus2 = 1, me.card2 = r.data.file_src, tu2.val("已上传"), $(".authup2 object").css("left", "240px")) : ($("#loadingGif2").hide(), nameE6.show().html("上传失败，请重新尝试~"), tu2.val("上传图片"))
                },
                upload_complete_handler: function() {
                    $("#loadingGif2").hide(),
                    tu2.css("background", "#E25353")
                }
            }),
            new SWFUpload({
                upload_url: me.op.uploadurl3,
				post_params: {'PHPSESSID': session_temp},
                flash_url: me.op.swfuploadBtn,
                button_placeholder_id: "spanButtonPlaceholder",
                file_types: "*.jpg;*.png;*.gif;*.jpeg",
                file_size_limit: "2MB",
                button_width: "250",
                button_height: "18",
				button_text : '<span class="button">选择本地图片 <span class="buttonSmall">(单图最大为 2 MB，支持多选)</span></span>',
                button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
                file_queue_error_handler : fileQueueError,
				file_dialog_complete_handler : fileDialogComplete,
				upload_progress_handler : uploadProgress,
				upload_error_handler : uploadError,
				upload_success_handler : uploadSuccess,
				upload_complete_handler : uploadComplete,
				custom_settings : {
					upload_target : "divFileProgressContainer"
				}
            })
        })
    } else if (itz.debug) throw {
        code: 100,
        info: "oauthpop class depend on jQuery"
    }
},
itz.authpop.prototype.wrapper = $("#authPopup").length ? $("#authPopup") : reid ? $('<div id="authPopup" class="authpopup authPopups" style="display:none;z-index:9999">	    <div class="tt">        	<h3>充值前请您完成以下认证</h3>	        <a class="authPopupsClose" id="authPopupClose">×</a>	    </div>        <div class="cn">			<div id="authSettingTitle1" class="item clearfix js_title">            	<p class="authItem"><s class="pop_no forum_pop"></s>手机认证</p>            	<p class="authItem1">用于接收充值、投资、项目到期、提现等重要通知提醒</p>            	<a id="authModify" class="authBtn">认证</a>        	</div>        	<div id="authModifyPop" class="authModifyPop js_content">            	<p class="authItem mgt"><s class="pop_no forum_pop"></s>手机认证</p>            	<a class="cancelSetting" id="cancelSetting">取消认证</a>             	<form>                    <div>                        <label>手机号码:</label>                        <input type="text" class="realTel ti1" id="realTel" name="phone"/>                        <input type="button" class="authModifyBtn1" id="authModifyBtn1"  value="获取验证码" />                        <span id="errorTips" class="autherror">获取验证码失败</span>                    </div>					<div class="yuyin-channel" style="display:none;"><label class="form-style-1-label">&nbsp</label><span class="yuyin-sp"><i class="js_info0">如果没有收到短信，您可</i><i class="js_info3"></i><i class="js_info1">请注意接听 010-5321233* 的来电，若没收到，您可</i><i class="yyct"></i><!--<i style="display:block">点击</i>--><i class="js_info2">重新</i><i style="display:block;">获取</i><input type="button" value="语音验证码" data-voice="true" class="voicech"/></span></div>                    <div>                        <label>短信验证码:</label>                        <input type="text" class="realCode ti1" id="realCode" name="sms_vcode"/>						<span id="errorTipsCode" class="dspb autherror">验证码错误</span>					</div>					<div>					 <label class="bspDiv"></label>                        <input type="sumbit" class="authModifyBtn" style="width:96px;" id="phoneAuth"  value="提交认证" />                    </div>                </form>                <div class="tips">                    <b>温馨提示</b>                    <p>1. 请填写真实有效的手机号，手机号将作为验证用户身份的重要手段。</p>                    <p>2. 手机认证过程遇到问题，请联系客服，'+kePhone+'。</p>                </div>        	</div>			<div id="authSettingTitle" class="item clearfix js_title">    	       	<p class="authItem"><s class="pop_no forum_pop"></s>实名认证</p>           	    <p class="authItem1">为保障您的账户和资金安全，需确认您的投资身份</p>	           	<a id="authSetting" class="authBtn">认证</a>    	    </div>        	<div id="authSettingPop" class="authSettingPop js_content">        	    <p class="authItem mgt"><s class="pop_no forum_pop"></s>实名认证</p>	            <a class="cancelSetting" id="cancelSetting">取消认证</a>    	        <form id="authSettingForm1">                	<div>        	        	<label>真实姓名:</label>                    	<input type="text" class="realName ti1 js_realname1" name="real_name" />                        <span class="js_e1 autherror"></span>                	</div>                	<div>                    	<label>身份证号:</label>                    	<input type="text" class="realNum ti1 js_card1" name="idcard"/>                        <span class="js_e2 autherror"></span>                	</div>                	<div>                    	<label class="bspDiv"></label>                    	<input type="sumbit" id="nameAuth1"  class="authSettingBtn ti1"  value="提交实名认证" />                    	<a id="authUpdateLink1" class="authUpdateLink">采用上传认证</a>                        <span class="js_e3 autherror" style="display: block;margin-left: 130px;"></span>                	</div>                </form>                <form id="authSettingForm2">                    <div>                        <label>真实姓名:</label>                        <input type="text" class="realName ti1 js_realname2" name="real_name"/>                        <span class="js_e4 autherror"></span>                    </div>										<div>                    	<label>证件号码:</label>                    	<input type="text" class="realNum ti1 js_card2" name="idcard"/>                        <span class="js_e8 autherror"></span>                	</div>                    <div class="rel authup1">                        <label>正面证件照:</label>                        <input type="button" class="authUpdateBtn" id="authUpdate"  value="上传图片" />                        <s id="loadingGif1" class="loadingGif" style="display:none;"></s>                        <a id="againUpdate" class="authUpdateLink" style="display:none"><s class="pop_right forum_pop"></s>重新选择图片</a>						<input id="upload1" type="file" class="authupload" name="file" >						<div id="upload11"></div>                        <span class="js_e5 autherror"></span>                    </div>                    <div class="rel authup2">                        <label>背面证件照:</label>                        <input type="button" id="authUpdate1" class="authUpdateBtn1"  value="上传图片" />                        <s id="loadingGif2" class="loadingGif" style="display:none;"></s>                        <a id="againUpdate1" class="authUpdateLink" style="display:none"><s class="pop_right forum_pop"></s>重新选择图片</a>						<input id="upload2" type="file" class="authupload" name="file" >						<div id="upload22"></div>                        <span class="js_e6 autherror"></span>                    </div>                    <div>                        <label class="bspDiv"></label>                        <input type="sumbit" id="nameAuth2" class="authSettingBtn"  value="提交实名认证" />                        <a id="authUpdateLink" class="authUpdateLink">采用接口认证</a>                        <span class="js_e7 autherror"></span>                    </div>                </form>                <div class="tips">                    <b>温馨提示</b>      <p>1. 您还可通过 上传认证 完成实名认证，然后等待客服人员审核通过。</p>                    <p>2. 实名认证过程遇到问题，请联系客服，'+kePhone+'</p>                </div>            </div>			<div id="authSettingTitle2" class="item clearfix js_title">                <p class="authItem"><s class="pop_no forum_pop"></s>设置支付密码</p>                <p class="authItem1">保障资金安全，充值、取现、投资等资金相关操作时使用</p>                <a id="authPass" class="authBtn">设置</a>            </div>			<div id="authPasswordPop" class="authPasswordPop js_content">            	<p class="authItem mgt"><s class="pop_no forum_pop"></s>设置支付密码</p>            	<a class="cancelSetting" id="cancelSetting">取消设置</a>				<form>                                       <div>            <label>设置支付密码:</label>                        <input type="password" class="realTel ti1" id="realPass" name="newpwd1"/>                        <span id="errorTips2" class="autherror">请输入6-16位密码</span>                    </div>                    <div>                        <label>再次输入密码:</label>                        <input type="password" class="realCode ti1" id="realPass1" name="newpwd2"/>                        <span id="errorTips1" class="autherror">两次输入不一致</span>                    </div>                    <div>                        <label class="bspDiv"></label>                        <input type="sumbit" id="authPassBtn"  value="设置支付密码" />                        <span id="errorTips3" class="autherror">两次输入不一致</span>                    </div>                </form>				<div class="tips">                    <b>温馨提示</b>                    <p>1.请牢记您设置的支付密码，支付密码将用于投资，提现等重要操作。</p>                    <p>2.使用过程遇到问题，请联系客服，'+kePhone+'</p>                </div>        	</div>		</div>	</div>').appendTo($("body")) : $('<div id="authPopup" class="authpopup authPopups" style="display:none;z-index:9999">	    <div class="tt">        	<h3>充值前请您完成以下认证</h3>	        <a class="authPopupsClose" id="authPopupClose">×</a>	    </div>        <div class="cn">			<div id="authSettingTitle1" class="item clearfix js_title">            	<p class="authItem"><s class="pop_no forum_pop"></s>手机认证</p>            	<p class="authItem1">用于接收充值、投资、项目到期、提现等重要通知提醒</p>            	<a id="authModify" class="authBtn">认证</a>        	</div>        	<div id="authModifyPop" class="authModifyPop js_content">            	<p class="authItem mgt"><s class="pop_no forum_pop"></s>手机认证</p>            	<a class="cancelSetting" id="cancelSetting">取消认证</a>             	<form>                    <div>                        <label>手机号码:</label>                        <input type="text" class="realTel ti1" id="realTel" name="phone"/>                        <input type="button" class="authModifyBtn1" id="authModifyBtn1"  value="获取验证码" />                        <span id="errorTips" class="autherror">获取验证码失败</span>                    </div>					<div class="yuyin-channel" style="display:none;"><label class="form-style-1-label">&nbsp</label><span class="yuyin-sp"><i class="js_info0">如果没有收到短信，您可</i><i class="js_info3"></i><i class="js_info1">请注意接听 010-5321233* 的来电，若没收到，您可</i><i class="yyct"></i><!--<i style="display:block">点击</i>--><i class="js_info2">重新</i><i style="display:block;">获取</i><input type="button" value="语音验证码" data-voice="true" class="voicech"/></span></div>                    <div>                        <label>短信验证码:</label>                        <input type="text" class="realCode ti1" id="realCode" name="sms_vcode"/>						<span id="errorTipsCode" class="dspb autherror">验证码错误</span>					</div>					<div>					 <label class="bspDiv"></label>                        <input type="sumbit" class="authModifyBtn" style="width:96px;" id="phoneAuth"  value="提交认证" />                    </div>                </form>                <div class="tips">                    <b>温馨提示</b>                    <p>1. 请填写真实有效的手机号，手机号将作为验证用户身份的重要手段。</p>                    <p>2. 手机认证过程遇到问题，请联系客服，'+kePhone+'。</p>                </div>        	</div>			<div id="authSettingTitle" class="item clearfix js_title">    	       	<p class="authItem"><s class="pop_no forum_pop"></s>实名认证</p>           	    <p class="authItem1">为保障您的账户和资金安全，需确认您的投资身份</p>	           	<a id="authSetting" class="authBtn">认证</a>    	    </div>        	<div id="authSettingPop" class="authSettingPop js_content">        	    <p class="authItem mgt"><s class="pop_no forum_pop"></s>实名认证</p>	            <a class="cancelSetting" id="cancelSetting">取消认证</a>    	        <form id="authSettingForm1 ">                	<div>        	        	<label>真实姓名:</label>                    	<input type="text" class="realName ti1 js_realname1" name="real_name" />                        <span class="js_e1 autherror"></span>                	</div>                	<div>                    	<label>身份证号:</label>                    	<input type="text" class="realNum ti1 js_card1" name="idcard"/>                        <span class="js_e2 autherror"></span>                	</div>                	<div>                    	<label class="bspDiv"></label>                    	<input type="sumbit" id="nameAuth1"  class="authSettingBtn ti1"  value="提交实名认证" />                    	<a id="authUpdateLink1" class="authUpdateLink">采用上传认证</a>                        <span class="js_e3 autherror" style="display: block;margin-left: 130px;"></span>                	</div>                </form>                <form id="authSettingForm2">                    <div>                        <label>真实姓名:</label>                        <input type="text" class="realName ti1 js_realname2" name="real_name"/>                        <span class="js_e4 autherror"></span>                    </div>										<div>                    	<label>证件号码:</label>                    	<input type="text" class="realNum ti1 js_card2" name="idcard"/>                        <span class="js_e8 autherror"></span>                	</div>                    <div class="rel authup1">                        <label>正面证件照:</label>                        <input type="button" class="authUpdateBtn" id="authUpdate"  value="上传图片" />                        <s id="loadingGif1" class="loadingGif" style="display:none;"></s>                        <a id="againUpdate" class="authUpdateLink" style="display:none"><s class="pop_right forum_pop"></s>重新选择图片</a>						<input id="upload1" type="file" class="authupload" name="file" >						<div id="upload11"></div>                        <span class="js_e5 autherror"></span>                    </div>                    <div class="rel authup2">                        <label>背面证件照:</label>                        <input type="button" id="authUpdate1" class="authUpdateBtn1"  value="上传图片" />                        <s id="loadingGif2" class="loadingGif" style="display:none;"></s>                        <a id="againUpdate1" class="authUpdateLink" style="display:none"><s class="pop_right forum_pop"></s>重新选择图片</a>						<input id="upload2" type="file" class="authupload" name="file" >						<div id="upload22"></div>                        <span class="js_e6 autherror"></span>                    </div>                    <div>                        <label class="bspDiv"></label>                        <input type="sumbit" id="nameAuth2" class="authSettingBtn"  value="提交实名认证" />                        <a id="authUpdateLink" class="authUpdateLink">采用接口认证</a>                        <span class="js_e7 autherror"></span>                    </div>                </form>                <div class="tips">                    <b>温馨提示</b>                                       <p>1. 您还可通过上传认证 完成实名认证，然后等待客服人员审核通过。</p>                    <p>2. 实名认证过程遇到问题，请联系客服，'+kePhone+'</p>                </div>            </div>			<div id="authSettingTitle2" class="item clearfix js_title">                <p class="authItem"><s class="pop_no forum_pop"></s>设置支付密码</p>                <p class="authItem1">保障资金安全，充值、取现、投资等资金相关操作时使用</p>                <a id="authPass" class="authBtn">设置</a>            </div>			<div id="authPasswordPop" class="authPasswordPop js_content">            	<p class="authItem mgt"><s class="pop_no forum_pop"></s>设置支付密码</p>            	<a class="cancelSetting" id="cancelSetting">取消设置</a>				<form>                               <div>            <label>设置支付密码:</label>                        <input type="password" class="realTel ti1" id="realPass" name="newpwd1"/>                        <span id="errorTips2" class="autherror">请输入6-16位密码</span>                    </div>                    <div>                        <label>再次输入密码:</label>                        <input type="password" class="realCode ti1" id="realPass1" name="newpwd2"/>                        <span id="errorTips1" class="autherror">两次输入不一致</span>                    </div>                    <div>                        <label class="bspDiv"></label>                        <input type="sumbit" id="authPassBtn"  value="设置支付密码" />                        <span id="errorTips3" class="autherror">两次输入不一致</span>                    </div>                </form>				<div class="tips">                    <b>温馨提示</b>                    <p>1.请牢记您设置的支付密码，支付密码将用于投资，提现等重要操作。</p>                    <p>2.使用过程遇到问题，请联系客服，'+kePhone+'</p>                </div>        	</div>		</div>	</div>').appendTo($("body")),
itz.authpop.prototype.bg = $("#authpopbg").length ? $("#authpopup") : $('<div id="authpopbg" style="position:absolute;left:0;top:0;z-index:9998;"></div>').appendTo($("body"));