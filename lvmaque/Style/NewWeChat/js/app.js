$(function() {
    $("#fixed-footer a").on("click",function(){
        $(this).parent().addClass("active").siblings().removeClass("active");
    })
})
