$(function(){
	var curHref= location.href.split('/').pop();
	$("#investNav>li>a").each(function(){
	    if($(this).attr("href").split('/').pop() === curHref){
	        var color = "<span class=\"am-icon-check\" style=\"color:red;\"></span>";
	        $(this).append(color);
	    }
	})
})