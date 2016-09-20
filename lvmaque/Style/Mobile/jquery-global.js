$(function(){

     var width = $(document).width()-12;
     $(".main").css('width',width+'px');
    
    $("#minus").click(function(){
        var cnum = parseInt($("#tnum").val());
        cnum=(cnum-1)>0?(cnum-1):1;
        $("#tnum").val(cnum+'份');
    })
    
    $("#plus").click(function(){
        var cnum = parseInt($("#tnum").val());
        cnum++;
        $("#tnum").val(cnum+'份');
    })
    $("#tnum").blur(function(){
        var num = $(this).val().replace(/[^0-9]/ig, "");
        var cnum = parseInt(num); 
        if(cnum < 1 || !cnum){
           cnum = 1;
        }
        $("#tnum").val(cnum+'份');
    })
})



