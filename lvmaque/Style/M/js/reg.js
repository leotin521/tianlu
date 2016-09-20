var validateCode = false;
var flag=true;
var arrBox = new Array();
arrBox["dvUser"] = "<span style='text-align:center;font-size:14px;font-weight:bold;display:block;float:left;width:15px;height:33px;margin-left:15px;'>!</span>&nbsp;4-20个字母、数字、汉字、下划线。";
arrBox["dvPwd"] = "<span style='text-align:center;font-size:14px;font-weight:bold;display:block;float:left;width:15px;height:33px;margin-left:15px;'>!</span>&nbsp;6-16个字母、数字、下划线。";
arrBox["dvRepwd"] = "<span style='text-align:center;font-size:14px;font-weight:bold;display:block;float:left;width:15px;height:33px;margin-left:15px;'>!</span>&nbsp;请再一次输入您的密码。";
arrBox["dvRec"] = "<span style='text-align:center;font-size:14px;font-weight:bold;display:block;float:left;width:15px;height:33px;margin-left:15px;'>!</span>&nbsp;请输入推荐人的用户名,可不填。";
arrBox["dvCode"] = "<span style='text-align:center;font-size:14px;font-weight:bold;display:block;float:left;width:15px;height:33px;margin-left:15px;'>!</span>&nbsp;请按照图片显示内容输入验证码。";
var arrWrong = new Array();
arrWrong["dvUser"] = "<img style='margin:9px;margin-left:20px;' src='"+imgpath+"images/zhuce2.gif'/>&nbsp;4-20个字母、数字、汉字、下划线。";
arrWrong["dvPwd"] = "<img style='margin:9px;margin-left:20px;' src='"+imgpath+"images/zhuce2.gif'/>&nbsp;6-16个字母、数字、下划线。";
arrWrong["dvRepwd"]= "<img style='margin:9px;margin-left:20px;' src='"+imgpath+"images/zhuce2.gif'/>&nbsp;未输入或两次输入密码不同。";
arrWrong["dvRec"] = "<img style='margin:9px;margin-left:20px;' src='"+imgpath+"images/zhuce2.gif'/>&nbsp;请输入推荐人的用户名。";
arrWrong["dvCode"] = "<img style='margin:9px;margin-left:20px;' src='"+imgpath+"images/zhuce2.gif'/>&nbsp;验证码位数输入不正确。";
arrWrong["dvPhone"] = "<img style='margin:9px;margin-left:20px;' src='"+imgpath+"images/zhuce2.gif'/>&nbsp;请输入正确的手机号";

var arrOk = new Array();
arrOk["dvUser"] = "<img style='margin:2px;' src='"+imgpath+"images/zhuce3.gif'/>&nbsp;";
arrOk["dvPwd"] = "<img style='margin:2px;' src='"+imgpath+"images/zhuce3.gif'/>&nbsp;";
arrOk["dvRepwd"] = "<img style='margin:2px;' src='"+imgpath+"images/zhuce3.gif'/>&nbsp;";
arrOk["dvRec"] = "<img style='margin:2px;' src='"+imgpath+"images/zhuce3.gif'/>&nbsp;";
arrOk["dvCode"] = "<img style='margin:2px;' src='"+imgpath+"images/zhuce3.gif'/>&nbsp;";
arrOk["dvPhone"] = "<img style='margin:2px;' src='"+imgpath+"images/zhuce3.gif'/>&nbsp;";


