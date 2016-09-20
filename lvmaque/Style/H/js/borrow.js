function checkday(){
	var is_check = $("#is_day").attr("checked");
	if(is_check){
		var option = $("#_day_option").html();
		$("#repayment_type option:eq(1)").attr('selected',true);
		$("#repayment_type").attr('disabled',true);
		//$("#_day_rate").html("日利率：").css("color","red");
		//$("input[name='borrow_interest_rate']").css("border","1px solid red");
	}
	else{
		var option = $("#_month_option").html();
		$("#repayment_type option:eq(0)").attr('selected',true);
		$("#repayment_type").attr('disabled',false);
		$("input[name='borrow_interest_rate']").removeAttr("style");
		$("#_day_rate").html("年利率：").removeAttr("style");
	}
	$("#borrow_duration").empty();
	$(option).appendTo("#borrow_duration");
}

function is_reward_do(){
	var is_check = $("#is_reward").attr("checked");
	if(is_check){
		$("#_is_reward").slideDown();
	}
	else{
		$("#_is_reward").slideUp();
	}
}

function is_reward_vouch_do(){
	var is_check = $("#is_reward_vouch").attr("checked");
	if(is_check){
		$("#_is_reward_vouch").slideDown();
	}
	else{
		$("#_is_reward_vouch").slideUp();
	}
}

function is_moneycollect_do(){
	var is_check = $("#is_moneycollect").attr("checked");
	if(is_check){
		$("#_is_moneycollect").slideDown();
	}
	else{
		$("#_is_moneycollect").slideUp();
		$("#moneycollect").val('');
	}
}

function reward_type_do(id){
	$("#reward_type_"+id).attr("checked",true);
}

function test_duration(){
	var type = $("#repayment_type").val();
	var month = $("#borrow_duration").val();
	var is_day = $("#is_day").attr("checked");
	if(type==3 && month%3!=0){
		alert("选择按季分期还款时，借款期限必须为3的整倍数，请重新填写借款期限或者选择别的还款方式");	
		$("#repayment_type option:eq(0)").attr("selected",true);
	}else if(type==1 && !is_day){
		alert("选择按天到期还款时，必须勾选 '按天'，请重新填写借款期限或者选择别的还款方式");	
		$("#repayment_type option:eq(0)").attr("selected",true);
	}
}
//返回数字
function NumberCheck(t){
	var num = t.value;
	var re=/^\d*$/;
	if(!re.test(num)){
		isNaN(parseInt(num))?t.value=0:t.value=parseInt(num);
	}
}

//返回数字，带小数
function NumberFloatCheck(t){
	var num = t.value;
	var re=/^\d+\.?\d*$/;
	if(!re.test(num)){
		isNaN(parseFloat(num))?t.value='':t.value=parseFloat(num);
	}
}