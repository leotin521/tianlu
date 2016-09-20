// JavaScript Document
//幻灯片Du
$(document).ready(function(){
 function HuanDeng(da,xiao){
	var index=0;
var width=$(da).width();
	var height=$(da).height();
	$(da).each(function(){
		var index=$(this).index()+1;
	 $(xiao).append('<li>'+index+'</li>');
		});
		var xiao2=$("li",xiao);
		 $(da).hide().first().show();
	 xiao2.first().addClass("BB");
function sj(){
		 var nn=Math.ceil(Math.random()*10);
		 var index4=index-1;
		 if(index==0)index4=len-1;
		 switch(nn)
		{
			case 1:
		clear();
$(da).eq(index4).css({"z-index":"2"});
$(da).eq(index).css({"z-index":"3","left":width+"px"}).show().animate({left:'0px'});
     break;
		   case 2:
		  clear();
 $(da).eq(index4).css({"z-index":"2"});
$(da).eq(index).css({"z-index":"3","right":width+"px"}).show().animate({right:'0px'});
break;
		   case 3:
	 clear();
$(da).eq(index4).css({"z-index":"2"});
$(da).eq(index).css({"z-index":"3","bottom":height+"px"}).show().animate({bottom:'0px'});
break;
		    case 4:
	 clear();
$(da).eq(index).css({"z-index":"3"}).hide().slideDown();
$(da).eq(index4).css({"z-index":"2"}).slideUp();
break;
		      case 5:
			   clear();
		  $(da).eq(index).css({"z-index":"2"}).hide().slideDown();
		   $(da).eq(index4).css({"z-index":"3"}).show().slideUp();
		   break;
		       case 6:
			    clear();
		  $(da).eq(index).css({"z-index":"3","bottom":height+"px"}).show().animate({bottom:'0px'});
		  $(da).eq(index4).css({"z-index":"2"}).animate({bottom:'-'+height+"px"});
		 break;
		     case 7:
			  clear();
		  $(da).eq(index).css({"z-index":"3","top":height+"px"}).show().animate({top:'0px'});
		  $(da).eq(index4).css({"z-index":"2"}).animate({top:'-'+height+"px"});
		 break;
		 case 8:
		  clear();
	$(da).eq(index).css({"z-index":"3","right":width+"px"}).show().animate({right:'0px'});
	$(da).eq(index4).css({"z-index":"2"}).animate({right:'-'+width+"px"});
		break;
		 case 9:
		  clear();
	$(da).eq(index).css({"z-index":"3","left":width+"px"}).show().animate({left:'0px'});
	$(da).eq(index4).css({"z-index":"2"}).animate({left:'-'+width+"px"});
		break;
	     case 10:
		  clear();
		 $(da).hide().eq(index).css({"z-index":"3"}).fadeIn(1000);
		  $(da).eq(index4).css({"z-index":"2"}).show().fadeOut();
		break;
}
		 }
		 function clear(){
			 $(da).css({"z-index":"","position":"absolute","left":"","right":"","bottom":"","top":""});
		 }
function jiange(){
          index++;
		   if (index==len ) index=0;
	  sj();
xiao2.eq(index).addClass("BB").siblings().removeClass("BB");
}
var C1=3000;//间隔
	var kkk=setInterval(jiange,C1);
	var len=xiao2.length;
	xiao2.mouseover(function(){
	clearInterval(kkk);
		$(this).addClass("BB").siblings().removeClass("BB");
  index=$(this).index();
		sj();
 }).mouseout(function(){
	 kkk=setInterval(jiange,C1);
});
$(da).mouseover(function(){
	clearInterval(kkk);
	}).mouseout(function(){
	 kkk=setInterval(jiange,C1);
});
}
HuanDeng('#JS_huanDeng div','#JS_HDmenu');//首页幻灯播放
HuanDeng('#JS_huanDengs div','#JS_HDmenus');//积分商城幻灯播放
//成功借款项目
var c_wrap=$('#cach');//定义滚动区域 
var c_interval=3000;//定义滚动间隙时间 
var c_moving;//需要清除的动画 
c_wrap.hover(function(){ 
clearInterval(c_moving);//当鼠标在滚动区域中时,停止滚动 
},function(){ 
c_moving=setInterval(function(){ 
var c_field=c_wrap.find('li:first');//此变量不可放置于函数起始处,li:first取值是变化的 
var c_h=c_field.height();//取得每次滚动高度 
c_field.animate({marginTop:-c_h+'px'},600,function(){c_field.css('marginTop',0).appendTo(c_wrap);}) 
},c_interval)//滚动间隔时间取决于c_interval 
}).trigger('mouseleave');//函数载入时,模拟执行mouseleave,即自动滚动 
});
//排行榜
function showTa(type,obj){
				$("#pmlist a").removeClass("hover");
				$(obj).addClass("hover");
				if(type=="day"){
					$("#showDay").show();
					$("#showMo").hide();
					$("#showWeek").hide();
				}else if(type=="week"){
					$("#showDay").hide();
					$("#showMo").hide();
					$("#showWeek").show();
				}else{
					$("#showDay").hide();
					$("#showMo").show();
					$("#showWeek").hide();
				}
			}
			
			function showTail(type,obj){
				$("#state_info_nav a").removeClass("current");
				$(obj).addClass("current");
				if(type=="userintro"){
					$("#userintro").show();
					$("#picintro").hide();
					$("#record").hide();
					$("#comment").hide();
				}else if(type=="picintro"){
					$("#userintro").hide();
					$("#picintro").show();
					$("#record").hide();
					$("#comment").hide();
				}else if(type=="record"){
					$("#userintro").hide();
					$("#picintro").hide();
					$("#record").show();
					$("#comment").hide();
				}else{
					$("#userintro").hide();
					$("#picintro").hide();
					$("#record").hide();
					$("#comment").show();
				}
			}