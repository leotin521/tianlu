var itz = itz || {};
itz.util = itz.util || {};
(function(){
	//sidebar导航
	$(".user-account-nav-item").click(function(){
		var li = $(this).parent();
		var ul = li.find("ul");
		var height = ul.height();
		if(!ul.data("data-height")){
			ul.data("data-height",height);
		}
		if(ul.is(":visible")){
			ul.animate({height: '0px'},230,function(){ul.hide()});
		}else{
			ul.show();
			ul.css({height: '0px'});
			ul.animate({height: ul.data("data-height")+'px'},230);
		}
	});
	//sidebar的tips
	$('.user-info-status span').tip({
        words_per_line:1000
    });
})();

// 对Date的扩展，将 Date 转化为指定格式的String
// 月(M)、日(d)、小时(h)、分(m)、秒(s)、季度(q) 可以用 1-2 个占位符， 
// 年(y)可以用 1-4 个占位符，毫秒(S)只能用 1 个占位符(是 1-3 位的数字) 
// 例子： 
// (new Date()).Format("yyyy-MM-dd hh:mm:ss.S") ==> 2006-07-02 08:09:04.423 
// (new Date()).Format("yyyy-M-d h:m:s.S")      ==> 2006-7-2 8:9:4.18 
Date.prototype.Format = function (fmt) { //author: meizz 
    var o = {
        "M+": this.getMonth() + 1, //月份 
        "d+": this.getDate(), //日 
        "h+": this.getHours(), //小时 
        "m+": this.getMinutes(), //分 
        "s+": this.getSeconds(), //秒 
        "q+": Math.floor((this.getMonth() + 3) / 3), //季度 
        "S": this.getMilliseconds() //毫秒 
    };
    if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
    for (var k in o)
    if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
    return fmt;
};
/***
 * itz.util.promptA user弹出框A
 * @promptId 弹出框的容器id
 * @tmplId dot模板的id
 * @data json or array, 数组：['a','b','c','d'], a\b\c都是前几个信息，d是代表状态，比如提交成功还是失败
 */
itz.util.promptA = function(promptId ,tmplId, data, dialogOption){
    var $prompt = $('#'+promptId),
        pagefn = doT.template(document.getElementById(tmplId).text, undefined),
        defaultOpt = {width:460};
    dialogOption = dialogOption ? $.extend({}, defaultOpt, dialogOption) : defaultOpt;
    if (!$prompt.length){
        $prompt = $('<div id="'+ promptId +'" style="display:none"></div>');
        $('body').append($prompt);
    }
    $prompt.html(pagefn(data));
    $prompt.dialog({
        dialogClass:"clearPop pop-style-1",
        bgiframe: true,
        modal: true,
        resizable:false,
        closeOnEscape: false,
        show: {
            effect: 'fadeIn',
            duration: 450
        },
        open:function(){
            
        },
        width: dialogOption.width,
        close: dialogOption.close
    });
};

/**
 * ajaxPager ajax分页
 * options
 * @pager 相关分页信息，例如rn,pn,tn,nn等
 * @ajaxHostUrl 请求地址的host
 * @loadImgPath loading图片的path
 * @container 内容容器的id
 * @pagination 分页导航的父的id
 */
itz.util.ajaxPager = function(options){
    var pager = options.pager,
        pageInfo = pager.pageInfo,//传入的分页类相关数据
        num_entries = pageInfo.nn ? pageInfo.nn : 1,//代表页数
        $container = $('#'+options.container),//内容区
        $p = $("#"+options.pagination),//分页的父容器
        $loading = $('<div style="height:100%;width:100%;position:absolute;left:0;top:0;text-align:center"><img style="top:50%;position:absolute;margin-top:-16px" src="'+ options.loadImgPath +'"/></div>'),
        currentAjaxRequset;//当前的ajax request
        
    if(num_entries>1){//大于1页时，显示分页区
        $p.fadeIn();
    }

    //点击分页按钮的回调函数
    function pageSelectCallback(page_index, $page){
        if(currentAjaxRequset){//
            currentAjaxRequset.abort();
        }
        currentAjaxRequset = $.ajax({
        	url: options.ajaxHostUrl + '?page=' + (page_index+1) + (pager.tmpl ? '&type=' + pager.tmpl : '') + (pager.tmpl_param ? options.paramsUrl : ''),
//            url: options.ajaxHostUrl + '?page=' + (page_index+1) + (pager.tmpl ? '&type=' + pager.tmpl : ''),
            dataType: 'html',
            timeout: 10000,
            beforeSend: function(xhr){
                $container.append($loading);
            },
            success: function(data){
                $container.html(data);
            },
            error: function(xhr){
                $loading.remove();
                if(xhr.statusText="abort"){
                    return;
                }
                alert('您的网络有问题，请刷新页面再试一下');
            }
        });
        return false;
    }
    //初始化分页
    $p.pagination(num_entries, {
        callback: pageSelectCallback,
        load_first_page: false,//第一次不执行ajax分页
        items_per_page:1, // Show only one item per page
        next_text: '&gt;',//下一页的文字
        prev_text: '&lt;'//上一页的文字
    });
    
    return {
        pageLoad: pageSelectCallback //回调函数
    };//返回对象
    
};
/**
 * itz.util.getBorrowTip
 * @$tips 目标node，jquery对象
 * @type 类型，默认是json，其它则为ajax的模板名称
 * @tipAjaxUrl ajax请求地址
 */
