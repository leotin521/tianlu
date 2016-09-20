function plus(id){
	var cnum = parseInt($("#tnum_"+id).val());
	cnum++;
	$("#tnum_"+id).val(cnum+'元');
}
function minus(id){
	var cnum = parseInt($("#tnum_"+id).val());
	cnum=(cnum-1)>0?(cnum-1):1;
	$("#tnum_"+id).val(cnum+'元');
}

function round2(floatData,i){
	var i=i+1;
	var floatStr = (floatData)+"";
	var index = floatStr.indexOf(".");
	if(index!=-1){
			return floatStr.substring(0,(index+i));	
	}
	else
		return floatStr;
}
function windowReload() {
    location.reload();
}


/*企业直投流程*/

var T_transfer_num = 0;
var T_month_min = 0;
var T_month_max = 0;
function Transfer(id){
    if( $('#J_transfer').attr('disabled') == 'disabled') return false;
	var num = $("#tnum_"+id).val();
	if(!/^[0-9]+([.]{1}[0-9]{1,2})?$/.test(num)){
		layer.msg("投资金额只能是数字，请重新输入",{icon:2});
		return false;
	}
   $.ajax({
        url: Transfer_invest_url+"/ajax_invest",
        type: "get",
        dataType: "json",
        data: {"id":id,"num":num},
        success: function(d) {
            if (d.status == 1) {
                $('#tcc').remove();
                $('.ajax-invest').remove();
                $("body").append(d.content);
                $('#investForm').slideDown(200);
                $('#tcc').css({"height": $(document).height()+"px"})
            }else{
               $("#tnum_"+id).val(d.money);
				layer.msg(d.message,{icon:2});
            }
        }
    });
}

function tanchu(id,ziduan){
	
	$.jBox("get:"+Transfer_invest_url+"/ajax_tanchu?id="+id+"&ziduan="+ziduan, {
		title: "详情",
		width: "auto",
		buttons: {}
	});
}
function sumTMoney(obj){
	obj.value=obj.value.replace(/[^0-9]/g,'');
	var tnum = parseInt($("#transfer_invest_num").val());
	var per = parseInt($("#per_transfer").val());
	var need = parseInt($("#need_num").text());
	if (tnum>need){ tnum=need;$("#transfer_invest_num").val(tnum); }
	var total = tnum*per;
	total = isNaN(total)?0:total;
	$("#total_transfer_money").html(total);
}

function showTMoney(){
	var rate=parseInt($("#year_interest").text());
	var reward_rate=parseInt($("#reward_rate").text());
	var days=parseInt($("#borrow_duration").text());
	var tnum = parseInt($("#transfer_invest_num").val());
	var per = parseInt($("#per_transfer").val());
	var total = tnum;
	var unit = parseInt($("#T_time_unit").val());
	total = isNaN(total)?0:total;
	var interest = parseFloat(rate)*total*days/(unit*100);
	var reward = parseFloat(reward_rate)*total/100;
	$("#except_income").html(round2(interest+reward,2));
	$("#interest_income").html(round2(interest,2));
	$("#reward_income").html(round2(reward,2));
}

function T_PostData() {
	var tnum = parseInt($("#transfer_invest_num").val());
	var per = parseInt($("#per_transfer").val());
	var total = tnum;
	total = isNaN(total)?0:total;
	var pin = $("#T_pin").val();
  	var borrow_id = $("#T_borrow_id").val();
	if(pin==""){
		layer.msg("请输入支付密码",{icon:0});
		return false;
	}
	if($("#pay_nxp").attr('checked') == false){
		layer.msg("您必须同意协议",{icon:0});
		return false;
	}
	$.ajax({
		url: Transfer_invest_url+"/investcheck",
		type: "post",
		dataType: "json",
		data: {"money":total,'pin':pin,'borrow_id':borrow_id},
		success: function(d) {
                                                        if (d.status == 1) {
                                                                        isinvest(true);
                                                                    return true;
                                                        } else{// 支付密码错误
                                                                //$.jBox.tip(d.message);
																layer.msg(d.message,{icon:2}); 
                                                        }
		}
	});
}

function ischarge(d){
	if(d===true) window.location.href="/member/charge#fragment-1";
}
function isinvest(d){
	if(d===true) document.forms.investForm.submit();
}
/*企业直投流程*/
/*定投宝流程*/
function F_PostData() {
	var tnum = parseInt($("#transfer_invest_num").val());
	var per = parseInt($("#per_transfer").val());
	var total = tnum;            //按金额
	total = isNaN(total)?0:total;
	var pin = $("#T_pin").val();
  	var borrow_id = $("#T_borrow_id").val();
	if(pin==""){
		layer.msg("请输入支付密码",{icon:0});
		return false;
	}
	if($("#pay_nxp").attr('checked') == false){
		layer.msg("您必须同意协议",{icon:0});
		return false;
	}
	$.ajax({
		url: Transfer_invest_url+"/investcheck",
		type: "post",
		dataType: "json",
		data: {"money":total,'pin':pin,'borrow_id':borrow_id},
		success: function(d) {
			if (d.status == 1) {
                                                                            isinvest(true);
                                                                              return true;
			} else{// 支付密码错误
                                                                            //$.jBox.tip(d.message,"error");
																			layer.msg(d.message,{icon:2}); 
                                                                    }
		}
	});
}
function FTransfer(id){
	var chooseWay = $("input[name='radios']:checked").val();
	if(chooseWay==null){
		layer.msg("请选择利息使用方式",{icon:0});
    	return false;
	}
	var num = $("#tnum_"+id).val();
                        $.ajax({
                        url: Transfer_invest_url+"/ajax_invest",
                        type: "GET",
                        dataType: "json",
                        data: {"id":id,"num":num,"chooseWay":chooseWay},
                        success: function(d) {
                            if (d.status == 1) {
                                $('#tcc').remove();
                                $('.ajax-invest').remove();
                               $("body").append(d.content);
                                $('#investForm').slideDown(200);
                               $('#tcc').css({"height": $(document).height()+"px"})
                            }else{
								layer.msg(d.message,{icon:2});
                            }
                        }
                    });
}
function showFMoney(rate,reward_rate,increase_rate,chooseway,shouyi4,shouyi6){
	var tnum = parseInt($("#transfer_invest_num").val());
	var month = parseInt($("#transfer_invest_month").val());
                        month = isNaN(month)?0:month;
	var total = tnum;
		total = isNaN(total)?0:total;
    alert(total);
	var chooseway = parseInt(chooseway)
	var interest_rate = parseFloat(rate);
	var interest;
	if(chooseway == 4){
	    interest = parseFloat(tnum*shouyi4);
	}else if(chooseway == 6){
	    interest = parseFloat(tnum*shouyi6);
	}
	var reward = parseFloat(reward_rate)*total/100;
	$("#year_interest").html(interest_rate);
	$("#except_income").html("￥"+round2((interest+reward),2));
	$("#interest_income").html("￥"+round2(interest,2));
	$("#reward_income").html("￥"+round2(reward,2));
}
/*定投宝流程*/
function bindpagebar(){
	$('.ajaxpagebar a').unbind().click(function(){
		try{	
			var geturl = $(this).attr('href');
			var id = $(this).parent().attr('data');
			var x={};
			$.ajax({
				url: geturl,
				data: x,
				timeout: 5000,
				cache: false,
				type: "get",
				dataType: "json",
				success: function (d, s, r) {
					if(d) $("#"+id).html(d.html);//更新客户端竞拍信息 作个判断，避免报错
				}
			});
		}catch(e){};
		return false;
	})
}
