/**
	tip插件,基于jquery1.9.1及以上
	参数：words_per_line >> 为数值时表示tip框一行容纳的字符数；
							为像素值时表示tip框的宽度（若tilte里面有链接即’<a href=""></a>‘时应使用像素值）
				   color >> 边框颜色
				 tip_top >> tip框往上偏移的数值
	用法：$("selector").tip({}})
*/
;(function($){
	var _options = {
		'words_per_line' : '150px',
		         'color' : '#e6e6e6',
		       'tip_top' : 0
	};
	$.fn.extend({
		tip: function(options) {
			getOptions(options);
			insertCssForTip();
			replaceTitle(this);
			_attachEvent(this);
			return this;
		}
	});
	var split_str = function(string,words_per_line) {
	    if(typeof string == 'undefined' || string.length == 0) return '';
	    words_per_line = parseInt(words_per_line);
	    var output_string = string.substring(0,1);
	    for(var i=1;i<string.length;i++) {
	        if(i%words_per_line == 0) {
	            output_string += "<br/>";
	        }
	        output_string += string.substring(i,i+1);
	    }
	    return output_string;
	}
	var title_show_hoverFlag = false;
	var titleMouseOver = function(obj) {
	    if(typeof $(obj).attr('_title') == 'undefined' || $(obj).attr('_title') == '') return false;
	    var title_show = document.getElementById("title_show");
	    if(title_show == null){
	        title_show = document.createElement("div"); 
	        $(title_show).attr('id','title_show');                     
	        $('body').append(title_show);
	        $(title_show).css({
	        	position : 'absolute',
	        	border : 'solid 1px '+_options['color'],
	        	background :　'#FFFFFF',
	        	lineHeight : '18px',
	        	fontSize : '12px',
	        	padding : '10px',
	        	left : '-9999px',
	        	'z-index' : '10000'
	        });
	    }
	    innerHtml= '';
	    var words_per_line = _options['words_per_line'];
	    //判断words_per_line为数字还是像素值
	    if(!(/^\d+px$/.test(words_per_line))){
	    	//格式化成整形值
			words_per_line = parseInt(words_per_line);
			innerHtml = split_str($(obj).attr('_title'),words_per_line);
	    }else{
	    	$(title_show).css('width',words_per_line);
	    	innerHtml = $(obj).attr('_title');
	    }
	    $(title_show).html(innerHtml);

	    var title_sanjiao = document.getElementById("title_sanjiao");
	    if(title_sanjiao == null){
	        title_sanjiao = document.createElement("div");
	        $(title_sanjiao).attr('id','title_sanjiao');
	        $('#title_show').append(title_sanjiao);
	        $(title_sanjiao).css({
	        	position : 'absolute',
	        	height : '10px',
	        	width : '14px',
	        	'z-index' : '10001'
	        });
	    }

	    //显示悬停效果DIV
	    $('#title_show').css('display','block');
	    
	    //获取title_show的尺寸
	    var title_show_width = $("#title_show").width();
	    var title_show_height = $("#title_show").height();

		//根据被hover元素本身的位置来确定tip框的位置
		var top_down = 10;
		var offset = $(obj).offset();
		//目标元素的尺寸
		var ele_height = $(obj).height();
		var ele_width = $(obj).width();
		//内边距
		var padding_height = 20;
		//tip框相对目标元素居中（20为padding）
		title_show.style.left = offset.left + (ele_width-title_show_width-20)/(_options['trangle-position']||2) + "px";

		if(_options['direction']=='up' || (offset.top-$(window).scrollTop()+ele_height+top_down+title_show_height + 25 >= $(window).height())){
			title_show.style.top = (offset.top - top_down - title_show_height - padding_height + _options['tip_top'])+"px";
			//下三角
			$(title_sanjiao).html('<span class="sanjiao sanjiao_fff3">◆</span><span class="sanjiao sanjiao_ddd4">◆</span>');
			title_sanjiao.style.top = title_show_height + padding_height - 9 + 'px';
		}else{
			title_show.style.top = (offset.top + ele_height + top_down - _options['tip_top'])+"px";
			//上三角
			$(title_sanjiao).html('<span class="sanjiao sanjiao_ddd1">◆</span><span class="sanjiao sanjiao_fff2">◆</span>');
			title_sanjiao.style.top = '-10px';
		}
		//小三角颜色（tip边框颜色）
		$(title_sanjiao).find("span[class^='sanjiao sanjiao_ddd']").css('color',_options['color']);
	    
	    //根据被hover元素本身的位置来确定tip框小三角形的位置
	    title_sanjiao.style.left = (title_show_width+20-14)/2 + 'px';
	}
	var hover_flag = false;//判断是否有元素被hover
	var titleMouseOut = function(obj) {
	    var title_show = document.getElementById("title_show");
	    if(title_show == null) return false;
	    if(hover_flag){
	    	return;
	    }
	    title_show.style.display = "none";
	}
	var _attachEvent = function(objs){
	    if(typeof objs != 'object') return false;
	    var current_over;
	    for(i=0;i<objs.length;i++){
	    	$(objs[i]).hover(
	    					function(){
					    		titleMouseOver(this);
					            current_over = this;
					            hover_flag = true;
					    	}
					    	,function(){
					    		var that = this;
					        	current_over = this;
					        	hover_flag = false;
					        	setTimeout(function(){
					        		if(title_show_hoverFlag){
					        			return;
					        		}else{
					        			titleMouseOut(that);
					        		}
					        	},60);
					    	}
	    	);
	    }
	    $("body").delegate("#title_show", "mouseenter", function(){
			title_show_hoverFlag = true;
		});
		$("body").delegate("#title_show", "mouseleave", function(){
			title_show_hoverFlag = false;
	    	titleMouseOut(current_over);
		});
	}
	var replaceTitle = function(objs){
	    for(i=0;i<objs.length;i++){
	    	$(objs[i]).attr('_title',$(objs[i]).attr('title'));
	    	$(objs[i]).removeAttr('title');
	    }
	}
	var addStyleString = function(css){
	    var style=document.createElement('style'); 
	    style.type='text/css'; 
	    try{
	        style.appendChild(document.createTextNode(css));     
	    }catch(ex){
	        style.styleSheet.cssText=css; 
	    }
	    var head=document.getElementsByTagName('head')[0]; 
	    head.appendChild(style); 
	}
	//三角的样式
	var insertCssForTip = function(){
		addStyleString(
			".sanjiao {font-size: 14px;font-family: 宋体, sans-serif;height: 8px;}"
			+ ".sanjiao_ddd1 { position: absolute;left: 0px;top: 0px;z-index: 1;}"
			+ ".sanjiao_fff2 {color: #fff;position: absolute;left: 0px;top: 2px;z-index: 2;}"
			+ ".sanjiao_fff3 {color: #fff;position: absolute;left: 0px;top: 0px;z-index: 2;}"
			+ ".sanjiao_ddd4 {position: absolute;left: 0px;top: 2px;z-index: 1;}"
		);
	};
	var getOptions = function(options){
		$.extend(_options,options);
		var words_per_line = _options['words_per_line'];
	    if(typeof words_per_line == 'undefined') words_per_line = '150px';
	    _options['words_per_line'] = words_per_line;
	}
})(jQuery);