function Init() {
    $('#txtUser').click(function() { ClickBox("dvUser") });
    $('#txtPwd').click(function() { ClickBox("dvPwd") });
    $('#txtRepwd').click(function() { ClickBox("dvRepwd") });
	$('#txtRec').click(function() { ClickBox("dvRec") });
    $('#txtCode').click(function() {
        if($("#txtCode").val()==''){
        ClickBox("dvCode")
        } });
    $('#txtUser').blur(function() { BlurUName(); });
    $('#txtPwd').blur(function() { BlurPwd(); });
    $('#txtRepwd').blur(function() { BlurRepwd(); });
	$('#txtRec').blur(function() { BlurRec(); });

   // $('#txtCode').blur(function() {BlurCode(); });

}
$(document).ready(
function() {
    //$('#dvRec').html('<font style="color:red">填写推荐人用户名，没有推荐人可不填。</font>');
    Init();
    $("#Img1").keypress(
        function (e) {
            if (e.keyCode == "13")
                $("#Img1").click();

        });
    $('#txtCode').keypress(function(){
        if (e.keyCode == "13")
            resendMobileValidSMSCode()
    })

})
function strLength(as_str){
		return as_str.replace(/[^\x00-\xff]/g, 'xx').length;
}
function isLegal(str){
	if(/[!,#,$,%,^,&,*,?,~,\s+]/gi.test(str) || str == '请输入用户名' ) {
        return false;
    }
	return true;
}
function BlurUName() {
    var txt = "#txtUser";
    var td = "#dvUser";
    var aos = new RegExp("^[0-9]+$");
    var pat = new RegExp("^[\\d|\\.a-z_A-Z|\\u4e00-\\u9fa5|\\x00-\\xff]$", "g");
    var str = $(txt).val();
    var length=str.length;
    ///^[\u4E00-\u9FA5][a-zA-Z0-9_]{4,20}$/
    var strlen = strLength(str);
    for(var i=0;i<length;i++){
        if(str.charAt(i)>'~'){
            length=length+1;
        }
    }
    if (isLegal(str) && length >= 4 && length <= 20) {
        if (aos.test(str)) {
            $(td).html(GetP("reg_wrong2", "<img style='margin:9px;margin-left:20px;' src='" + imgpath + "images/zhuce2.gif'/>&nbsp;用户名不能全部为数字。"));
            return false;
        }
        if (!str.match(/^[\w\u4E00-\u9FA5]+$/)){
            $(td).html(GetP("reg_wrong2", "<img style='margin:9px;margin-left:20px;' src='" + imgpath + "images/zhuce2.gif'/>&nbsp;请输入符合规则的用户名"));
            return false;
        }

        $(td).html(GetP("reg_info2", "<img style='margin:9px;margin-left:20px;' src='" + imgpath + "images/zhuce0.gif'/>&nbsp;正在检测用户名……"));
        $.ajax({
            type: "post",
            async: false,
            url: "/member/common/ckuser/",
            dataType: "json",
            data: {"UserName": str},
            timeout: 3000,
            success: AsyncUname
        });

} else {
        $(td).html(GetP("reg_wrong2", arrWrong["dvUser"]));
        $('#phonecard').hide();
    }
}
function BlurRec() {
    var txt = "#txtRec";
    var td = "#dvRec";
    var pat = new RegExp("^[a-zA-Z0-9_]*$", "g");
    var str = $(txt).val();
    var strlen = strLength(str);
	if (isLegal(str) && strlen>=3 && strlen<=20) {
		$(td).html(GetP("reg_info2", "<img style='margin:9px;margin-left:20px;' src='"+imgpath+"images/zhuce0.gif'/>&nbsp;正在检测推荐人……"));
		$.ajax({
			type: "post",
			async: false,
			url: "/member/common/ckInviteUser/",
			dataType: "json",
			data: {"InviteUserName":str},
			timeout: 3000,
			success: AsyncInviteUname
		}
		);
	}else if(str==''){
		$(td).empty();
    }
    else {
        $(td).html(GetP("reg_wrong2", arrWrong["dvRec"]));
    }
}
function AsyncUname(data) {
    if (data.status == "1") {
        $("#dvUser").html(GetP("reg_ok", arrOk["dvUser"]));
        $('#phonecard').show();
        $('#dvCode').show();
    }
    else {
        $("#dvUser").html(GetP("reg_wrong2", "<img style='margin:9px;margin-left:20px;' src='"+imgpath+"images/zhuce2.gif'/>&nbsp;此用户名已被注册。"));

    }

}
function AsyncInviteUname(data) {
    if (data.status == "1") {
        $("#dvRec").html(GetP("reg_ok", arrOk["dvRec"]));
    }
    else {
        $("#dvRec").html(GetP("reg_wrong2", "<img style='margin:9px;margin-left:20px;' src='"+imgpath+"images/zhuce2.gif'/>&nbsp;此推荐人不存在。"));

    }

}
function getPassWord() {
	window.location.href = "/member/common/getpassword/";
}

function BlurPwd() {
    var txt = "#txtPwd";
    var td = "#dvPwd";
    var pat = new RegExp("^[a-zA-Z0-9_]{6,16}$", "i");
    var str = $(txt).val();
    if (pat.test(str)) {
        //格式正确
        $(td).html(GetP("reg_ok", arrOk["dvPwd"]));
    }
    else {
        $(td).html(GetP("reg_wrong2", arrWrong["dvPwd"]));
    }
}

function BlurRepwd() {
    var txt = "#txtRepwd";
    var td = "#dvRepwd";
    var pat = new RegExp("^[a-zA-Z0-9_]{6,16}$", "i");
    var str = $(txt).val();
    if (str == $("#txtPwd").val() && str.length > 5) {
    	if (!pat.test(str)){
    		$(td).html(GetP("reg_wrong2", arrWrong["dvPwd"]));
    	}
    	else {
    		//格式正确
            $(td).html(GetP("reg_ok", arrOk["dvRepwd"]));
    	}
    }
    else {
        $(td).html(GetP("reg_wrong2", arrWrong["dvRepwd"]));
    }
}
//检验 验证码
var ss;
function BlurCode() {
    var txt = "#txtCode";
    var td = "#dvCode";
    var pat = new RegExp("^[\\da-z]{4}$", "i");
    var str = $(txt).val();
    if (pat.test(str)) {
        //格式正确
        //$(td).show();
        $.post("/member/common/ckcode/", { Action: "post", Cmd: "CheckVerCode", sVerCode: str }, AsyncVerCode);
    }
    else {
        $(td).html(GetP("reg_wrong2", arrWrong["dvCode"]));
        return false;
    }
}
function AsyncVerCode(data) {
    var dataObj=eval("("+data+")");//转换为json对象
    if (dataObj.code == "1") {
        if( validateCode == true ) {
            $("#dvCode").html(GetP("reg_ok", arrOk["dvCode"]));
        }else{
            $("#dvCode").html(GetP("reg_ok", arrOk["dvCode"]));
            validateCode = true;
        }
    }
    else {
        $("#dvCode").html(GetP("reg_wrong2", arrBox["dvCode"]));
        ss=$('#imVcode').attr('src');
        $('#imVcode').attr('src',ss+'?t='+Math.random());
        validateCode = false;
        return false;
    }
}

function ClickBox(id) {
    var ele = '#' + id;
    $(ele).html(GetP("reg_info2", arrBox[id]));
}

function GetP(clsName, c) {return "<div class='" + clsName + "'>" + c + "</div>"; }
var ee=true;
function dialogClose(obj, masking)
{
    obj.hide();
    $("#txtCode").val('');
    $("#zctc-error").html('');
    $("#zctc-error").hide();
    ss=$('#imVcode').attr('src');
    $('#imVcode').attr('src',ss+'?t='+Math.random());
    if (masking != false) {
        $("fixed").remove()
    }
};
function RegSubmit(ctrl) {
    $(ctrl).unbind("click");
    var arrTds = new Array("#dvUser", "#dvPwd","#dvRepwd", "#dvCode", "#dvRec");
    BlurUName();
    BlurPwd();
    BlurRepwd();
    BlurRec();
    if(validateCode == false){

        BlurCode();
        return false;
    }
    if($('#phonecard').show()==true){
        $("#dvCode").show();
    }

    for (var i = 0; i < arrTds.length; i++) {
        if ($(arrTds[i]).html().indexOf('reg_wrong2') > -1) {
            $(ctrl).click(function() { RegSubmit(this); });
            return false;
        }
    }
    var check = $("input[type='checkbox']").attr("checked");
	if(!check){
		layer.msg("请确认服务协议",{icon:0});
		return false;
  	}
	layer.msg('提交中...', {icon: 16});
	$.ajax({
		url: curpath+"/regtemp/",
        data: {"txtUser": $("#txtUser").val(),"txtPwd": $("#txtPwd").val(),"sVerCode": $("#txtCode").val(),"txtRec": $("#txtRec").val()},
        cache: false,
		type: "post",
		dataType: "json",
		success: function (d, s, r) {
			if(d){
				if(d.status==0){
					layer.msg(d.message,{icon:2});
                }else{
					window.location.href="/member/common/register2/";
				}
			}
		}
	});
}
function myrefresh()
{
 window.location.href="/member/";
}

function BlurPhone() {
    var txt = "#txt_phone";
    var td = "#dvPhone";
    var pat =/^(1)[0-9]{10}$/;
    var str = $(txt).val();

    if (pat.test(str)) {
        //$(td).html(GetP("reg_ok", arrWrong["dvPhone"]));
        $.ajax({
            type: "post",
            async: false,
            ataType: "html",
            url: "/member/common/ckphone/",
            data: {"phone":str},
            timeout: 3000,
            success: AsyncPhone
        });
    }else {
        $(td).show().html(GetP("reg_wrong2", arrWrong["dvPhone"]));
    }
}
function AsyncPhone(data) {
    var td = "#dvPhone";
    if (data==1) {
        //$("#dvPhone").html(GetP("reg_ok", arrOk["dvPhone"]));
        //$('#phonecard').show();
        reset_sendSMSTip();

    }else{
        $wrong="<img style='margin:9px;margin-left:20px;' src='"+imgpath+"images/zhuce2.gif'/>&nbsp;手机号已注册。";
        $(td).show().html(GetP("reg_wrong2", $wrong));

    }
}
