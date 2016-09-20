/**********投资记录***********/
function investRecord(id){
	$.get(rooturl+"investRecord?borrow_id="+id,function(data){
		//alert(data);
		layer.open({
			type: 1,
			title: ['投资记录',
				'background-color: #FC4949;'
			],
			area: ['100%', '350px'],
			fix: true, //不固定
			maxmin: false,
			content: data
		});
	});	
}

/****************项目详情******************/
function borrowaboutus(id){
	var pageii=layer.open({
			type: 2,
			title: ['借款详情',
				'background-color: #FC4949;'
			],
			area: ['100%', '100%'],
			fix: true, //不固定
			maxmin: false,
			content: rooturl+"borrow_aboutus?id="+id,
			//btn: ['确认', '取消']
		});
}

/*******优惠券*******/
function coupons_youhui(id){
	var pageii=layer.open({
			type: 2,
			title: ['优惠券',
				'background-color: #B53354;'
			],
			area: ['100%', '350px'],
			fix: true, //不固定
			maxmin: false,
			content: rooturl+"Coupons.html",
			//btn: ['确认', '取消']
		});
}

function youhui_false(){
	$("#yhq_money").val('');
}