itz.util.getBorrowTip = function($tips, type, tipAjaxUrl){
    if(!tipAjaxUrl){
        tipAjaxUrl = '/newuser/ajax/GetBorrowInfo';
    }
    if(!type){
        type = 'borrow_detail';
    }
    $tips.poshytip({
        //showOn: 'none',
        //target: 'target',
        alignY: 'bottom',
        showTimeout: 100,
        liveEvents: true,
        content: function(updateCallback) {
            var $this = $(this),
                id = $this.attr('_id'),
                cid = $this.attr('_cid');//tend_id
            
                $.ajax({
                    url: tipAjaxUrl + '?id=' + id + '&type=' + type + '&cid=' + cid,
                    dataType: 'html',
                    success: function(data){
                        updateCallback(data);
                    }
                });
            return '拼命加载中...';
        }
    });    
};
/**普通手续费计算
 * @money 金额
 * @fee 费率:0.005，千五  
 */
itz.util.getFee = function(money, fee){
    if( !money || !fee ){
        return '缺少金额或费率';
    }
    return (money * fee).toFixed(2);
};
/**
 * 倒计时按钮
 * @$phone 手机号所在的容器，jquery类型;如果不需要手机号，比如找回支付密码，可以输入undefined
 * @url 验证地址
 * @time倒计时开始秒
 */
