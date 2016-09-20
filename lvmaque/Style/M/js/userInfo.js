itz.userInfo = {
    init: function(userInfoData) {
        var that = this;
        $(".user-information-abstract .user-info-state").poshytip({
            alignX: "left",
            offsetX: 15,
            offsetY: -38,
            alignTo: "target",
            showTimeout: 10
        }),
        that.portraitEvents(),
        $("body").delegate(".ps1-close", "click",
        function() {
            var id = $(this).parents(".ui-dialog-content").attr("id");
            $("#" + id).dialog("close")
        }),
        $("#editButton").click(function() {
           var $this = $(this);
            $this.hasClass("editMode") ? that.editModeHide($this) : that.editModeShow($this)
        }),
        $("#resetForm").click(function() {
            that.editModeHide($("#editButton"))
        });
        var uploadingerror = $(".uploading-portrait-con-error");
        $("#PhotoUpload1").fileupload({
            url: userInfoData.uploadUrl + "?type=avatar&time=" + (new Date).getTime(),
            dataType: "json",
            add: function(e, data) {
                var thisFile = data.originalFiles[0];
                return /(\.|\/)(gif|jpe?g|png)$/i.test(thisFile.name) ? thisFile.size > 2097152 ? (uploadingerror.text("上传失败，文件超过2m了"), void 0) : ($("#PhotoUpload1").attr("disabled", !0), uploadStatus1 = 1, $("#uploadBtn1").find("em").text("上传中..."), uploadingerror.empty().append("<img src=" + userInfoData.loading + " id='userInfoDataLoading'/>"), data.submit().success(function(result) {
                    0 == result.code ? (userInfoData.photoSrc = result.data.file_domain + result.data.file_src, $("#uploading-portrait-con-img").attr("src", userInfoData.photoSrc).show(), $("#userInfoDataLoading").remove(), uploadingerror.text(""), $('[name="avatar"]').val(result.data.file_src)) : uploadingerror.text("上传失败，请重新尝试~" + result.info)
                }).error(function() {
                    $("#userInfoDataLoading").remove(),
                    uploadingerror.text("上传失败，请重新尝试")
                }).complete(function() {
                    $("#userInfoDataLoading").remove(),
                    $("#PhotoUpload1").removeAttr("disabled"),
                    $("#uploadBtn1").find("em").text("上传图片"),
                    uploadStatus1 = 0
                }), void 0) : (uploadingerror.text("上传失败，只能上传后缀为gif|jpeg|png的图片"), void 0)
            },
            done: function() {}
        }),
        $(".btn-style-1").click(function() {
            return "" == $("#uploading-portrait-con-img").attr("src") ? (uploadingerror.text("请您上传头像"), !1) : ($.ajax({
                url: userInfoData.photoSrcPost,
                type: "post",
                dataType: "json",
                data: {
                    avatar: $('[name="avatar"]').val()
                },
                success: function(data) {
                    0 == data.code ? (uploadingerror.empty(), $("#Edit_portrait").dialog("close"), $(".user-portrait img").attr("src", userInfoData.photoSrc)) : uploadingerror.html(data.info)
                }
            }), void 0)
        }),
        that.editSubmit(userInfoData)
    },
    editModeShow: function($button) {
        $button.addClass("editMode"),
        $("#userInfo").hide(),
        $("#userInfoEdit").fadeIn(),
        $button.find(".innerText").html("取消修改")
    },
    editModeHide: function($button) {
       $("#userInfoEdit").hide(),
        $("#userInfo").fadeIn(),
        $button.find(".innerText").html("修改个人信息"),
        $button.removeClass("editMode")
    },
    editSubmit: function(userInfoData) {
        $("#userInfoForm").validate({
            submitHandler: function(form) {
                $("#submitBtn").attr("disabled", !0).val("保存中..."),
                $.ajax({
                    url: userInfoData.postUrl,
                    type: "post",
                    dataType: "json",
                    data: $(form).serialize(),
                    success: function(data) {
                        var formItem;
                        if (0 === data.code) {
                            for (var formData = $(form).serializeArray(), len = formData.length, i = 0; len > i; i++) if (formItem = formData[i], "merriage_status" === formItem.name) switch (formItem.value) {
                            case "1":
                                $("#" + formItem.name).text("未婚");
                                break;
                            case "2":
                                $("#" + formItem.name).text("已婚");
                                break;
                            default:
                                $("#" + formItem.name).text("未设置")
                            } else $("#" + formItem.name).text(formItem.value);
                            $("#userInfoEdit").hide(),
                            $("#userInfo").fadeIn(),
                            $("#editButton").removeClass("editMode").find(".innerText").html("修改个人信息")
                        } else itz.util.promptA("userInfo_prompt", "promptTmpl", ["个人信息提示", "修改失败了！", "原因：" + (data.info ? data.info: data.data[0]) + "<br/>", 0]);
                        $("#submitBtn").removeAttr("disabled").val("保存")
                    },
                    error: function() {
                        itz.util.promptA("userInfo_prompt", "promptTmpl", ["通知设置提示", "由于网络原因，保存失败！", "您可以点击保存重试，或联系网站客服<br/>", 0]),
                        $("#submitBtn").removeAttr("disabled").val("保存")
                    }
                })
            }
        })
    },
    portraitEvents: function() {
        $(".user-portrait").bind("mouseenter",
        function() {
            $(this).addClass("edit-portrait").find("p").show()
        }).bind("mouseleave",
        function() {
            $(this).removeClass("edit-portrait").find("p").hide()
        }).click(function() {
            $("#Edit_portrait").dialog({
                dialogClass: "clearPop pop-style-1",
                bgiframe: !0,
                modal: !0,
                resizable: !1,
                width: 460,
                close: function() {
                    $("#uploading-portrait-con-img").attr("src", "").hide(),
                    $(".uploading-portrait-con-error").html("")
                }
            })
        }).end().find(".btn-style-2").click(function() {
            $("#Edit_portrait").dialog("close")
        })
    }
};