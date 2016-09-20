;
itz.borrow = {};
itz.borrow.init = function(borrowData){
    this.bindDate(borrowData);
    this.bindDateTwo(borrowData);
    this.orderby();

    $(".icon-xiala").poshytip({
        //showOn: 'none',    
        //target: 'target',
        alignY: 'bottom',
        showTimeout: 100,
        liveEvents: true,
        content: function(updateCallback) {
            if(borrowData.pageType=='lease') { 
                var tpl_type = 'borrow_detail_tpl5';
            } else if (borrowData.pageType=='factoring') {
                var tpl_type = 'borrow_detail_tpl7';
            } else if (borrowData.pageType=='art') {
                var tpl_type = 'borrow_detail_tpl8';
            } else {
                var tpl_type = 'borrow_detail_tpl1';
            }
            var $this = $(this),
                id = $this.attr('_id'),
                cid = $this.attr('_cid'),
                url = borrowData.tipAjaxUrl + '?id=' + id + '&type=' + tpl_type + '&cid=' + cid +((borrowData.pageType&&borrowData.pageType==='abs') ? '&pageType=abs' : '');//tend_id) 
                $.ajax({
                    url: url,
                    dataType: 'html',
                    success: function(data){
                        updateCallback(data);
                    }
                });
            return '拼命加载中...';
        }
    });
    
    //点击详情tip
    $('.icon-detail_show').itzTip({
        //className: 'itz-tip',
        delegate: 'body',
        position: 'left:-160px;*left:-161px;top:41px;*top:25px',
        content: function(updateCallback){
            var $this = $(this),
                cid = $this.attr('_cid');//tend_id
                $.ajax({
                    url: borrowData.collectionUrl + '?tender_id=' + cid,
                    dataType: 'html',
                    success: function(data){
                        updateCallback(data);
                    }
                });
            return '<div class="interest-plan" style="text-align:center">拼命加载中...</div>';           
        }
    });
    $(".tips").tip();
};
var p1 = 0;
function sendUrl(value){
    var params = {},arr=[];
    var production = $.getQueryString('production');
    p1 = production;
    if (production){
        params["production"] = production;
    }
    var guarantors = $.getQueryString('guarantors');
    if (guarantors){
        params.guarantors = guarantors;
    }
    var t = $.getQueryString('t');//投资方式,0=全部,1=直投,2=债券
    if (t){
        params.t = t;
    }
    var as = $.getQueryString('as');//投资方式,0=全部,1=直投,2=债券
    if (as){
        params.as=as;
    }
    var ae = $.getQueryString('ae');//投资时间--结束
    if (ae){
        params.ae=ae;
    }
    var es = $.getQueryString('es');//到期时间--开始
    if (es){
        params.es=es;
    }
    var ee = $.getQueryString('ee');//到期时间--结束
    if (ee){
        params.ee=ee;
    }
    var s = $.getQueryString('k');//还款状态
    if (s){
        params.k=s;
    }
    var order = $.getQueryString('order');
    if (order){
        params.order=order;
    }
    for(var i = 0 ; i < value.length ; i++){
        params[value[i].name] = value[i].value;
    }

    if( params.production && p1 != params.production){
        params.guarantors = 0;   
    }
    for(var arg in params){
        arr.push(arg+"="+params[arg]);
    }
    location.href = location.pathname + '?' + arr.join('&');
}

//投资时间
itz.borrow.bindDate = function(data){
    var $beginDate = $( "#beginDate" ),
        $endDate = $( "#endDate" ),
        oFromTime = $beginDate.val(),
        oToTime = $endDate.val(),
        value1 = "",value2="";
    //绑定时间
    $.datepicker.setDefaults( {dateFormat:"yy-mm-dd"} );
    
    $beginDate.datepicker({
        changeMonth: true,
        onClose: function( selectedDate ) {
            //var $endDate = $('#endDate');
            $endDate.datepicker( "option", "minDate", selectedDate );
            value1 = (selectedDate?selectedDate:"");
            value2 = ($endDate.val()?$endDate.val():"");
            if(value1 == "" || value2 == ""){
                return false;
            }
            sendUrl([{"name":"as","value":value1},{"name":"ae","value":value2}]);
        }
    });
    $endDate.datepicker({
        changeMonth: true,
        onClose: function( selectedDate ) {
            //var $beginDate = $('#beginDate');
            $beginDate.datepicker( "option", "maxDate", selectedDate );
            value1 = ($beginDate.val()?$beginDate.val():"");
            value2 = (selectedDate?selectedDate:"");
            if(value1 == "" || value2 == ""){
                return false;
            }
            sendUrl([{"name":"as","value":value1},{"name":"ae","value":value2}]);
        }
    });
};

//到期时间
itz.borrow.bindDateTwo = function(data){
    var $beginDate = $( "#beginDate2" ),
        $endDate = $( "#endDate2" ),
        oFromTime = $beginDate.val(),
        oToTime = $endDate.val(),
        value3 = "",value4="";
    //绑定时间
    $.datepicker.setDefaults( {dateFormat:"yy-mm-dd"} );
    $beginDate.datepicker({
       changeMonth: true,
        onClose: function( selectedDate ) {
            //var $endDate = $('#endDate');
            $endDate.datepicker( "option", "minDate", selectedDate );
            value3 = (selectedDate?selectedDate:"");
            value4 = ($endDate.val()?$endDate.val():"");
            if(value3 == "" || value4 == ""){
                return false;
            }
            sendUrl([{"name":"es","value":value3},{"name":"ee","value":value4}]);
        }
    });
    $endDate.datepicker({
        changeMonth: true,
        onClose: function( selectedDate ) {
            //var $beginDate = $('#beginDate');
            $beginDate.datepicker( "option", "maxDate", selectedDate );
            value3 = ($beginDate.val()?$beginDate.val():"");
            value4 = (selectedDate?selectedDate:"");
            if(value3 == "" || value4 == ""){
                return false;
            }
            sendUrl([{"name":"es","value":value3},{"name":"ee","value":value4}]);
        }
    });
};

