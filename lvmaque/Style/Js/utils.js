$(function() {

    (function() {
        var time = 0;
        $(".navigation-item").mouseenter(function() {
            var that = $(this);
            clearTimeout(time);
            time = setTimeout(function() {
                var next = that.find(".navigation-list-two-con"),
                width = that.width(),
                eleWidth = next.width(),
                cha = (eleWidth - width) / 2;
                that.addClass("select");
                next.css("left", -cha);
                next.find(".nav-sanjiao").css("left", eleWidth / 2 - 6);
				if(next.find("ul li").get(0) == undefined){
				}else{
					next.stop().fadeIn(0);
					}
            },
            0);
        }).mouseleave(function() {
            var that = $(this),
            listTwo = that.find(".navigation-list-two-con");
            clearTimeout(time);
            setTimeout(function() {
                if (that.hasClass("select")) {
                    that.removeClass("select");
                    that.find(".navigation-list-two-con").stop().fadeOut(0);
                }
            },
            0);
        });
    })();
});

//为表单项设置默认的title
function SetDefaultTitle(id){
	var id = "#"+id
	var title = $(id).attr("data");
    $(id).val(title).css("color", "#8D8C8C").click(function() {
        if ($(this).val() == title)
        { $(this).val("").css("color", "#000"); }
    }).blur(function() {
        if ($(this).val().length < 1)
        { $(this).val(title).css("color", "#8D8C8C"); }
    });
}
function checkFloatType(t) {
    var num = t.value;
    var re=/^\d{0,20}(\.)\d*$/;
    if(!re.test(num)){
        isNaN(parseFloat(num))?t.value='':t.value=parseFloat(num);
    }
}
