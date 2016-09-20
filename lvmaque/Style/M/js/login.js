var arrBox = new Array();
arrBox["dvUser"] = "<span style='margin-left:7px;text-align:center;font-size:14px;font-weight:bold;display:block;float:left;width:20px;height:33px;'>!</span>&nbsp;请填写登录账号！";
arrBox["dvCode"] = "<span style='margin-left:7px;text-align:center;font-size:14px;font-weight:bold;display:block;float:left;width:20px;height:33px;'>!</span>&nbsp;请按照图片显示内容输入验证码！";
arrBox["dvPwd"] = "<span style='margin-left:7px;text-align:center;font-size:14px;font-weight:bold;display:block;float:left;width:20px;height:33px;'>!</span>&nbsp;请填写您的密码！";

var arrWrong = new Array();
arrWrong["dvUser"] = "<img style='margin:9px;' src='"+imgpath+"images/zhuce2.gif'/>&nbsp;账号格式填写错误！";
arrWrong["dvCode"] = "<img style='margin:9px;' src='"+imgpath+"images/zhuce2.gif'/>&nbsp;验证码填写错误！";
arrWrong["dvPwd"] = "<img style='margin:9px;' src='"+imgpath+"images/zhuce2.gif'/>&nbsp;密码填写错误！";

arrWrong["login"] = "<img style='margin:9px;' src='"+imgpath+"images/zhuce2.gif'/>&nbsp;账号或密码填写错误！";
arrWrong["code"] = "<img style='margin:9px;' src='"+imgpath+"images/zhuce2.gif'/>&nbsp;账号或密码填写错误！";
var arrOk = new Array();
arrOk["dvUser2"] = "<img style='margin:2px;' src='"+imgpath+"images/zhuce3.gif'/>";
arrOk["dvPwd2"] = "<img style='margin:2px;' src='"+imgpath+"images/zhuce3.gif'/>";
arrOk["dvCode2"] = "<img style='margin:2px;' src='"+imgpath+"images/zhuce3.gif'/>";



function Init() {
    $('#txtUser').click(function() { ClickBox("dvUser"); });
    $('#txtPwd').click(function() { ClickBox("dvPwd"); });

    $('#txtCode').click(function() { ClickBox("dvCode"); });
    $('#txtCode').blur(function() { BlurCode(); });

    $('#txtUser').blur(function() { BlurEmail(); });
    $('#txtPwd').blur(function() { BlurPwd(); });

}

function BlurCode() {
    var txt = "#txtCode";
    var td = "#dvUser";
    var td2 = "#dvCode2";
    var pat = new RegExp("^[\\da-z]{4}$", "i");
    var str = $(txt).val();
    if (pat.test(str)) {
        $(td).hide();
        $(td2).show().html(GetP("reg_ok", arrOk["dvCode2"]));
        return true;
    } else {
        $(td).show();
        $(td2).hide();
        $(td).html(GetP("reg_wrong", arrWrong["dvCode"]));
		return false;
    }
}

function strLength(as_str){
		return as_str.replace(/[^\x00-\xff]/g, 'xx').length;
}
function isLegal(str){
	if(/[!,#,$,%,^,&,*,?,~,\s+]/gi.test(str)) return false;
	return true;
}

function BlurEmail() {
    var txt = "#txtUser";
    var td = "#dvUser";
    var str = $(txt).val();
    var pat2 = new RegExp("^[\\w-]+(\\.[\\w-]+)*@[\\w-]+(\\.[\\w-]+)+$", "i");
	strlen = strLength(str);
    if (!isLegal(str) || strlen<4 || strlen>20 || str=='手机/邮箱/用户名'){
        $(td).show().html(GetP("reg_wrong", arrWrong["dvUser"]));
        $("#dvUser2").hide();
        return false;
    }
    $("#dvUser2").show().html(GetP("reg_ok", arrOk["dvUser2"]));
       $(td).hide();
       return true;
}

function BlurPwd() {
    var txt = "#txtPwd";
    var td = "#dvUser";
    var td2 = "#dvPwd2";
    var pat = new RegExp("^.{6,}$", "i");
    var str = $(txt).val();
    if (pat.test(str)) {
        //格式正确
        $(td2).show().html(GetP("reg_ok", arrOk["dvPwd2"]));
        $(td).hide();
        return true;
    }
    else {
        $(td2).hide();
        $(td).show();
        $(td).html(GetP("reg_wrong", arrWrong["dvPwd"]));

		return false;
    }
}

(function($) {
    $(function() {
        Init();
        $("#form1").keypress(
        function(e) {
            if (e.keyCode == "13")
                $("#btnReg").click();
        });
    });
})(jQuery);

function ClickBox(id) {
    var ele = '#' + id;
    $(ele).html(GetP("reg_info", arrBox[id]));
}

function getQueryString(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
    var r = window.location.search.substr(1).match(reg);
    if (r != null) return unescape(r[2]); return null;
}

function GetP(clsName, c){return "<div class='" + clsName + "'>" + c + "</div>"; }
var redirectUrl = getQueryString("redirectUrl");
//var validateCode = false;
function LoginSubmit(ctrl) {
    $(ctrl).unbind("click");
    var arrTds = new Array("#dvUser", "#dvPwd", "#dvCode");
    BlurEmail();
    if(BlurEmail()==false){
        return false;
    }else{
        BlurPwd();
        if( BlurPwd()==false){
        return false;
    }else{
        BlurCode();
        if(BlurCode()==false){
            return false;
        }
    }
    }


    var keep = 0;
    if ($("#states").attr("checked") == true) {
        keep = 1;
    }
    var txtRem = 0;
    if ($("#txtRemember").attr("checked") == true) {
    	txtRem = 1;
    }
	layer.msg('登录中...', {icon: 16});
    var ss;
	$.ajax({
		url: curpath+"/actlogin/",
		data: {"sUserName": $("#txtUser").val(),"sPassword": $("#txtPwd").val(),"sVerCode": $("#txtCode").val(),"sVerRem": txtRem},
		timeout: 5000,
		cache: false,
		type: "post",
		dataType: "json",
		success: function (d, s, r) {
			if(d){
				if(d.status==0){
					layer.msg(d.message, {icon: 2});
                    ss=$('#imVcode').attr('src');
                    $('#imVcode').attr('src',ss+'?t='+Math.random());
					return false;
				}else if(d.status==2){
					layer.msg(d.message, {icon: 4});
                    ss=$('#imVcode').attr('src');
                    $('#imVcode').attr('src',ss+'?t='+Math.random());
					return false;
				}else if(d.status==3){
					layer.msg(d.message, {icon: 0});
                    ss=$('#imVcode').attr('src');
                    $('#imVcode').attr('src',ss+'?t='+Math.random());
					return false;
				}else{
					var url = "/member/";
                    if( redirectUrl != null ) {
                        url = redirectUrl;
                    }
					window.location.href = url;					
				}
			}
		}
	});
	
	
}
