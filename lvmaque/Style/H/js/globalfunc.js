var ERROR=0,UPTXT=1,ALARM=2;//报错,更新html,提示窗

//检测是否是空白
function isBlank(n){ var i=0;if (typeof(n)=="undefined")return true;while (n.length>i){ if (n.charAt(i)!=' '&&n.charAt(i)!='　')return false;i++; }return true; }
//检测是否是纯数字
function isNumber(n,dot,sym){
	var i=0,tb=[0,0,0,0,0,0,0,0,0,0];
	if (isBlank(dot)==true||dot==true){ tb['.']=0; }//包含小数
	if (isBlank(sym)==true||sym==true){ if (n.charAt(0)==='-'){ i=1; } }//包含负数
	while (n.length>i){ if (typeof(tb[n.charAt(i++)])=="undefined")return false;}
	return true;
}
//报告数组中成员的名称和值
function chkArray(arr){ for (var i in arr){ alert(i+" = "+arr[i]); } }
//转换时间参数
function calTime(start,end){
	if (isBlank(start)===true&&isBlank(end)===true){ return false; }
	var ustart=" 00:00",uend=" 23:59";
	if (isBlank(start)===true){ return "lt`"+end+uend; }
	if (isBlank(end)===true){ return "gt`"+start+ustart; }
	return "between`"+start+ustart+","+end+uend;
}
//ajax更新数据
function updateSpan(o,span,url){
	if (isBlank(url)===true){
		url=o.attr("data");
	}
	$.ajax({
		url: url,
		timeout: 5000,
		cache: false,
		type: "get",
		dataType: "json",
		success: function(d, s, r){
			if(d){
				if(d.status==UPTXT&&isBlank(span)===false){
					$(span).html(d.message);
				}
				else if (d.status==ALARM){
					alert(d.message);
				}
				else if (d.status==ERROR){
					alert(d.message);
				}
			}
		}
	});
}
//格式化输出资金数额
function fmtMoney(o){
	var fmt=o.attr("data");
	var arr=o.text().split('.');
	if (isBlank(arr[0])===true){ arr[0]="0"; }
	if (arr.length==1&&isNumber(arr[0],false)===true){ arr[1]="000"; }
	if (arr.length==2&&isNumber(arr[0],false)===true&&isNumber(arr[1],false)===true){
		switch (fmt){
			case "dot_2":
				o.html(arr[0]+"."+(arr[1]+"000").slice(0,2));
				break;
			default:
				o.html("<strong>"+arr[0]+"</strong>. <i>"+(arr[1]+"000").slice(0,2)+"</i>元");
				break;
		}
	}
}
//页面切换
function onTabClick(o,att,cls,flx){
	if (isBlank(cls)===true){ cls="a"; }
	if (isBlank(att)===true){ att="_cur"; }
	if (isBlank(flx)===true){ flx="#flash_table"; }
	var p=o.parents().find("["+att+"]"),cur=p.attr(att);
	if (isBlank(cur)===true){ p=o.parent();cur="selected"; }
	if (o.hasClass(cur)){ return; }
	p.find(cls).removeClass(cur);
	o.addClass(cur);
	$(flx).attr("_text",o.text());
	$(flx).trigger("click");
}
//设置弹窗响应函数
function setPopAction(arr){
	arr.alignY = (isBlank(arr.alignY) === true) ? "top" : arr.alignY;
	arr.alignX = (isBlank(arr.alignX) === true) ? "right" : arr.alignX;
	arr.txt_plus = (isBlank(arr.txt_plus) === true) ? "'记录'" : arr.txt_plus;
	arr.txt_prev = (isBlank(arr.txt_prev) === true) ? "暂时无" : arr.txt_prev;
	arr.txt_default = (isBlank(arr.txt_default) === true) ? "投标记录" : arr.txt_default;
	$(arr.obj).each(function(index){
		var o = $(this);
		o.poshytip({
			alignX: arr.alignX,
			alignY: arr.alignY,
			showTimeout: 100,
			liveEvents: !0,
			content: function(updateCallback){
				var ajaxFun, transactionData = {},
				pageFn = doT.template(document.getElementById(arr.temp_id).text, void 0),
				transactionAjax = function(txt){
					var i, val, n = 0, params = "";
					for (i in arr.attr){
						val=o.attr(arr.attr[i]);
						if (typeof(val)!=="undefined" && isBlank(val)===false){ params+=i+"="+val+"&"; }
					}
					for (i in arr.param){
						params+=i+"="+arr.param[i]+"&";
					}
					ajaxFun = $.ajax({
						url: arr.url, //必填
						type: "GET",
						dataType: "json",
						data: params,
						success: function(data){
							var dataValue;
							0 == data.code ? (transactionData = data.data, dataValue = data.data.length > 0 ? data.data : ["", arr.txt_prev + (txt ? txt + eval(arr.txt_plus) : arr.txt_default)]) : dataValue = 0;
							updateCallback(pageFn(dataValue));
						},
					});
					return "拼命加载中...";
				};
				return transactionAjax("");
			}
		});
	});
}
//设置点击响应函数
function setClickAction(arr,flash){
	$(arr.obj).each(function(index){
		var o=$(this);
		o.unbind("click");
		o.bind("click",function(){
			var i,val,n=0,params="";
			if (typeof(arr.check)!=="undefined"&&isBlank(arr.check.func)===false){
				for (i in arr.check.param.attr){
					val=o.attr(arr.check.param.attr[i]);
					if (typeof(val)!=="undefined"&&isBlank(val)===false){ params+=val+",";}
				}
				for (i in arr.check.param.param){
					params+=arr.check.param.param[i]+",";
				}

				val=eval(arr.check.func+"("+params+"0)");
				if (val!==true){ return; }
			}
			params="";
			for (i in arr.attr){
				val=o.attr(arr.attr[i]);
				if (typeof(val)!=="undefined" && isBlank(val)===false){ params+=i+"="+val+"&"; }
			}
			for (i in arr.param){
				params+=i+"="+arr.param[i]+"&";
			}
			for (i in arr.select){
				val=$(arr.select[i]).val();
				if (typeof(val)!=="undefined" && isBlank(val)===false){ params+=i+"="+val+"&"; }
			}
			ajaxFun = $.ajax({
				url: arr.url, //必填
				type: "GET",
				dataType: "json",
				data: params,
				success: function(data){
					if (data.code===ERROR||data.code===ALARM){
						//alert(data.data.msg);
						layer.msg(data.data.msg, {icon: 2});
					}
					else if (data.code===UPTXT){
						//alert(data.data.msg);
						layer.msg(data.data.msg, {icon: 1});
						if (isBlank(arr.flash)===false){ $(arr.flash).trigger("click"); }
					}
				}
			});
		});
	});
}

