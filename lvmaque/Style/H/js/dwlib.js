var dw = {
    lps: location.pathname.split("/"),
    on:"on",
    hover:"js_hover",
    //URL参数
    //URL变色 1.0
    urlcolor: function(a, b) //(a,index)
    {
        var b1 = b || 1,
            a1 = a.attr("href").split("/")[b1],
            c = dw.lps[b1];
     
        c == a1 && a.addClass(dw.on);
},
        //经典导航 子 a ul 2.1
    nav: function(r,xiala) {
        a = $(r).children();
        var first = a.first().children("a"),
        index,
        p3=$(r).offset().left,
  w3 = $(r).innerWidth();
        "" == dw.lps[1] && first.addClass(dw.on);
        a.each(function() {
   var a1 = $(this).children("ul"),
                a2 = $(this).children("a"),
                w1 = a1.innerWidth(),
                w2 = $(this).innerWidth(),
                a3 = a1.children().length,
                p2=a2.offset().left;
              //有料
              //a3 && a2.addClass('smbhs_youliao');
             //定位
                   if (w1 > w2)
            {
            var pp1=w1 - w2;
   pp=pp1 *0.5;
   a1.css('left', -pp);
/*             var p4=p2-pp;
            if(p4 <p3)
            a1.css('left',p3-p2);
            else if(p4+w1 > p3+w3)
            {
            a1.css('left',-(pp-(p3+w3-(p4+w1))));
            } */
      }
   //URL变色

                dw.urlcolor(a2);
                var aid = a2.attr("id"),
                ahref=a2.attr("href").split("/")[1];
        ( aid && dw.lps[1] == aid || "tinvest" == dw.lps[1] && 'invest'== ahref || 'debt'== dw.lps[1] && 'invest'== ahref ) && a2.addClass(dw.on);
        //if (a3) {
     if(!xiala && a2.hasClass(dw.on) )
                    {
                    a3 && a1.show();
                    index=$(this).index();
                    index=$(a[index]).children("ul");
                   }
            //鼠标
  $(this).mouseenter(function() {
                   index && index.hide();
                    a3 && a1.fadeIn(250);
    a2.addClass(dw.hover);
    }).mouseleave(function() {
                    a3 && a1.hide();
      index && index.show();
                    a2.removeClass(dw.hover);
                });
            //}else
           // !a3 && a2.css("background",'none');
                            //a1.children().last().css({"border":"0"});
  });
    },
    //迷你导航	//父,子,子(show),子(子)1.0
    erji: function(a, b, c, d) {
        $(a).children(b).each(function() {
            var a1 = $(this).children(c),
             b2 = $(this).children(d),
             index=$(this).index();
            if (a1.html()) $(this).hover(function() {
                a1.show();
                
            index==0 && $(this).css({'background-position':'0px -62px'});
            index==1 && $(this).css({'background-position':'0px -124px'});
                b2.css({

                    'color':'#cfcfcf'
                });
            }, function() {
                a1.hide();
                 index==0 && $(this).css({'background-position':'0px -31px'});
            index==1 && $(this).css({'background-position':'0px -93px'});
                b2.css({

                    'color':'#A7A7A7'
                });
            });
        });
    },
//幻灯片4.1 大 小
HuanDeng:function(a,e,o){if(!$(a).length)return;o=o||{};var sd=o.sd||5000,sy=o.style||'',num=o.num||false,nozhong=o.nozhong||0,test={"z-index":"2"};function m(c){var e=sy||Math.ceil(10*Math.random()),c=c||b-1;0>c&&(c=n-1);switch(e){case 1:d();$(a).eq(c).css({"z-index":"2"});$(a).eq(b).css({"z-index":"3",left:f+"px"}).show().animate({left:"0px"});break;case 2:d();$(a).eq(c).css({"z-index":"2"});$(a).eq(b).css({"z-index":"3",right:f+"px"}).show().animate({right:"0px"});break;case 3:d();$(a).eq(c).css({"z-index":"2"});$(a).eq(b).css({"z-index":"3",bottom:g+"px"}).show().animate({bottom:"0px"});break;case 4:d();$(a).eq(b).css({"z-index":"3"}).hide().slideDown();$(a).eq(c).css({"z-index":"2"}).slideUp();break;case 5:d();$(a).eq(b).css({"z-index":"2"}).hide().slideDown();$(a).eq(c).css({"z-index":"3"}).show().slideUp();break;case 6:d();$(a).eq(b).css({"z-index":"3",bottom:g+"px"}).show().animate({bottom:"0px"});$(a).eq(c).css({"z-index":"2"}).animate({bottom:"-"+g+"px"});break;case 7:d();$(a).eq(b).css({"z-index":"3",top:g+"px"}).show().animate({top:"0px"});$(a).eq(c).css({"z-index":"2"}).animate({top:"-"+g+"px"});break;case 8:d();$(a).eq(b).css({"z-index":"3",right:f+"px"}).show().animate({right:"0px"});$(a).eq(c).css({"z-index":"2"}).animate({right:"-"+f+"px"});break;case 9:d();$(a).eq(b).css({"z-index":"3",left:f+"px"}).show().animate({left:"0px"});$(a).eq(c).css({"z-index":"2"}).animate({left:"-"+f+"px"});break;case 10:d(),$(a).hide().eq(b).css({"z-index":"3"}).fadeIn(1E3),$(a).eq(c).css({"z-index":"2"}).show()}};function d(){$(a).css({"z-index":"",position:"absolute",left:"",right:"",bottom:"",top:""})};function k(){b++;b==n&&(b=0);m();h.eq(b).addClass(dw.on).siblings().removeClass(dw.on)};var b=0,f=$(a).width(),g=$(a).height();$(a).each(function(){var sindex;if(num){sindex=$(this).index()+1;sindex="<li>"+sindex+"</li>"}else sindex="<li></li>";$(e).append(sindex)});var h=$("li",e);$(a).hide().first().show();h.first().addClass(dw.on);var i=setInterval(k,sd),n=h.length;h.mouseover(function(){clearInterval(i);$(this).addClass(dw.on).siblings().removeClass(dw.on);var a=b;b=$(this).index();m(a)}).mouseout(function(){i=setInterval(k,sd)});$(a).mouseover(function(){clearInterval(i)}).mouseout(function(){i=setInterval(k,sd)});if(!nozhong){var j=$(e).parent().width();var j=(j-$(e).width())/2;$(e).css("left",j)}},
keys:function(a,b){
function keyUp(e) {
           var currKey=0,e=e||event;
            currKey=e.keyCode||e.which||e.charCode;
  if(currKey==13){
 document.getElementById(b).click();
 }
          }
   document.getElementById(a).onkeydown = keyUp;
},
//mouseimage 1.0
mouseimage:function(ca,cb){
var smbhs_MM= $(ca);
smbhs_MM.each(function(){
var ap=$(this).css('background-position');
if (typeof (ap) === "undefined")//IE兼容
{
ap=$(this).css('background-position-x');
ap+=" "+$(this).css('background-position-y');
}
cb= cb || $(this).innerHeight()+1;
var a=ap.split(" "),
a1=parseInt(a[1])-cb,
a1=a1+"px",
b=a[0]+" "+a1;
$(this).mouseenter(function(){
$(this).css('background-position',b);
}).mouseleave(function(){
$(this).css('background-position',ap);
});
});
},
    huadong:function(a,o){
    var a3=$(a).children("ul"),
	a1=a3.children('li'),
    a2=$(a).children("div").children();
    a1.mouseenter(function(){
$(this).addClass('on').siblings().removeClass('on');
     var index=$(this).index();
    a2.hide().eq(index).show();
    if(o){
    var a3=a3.children("a");
	index==0  &&  a3.attr('href',o.url1);
	index==1 &&   a3.attr('href',o.url2);
	}
});
    },
	donghua:function(a){
	a=$(a).children();
	a.each(function(){
	var a1=$(this), a2=a1.children().eq(0);
a1.mouseenter(function(){
a2.animate({top:'-5px'},300).animate({top:'0px'},50);
}).mouseleave(function(){
a2.animate({top:'0px'},100).stop();
});
	});
},
scrollNav:function(id){
var a=$(id),a1=a.children(),as=a.offset().top,ds=$(document).scrollTop();
$(window).scroll(function(){ 
ds=$(document).scrollTop();
	if(ds>as)
	{
	a.addClass('on');
	}else{
	a.removeClass('on');
	}

});
	a1.each(function(){
	var t=$(this),hreff=t.attr('hreff'),hreff=$(hreff),hreffT=hreff.offset().top-90;
	$(window).scroll(function(){ 
ds=$(document).scrollTop();
		if(ds>=hreffT-10 && ds < hreffT+100)
		{
			t.addClass('on').siblings().removeClass('on');
		}
	});
	t.click(function(){
		$('html,body').animate({scrollTop:hreffT});
	});
});
},
H5_xuanzhuan:function(a,o){
o = o || {};
var n1=o.jd || 0,rotINT1,
jg=o.jg || 10,sd=o.sd || 5;
clearInterval(rotINT1);
rotINT1=setInterval(setRotate1,jg);
function setRotate1(){
n1=n1+sd;
a[0].style.transform="rotate(" + n1 + "deg)"
a[0].style.webkitTransform="rotate(" + n1 + "deg)"
a[0].style.OTransform="rotate(" + n1 + "deg)"
a[0].style.MozTransform="rotate(" + n1 + "deg)";
if (n1==180 || n1==360)
	{
	clearInterval(rotINT1)
	//if (n1==360){n1=0}
	}
}
}
    
};