//排序
itz.borrow.orderby = function() {


    var ie=!!window.ActiveXObject;
    var ie6=ie&&!window.XMLHttpRequest;
    var ie8=ie&&!!document.documentMode;
    var ie7=(navigator.appName == "Microsoft Internet Explorer" && navigator.appVersion.match(/7./i)=="7.");

    if (ie && ( ie6 || ie7 )){
        $("#ChanPinLeiXing").change(function(){
            var type = $(this).val();
            sendUrl([{
                "name" : "production",
                "value" : type
            }]);
        }).show();
        $("#BaoZhangJiGou").change(function(){
            var type = $(this).val();
            sendUrl([{
                "name" : "guarantors",
                "value" : type
            }]);
        }).show();
    }else{
        $("#ChanPinLeiXing").selectmenu({
            change: function( event, ui ) {
                sendUrl([{
                    "name" : "production",
                    "value" : ui.item.value
                }]);
            },
            width   :   "226px"
        }).selectmenu("menuWidget").addClass("height-150");
        $("#BaoZhangJiGou").selectmenu({
            change: function( event, ui ) {
                sendUrl([{
                    "name" : "guarantors",
                    "value" : ui.item.value
                }]);
            },
            width   :   "226px"
        }).selectmenu("menuWidget").addClass("height-150");
    }
    $("#HuanKuanZhuangTai a").click(function(){
        sendUrl([{
            "name" : "k",
            "value" : $(this).attr("data-value")
        }]);
        return false;
    });
    $("#XiangMuZhuangTai a").click(function(){
        sendUrl([{
            "name" : "t",
            "value" : $(this).attr("data-value")
        }]);
        return false;
    });
    $(".sort-select").click(function(){
        var that = $(this).find("span"),type = that.attr("data-value");
        var name = type == "type1" ? ["id","ia"] : type == "type2" ? ["md","ma"] : type == "type3" ? ["ad","aa"] : type == "type4" ? ["ed","ea"] : "";
        var value = "";

        if(that.hasClass("sort-shang")){
            value = name[0];
        }else if(that.hasClass("sort-xia")){
            value = name[1];
        }else if(that.hasClass("sort")){
            value = name[0];
        }
        sendUrl([{
            "name" : "order",
            "value" : value
        }]);
    })
};

//tip
$.fn.itzTip = function(options){
    var $t = this,
        defaultOptions;
    defaultOptions = {
        className: 'itz-tip', //tip的class
        delegate: false, //false, '.selector', '#id', 'body'
        position: 'left:0;top:0',//相对点击元素的有relative的父亲的绝对定位的坐标
        content: '[title]' //内容来源('[title]', 'string', element, function(updateCallback){...}       
    };
    options = $.extend(defaultOptions, options);
    
    if(options.delegate){
        $(options.delegate).delegate($t.selector, 'click', handler);
    }else{
        $t.click(handler);
    }
    
    $t.each(function(index,ele){
        $(ele).attr('tipId', index);
    });
    
    function tip($this){
        this.$this = $this;
        this.init();
    };
    
    var PROTOTYPE = tip.prototype;
    PROTOTYPE.init = function init(){
        tip.obj = null;        
    };    
    PROTOTYPE.update = function(data){
        var $this = this.$this;
        //.css({'z-index':100}) for ie6,7 
        $this.parent().css({'z-index':100}).append($('<div class="poptip '+ options.className +'" style="z-index:1000;'+ options.position +'"><span class="poptip-arrow poptip-arrow-top"><em>◆</em><i>◆</i></span></div>').append(data));
    };
    PROTOTYPE.close = function(data){
        var $this = this.$this;
        $this.parent().find('.' + options.className).remove();        
    };/*
    PROTOTYPE.destroy = function(data){
        tip.obj = null;
    };*/
    function handler(){
        var $this = $(this),
            $p = $this.parent(),
            content = options.content == '[title]' ? $this.attr('title') : options.content;
        if($this.attr('tipId') === tip.currentTip){
            return;
        }
        var $tips = $('.' + options.className);        
        if($tips.length){
            $tips.parent().css({'z-index':0});//ie6,7
            $tips.remove();
        }        
        tip.currentTip = $this.attr('tipId');
        tip.obj = new tip($this);
        
        if( typeof content === 'function' ){
            tip.obj.update(content.call(this,function(c){
                var obj = tip.obj;
                obj.close();
                obj.update(c);
            }));
        }else{
            tip.obj.update(content);
        }
        
    }
    $(document).delegate('body', 'click', function(e){
        var $node = $(e.target);
        if($node.attr('tipId') && $node.attr('tipId') === tip.currentTip){
            return;
        }
        var $tips = $('.' + options.className);
        $tips.parent().css({'z-index':0});//ie6,7
        $tips.remove();
        tip.currentTip = null;
    });
    
};