//highcharts插件
    var zz = zz || {};
	zz.userIndex = {},
		zz.userIndex.init = function (indexData) {

			this.creatChart(indexData, 3)
			this.bindEvents(indexData)
		},
		zz.userIndex.bindEvents = function (userIndexData) {
			this.lineChartMonthSwitch(userIndexData)
		},
		zz.userIndex.lineChartMonthSwitch = function (userIndexData) {

			var that = this,
				$container = $("#container");
			$("#conLine .classify-selected-1-click a").click(function () {
				var $this = $(this);
				if ($this.hasClass("selected")) return !1;
				$("#conLine .classify-selected-1-click a").removeClass("selected"),
					$this.addClass("selected");
				var type = parseInt($this.attr("_type"));
				$.ajax({
					url: userIndexData.lineUrl,
					type: "GET",
					dataType: "json",
					data: {
						type: type
					},
					beforeSend: function () {
						$container.html('<img src="' + userIndexData.loadImgPath + '" style="margin:120px 45%"/>')
					},
					success: function (data) {
						if (0 === data.code) {
							var d = data.data;
							that.creatChart(d, type)
						} else $container.html('<span style="display: inline-block; width: 199px; margin: 100px 35%;">网络出错啦，请重试</span>')
					},
					error: function () {
					},
					complete: function () {
					}
				})
			})
		},
		zz.userIndex.creatChart = function (userIndexData, type) {
			var iIsnull = function (data) {
				return data && data.length > 0 ? !1 : !0
			};
			if (iIsnull(userIndexData.line1) && iIsnull(userIndexData.line2)) {
				$("#container").addClass("user-jilu0");
				return $("#container img").remove(); //`mxl 20150310`hide
			}
			void 0;
			var line1Option = {
				credits: {
					enabled: !1
				},
				chart: {
					renderTo: "container"
				},
				title: {
					text: ""
				},
				xAxis: {
					type: "datetime",
					minRange: 2592e6,
					minTickInterval: 2592e6,
					labels: {
						style: {
							color: "#bfbfbf"
						},
						formatter: function () {
							return Highcharts.dateFormat("%y年%w月", this.value)
						},
						x: -5,
						y: 20
					}
				},
				yAxis: {
					title: {
						text: ""
					},
					labels: {
						style: {
							color: "#bfbfbf"
						},
						formatter: function () {
							return Highcharts.numberFormat(this.value, 0, ".") + "元"
						},
						y: -2
					},
					gridLineWidth: 0,
					lineWidth: 1,
					tickWidth: 1,
					min: 0
				},
				tooltip: {
					formatter: function () {
						return Highcharts.dateFormat("%y年%w月", this.x) + "<br/>" + this.series.name + '<span style="color:#fe6e00">' + Highcharts.numberFormat(this.y, 2, ".") + "</span>元"
					}
				},
				legend: {
					enabled: !0,
					layout: "horizontal",
					align: "center",
					verticalAlign: "top",
					x: 270,
					y: -3,
					borderWidth: 0,
					symbolWidth: 30,
					itemStyle: {
						cursor: "default"
					}
				},
				plotOptions: {
					line: {
						point: {
							events: {
								click: function () {
									!this.y > 0 || (location.href = "/member/borrowin?year=" + new Date(this.x).getFullYear() + "&month=" + (new Date(this.x).getMonth() + 1))
								}
							}
						},
						events: {
							legendItemClick: function () {
								return !1
							}
						}
					},
					series: {
						lineWidth: 2
					}
				},
				series: userIndexData.series //`mxl 20150310`
			};
			Highcharts.dateFormats = {
				w: function (timestamp) {
					var date = new Date(timestamp),
						month = date.getMonth() + 1;
					return month;
				}
			},
				new Highcharts.Chart(line1Option)
		},

		zz.timer = {},
		zz.timer.init = function () {
			$.datepicker.setDefaults({changeMonth: true, showButtonPanel: true});
			$(".pairtimer").each(function () {
				var ostart = $(this).find(".startone"),
					oend = $(this).find(".endone");
				ostart.datepicker({
					onClose: function (n) {
						oend.datepicker("option", "minDate", n);
						if (isBlank(n)) {
							ostart.trigger("change");
							return;
						}
					}
				});
				oend.datepicker({
					onClose: function (n) {
						ostart.datepicker("option", "maxDate", n);
						if (isBlank(n)) {
							oend.trigger("change");
							return;
						}
					}
				});
			});
		},
		zz.userAction = {},
		zz.userAction.init = function (userData) {

			userData.data = (isBlank(userData.data) === true) ? {} : userData.data;
			userData.txt_plus = (isBlank(userData.txt_plus) === true) ? "'记录'" : userData.txt_plus;
			userData.txt_prev = (isBlank(userData.txt_prev) === true) ? "暂时无" : userData.txt_prev;
			userData.txt_default = (isBlank(userData.txt_default) === true) ? "任何记录" : userData.txt_default;
			userData.transfer_type = (isBlank(userData.transfer_type) === true) ? "GET" : userData.transfer_type;
			userData.loading_gif_id = (isBlank(userData.loading_gif_id) === true) ? "#lodingGif_1" : userData.loading_gif_id;
			var ajaxFun, transactionData = {},
				pageFn = doT.template(document.getElementById(userData.temp_id).text, void 0),
				transactionAjax = function (txt) {
					//`mxl 20150317`
					var i, val, n = 0, params = "";
					for (i in userData.attr) {
						val = $(userData.tabs[userData.attr[i].tab].obj).filter("." + userData.tabs[userData.attr[i].tab].cur).attr(userData.attr[i].key);
						if (typeof(val) !== "undefined" && isBlank(val) === false) {
							params += i + "=" + val + "&";
						}
					}
					for (i in userData.timer) {
						val = calTime($(userData.timer[i]).find(".startone").val(), $(userData.timer[i]).find(".endone").val());
						if (val !== false) {
							params += i + "=" + val + "&";
						}
					}
					for (i in userData.select) {
						val = $(userData.select[i]).val();
						if (typeof(val) !== "undefined" && isBlank(val) === false) {
							params += i + "=" + val + "&";
						}
					}
					for (i in userData.sort) {
						val = $(userData.sort[i]).attr("_sort");
						if (typeof(val) !== "undefined" && isBlank(val) === false) {
							params += "sort=" + i + "," + val + "&";
						}
					}
					for (i in userData.param) {
						params += i + "=" + userData.param[i] + "&";
					}
					//`mxl 20150317`
					ajaxFun = $.ajax({
						url: userData.url, //必填
						type: userData.transfer_type,
						dataType: "json",
						data: params,
						success: function (data) {
							var dataValue;
							0 == data.code ? (transactionData = data.data, dataValue = data.data.length > 0 ? data.data : ["", userData.txt_prev + (txt ? txt + eval(userData.txt_plus) : userData.txt_default)]) : dataValue = 0,
								$(userData.disp_id).html(pageFn(dataValue)),
							$(userData.loading_gif_id).length > 0 && $(userData.loading_gif_id).remove();
							var repaySum = $('#repayList').attr('data-sum');
							if (repaySum == 'undefined') {
								repaySum = '0.00';
							}
							$(".ffA").html(repaySum);
							for (var i in userData.sort) {
								$(userData.sort[i]).unbind("click");
								$(userData.sort[i]).bind("click", function () {
									var s = $(this).attr("_sort");
									$(".sortSomething").attr("_sort", "");
									$(".sortSomething").find("span").attr("class", "sort_N");
									s = (isBlank(s) === true) ? "ASC" : ((s === "ASC") ? "DESC" : "ASC");
									$(this).attr("_sort", s);
									$(this).find("span").attr("class", "sort_" + s);
									ajaxFun.abort();
									transactionAjax($(this).text());
								});
							}
							for (var i in userData.pops) {
								setPopAction(userData.pops[i]);
							}
							for (var i in userData.click) {
								setClickAction(userData.click[i]);
							}
						}
					})
				};
			for (var i in userData.select) {
				$(userData.select[i]).unbind("change");
				$(userData.select[i]).bind("change", function () {
					ajaxFun.abort();
					transactionAjax($(this).find("option:selected").text());
				});
			}
			for (var i in userData.timer) {
				$(userData.timer[i]).find("input").unbind("click");
				$(userData.timer[i]).find("input").bind("click", function () {
					$(this).val("");
					$(this).attr("_time", "");
				});
				$(userData.timer[i]).find("input").unbind("change");
				$(userData.timer[i]).find("input").bind("change", function () {
					ajaxFun.abort();
					transactionAjax("");
				});
			}
			$(userData.flash).click(function (){
				ajaxFun.abort();
				transactionAjax($(this).attr("_text"));
			})
			transactionAjax("")
		};