$.fn.itzCutDownBtn = function($phone, url, time){
    var iTime,origTime,
        iUrl = url || '/newuser/ajax/PhoneCheck',
        $that = $(this),
        st,
		yuyin_wrapper = $that.closest('li').siblings('.yuyin-channel'),
		vnum = $('.js_vnum');
	
	iTime = origTime = time || 60;

	var yflag = yuyin_wrapper.length>0?true:false;
	//短信验证码
    this.click(function(){
		yflag&&yuyin_wrapper.hide();
        var $t = $(this),
            num = $phone===undefined ? 0 : $phone.val();//undefined
        
        if(!num && $phone!==undefined){
            checkAlert($t, '请填写手机号~');
            return;
        }
        if(!/^1\d{10}$/.test(num) && $phone!==undefined){
            checkAlert($t, '手机格式不正确~'); 
            return;
        }
        $t.attr('disabled','true').removeClass('btn-style-1').addClass('btn-style-2').val('发送中……');
		//向后台发送处理数据
        $.ajax({
            type: "POST", //用POST方式传输
            dataType: "JSON", //数据格式:JSON
            url: iUrl, //目标地址
            data: "sms=" + num + '&num=' + (vnum.length>0?vnum.val():0),
            error: function (XMLHttpRequest, textStatus, errorThrown){
                checkAlert($t, '网络错误~');
				if(yflag) {
					yuyin_wrapper.show();
					yuyin.removeAttr('disabled').removeClass('voicechdis');
				}
            },
            success: function (data){
                if(data.code==0){
                    $t.val(iTime + '秒后可重新获取');
					if(yflag) {
						yuyin.attr('disabled', true).addClass('voicechdis');
						yuyin_wrapper.show();
						i0.show();
						i1.hide();
						i3.hide();
						yct.show().html('在 '+iTime+'秒 后');
					}

                    st = setInterval(function(){
                        if(iTime===1){
                            clearInterval(st);
                            $t.removeAttr('disabled').removeClass('btn-style-2').addClass('btn-style-1');
                            $t.val('重新获取短信验证码');
							yflag&&yct.hide();
							yflag&&yuyin.removeAttr('disabled').removeClass('voicechdis');
                            iTime = origTime;
                            return;
                        }

                        $t.val(--iTime + '秒后可重新获取');
						yflag&&yct.html('在 '+iTime+'秒 后');
                    }, 1000);    
                }else if(data.code==5 && $phone!==undefined){
                    checkAlert($t, '该手机号已被认证过~');					
                }else{
                    checkAlert($t, data.info);
                }
            }
        });
        
    });

	//语音验证码
	if(yflag) {
		var yuyin = yuyin_wrapper.find('input'),
			i0 = yuyin_wrapper.find('.js_info0'),
			i1 = yuyin_wrapper.find('.js_info1'),
			i2 = yuyin_wrapper.find('.js_info2'),
			i3 = yuyin_wrapper.find('.js_info3'),
			yct = yuyin_wrapper.find('.yyct'),
			sms = yuyin_wrapper.siblings().find('.sms-channel');

		yuyin.click(function(){
	        var $t = $(this),
    	        num = $phone===undefined ? 0 : $phone.val();//undefined
        
        	if(!num && $phone!==undefined){
            	//checkAlert($t, '请填写手机号~');
	            return;
    	    }
        	if(!/^1\d{10}$/.test(num) && $phone!==undefined){
            	//checkAlert($t, '手机格式不正确~'); 
	            return;
    	    }
        	$t.attr('disabled','true').addClass('voicechdis');
			sms.attr('disabled', true).removeClass('btn-style-1').addClass('btn-style-2');

		var da = yuyin.data('type') ? {Voice: 'true'} : {sms: num, Voice: 'true'};
			da['num'] = vnum.length>0?vnum.val():0
	        //向后台发送处理数据
    	    $.ajax({
        	    type: "POST", //用POST方式传输
            	dataType: "JSON", //数据格式:JSON
	            url: iUrl, //目标地址
    	        data: da,
        	    error: function (XMLHttpRequest, textStatus, errorThrown){
            	    checkAlert($t, '网络错误~');
	            },
    	        success: function (data){
        	        if(data.code==0){
						i0.hide();
						i1.show();
						i2.show();
						i3.show();
						yct.show().html('在 '+iTime+'秒 后');
						sms.val(iTime+'秒后获取短信验证码');
	                    st = setInterval(function(){     
                        	if(iTime===1){
                            	clearInterval(st);
	                            $t.removeAttr('disabled').removeClass('voicechdis');
				                sms.removeAttr('disabled').removeClass('btn-style-2').addClass('btn-style-1');
								yct.hide();
	                            iTime = origTime;
								sms.val('获取短信验证码');
    	                        return;
        	                }
	                        yct.html('在 '+(--iTime)+'秒 后');
							sms.val(iTime+'秒后获取短信验证码');
	                    }, 1000);    
    	            }else if(data.code==5 && $phone!==undefined){
        	            checkAlert($t, '该手机号已被认证过~');
						$t.removeAttr('disabled').removeClass('voicechdis');
				        sms.removeAttr('disabled').removeClass('btn-style-2').addClass('btn-style-1');
            	    }else{
                	    checkAlert($t, data.info);
						$t.removeAttr('disabled').removeClass('voicechdis');
				        sms.removeAttr('disabled').removeClass('btn-style-2').addClass('btn-style-1');
	                }
	            }
        	});
        
	    });

	}

    //错误提示
    function checkAlert($t, errorText){
		if(!$t.data('voice')) {
	        $t.attr('disabled','true').removeClass('btn-style-1').addClass('btn-style-2').val(errorText);
    	    setTimeout(function(){
        	    $t.removeAttr('disabled').removeClass('btn-style-2').addClass('btn-style-1').val('继续获取短信验证码');
	        },2000); 
		} else {
			$t.val(errorText);
    	    setTimeout(function(){
        	    $t.val('语音验证码').removeAttr('disabled').removeClass('voicechdis');
	        },2000); 
			
		}
    };
    return {
        reset: function(){
            clearInterval(st);
            iTime = time || 60;
            $that.removeAttr('disabled').removeClass('btn-style-2').addClass('btn-style-1').val('获取验证码');
			yflag&&yuyin_wrapper.hide();
        }
    };
};
/*
 * array 转 json
 * 将[[k,v],[k,v],....]转为json
 */
itz.util.parseArrayToJson = function(arr) {
    var tp = Object.prototype.toString;

    if(tp.call(arr).slice(8,-1) !== 'Array') return {};

    if(arr.length == 0) return {};

    var rst = {},
        db = {},
        ct = 0;

    $.each(arr, function(k, v){
        if(v.length >= 2) {
            rst[v[0]] = v[1].toString();
            db[v[1]] = v[0].toString();
            ct++;
        }
    });

    return {
        result: rst
        , converse: db
        , count: ct
    };
}
