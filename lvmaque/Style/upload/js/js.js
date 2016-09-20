

	var target = $(".slide");
	for(var i=0;i<target.length;i++){
		target[i].style.webkitTransition = 'all ease 0.05s';
	}
	target.on('touchstart', function(ev){
		ev.preventDefault();
	});
//	var initialScale = 1;
//	var currentScale;
//
//	target.on('pinchend', function(ev){
//		currentScale = ev.scale - 1;
//		currentScale = initialScale + currentScale;
//		currentScale = currentScale > 2 ? 2 : currentScale;
//		currentScale = currentScale < 1 ? 1 : currentScale;
//		this.style.webkitTransform = 'scale(' + currentScale + ')';
//		log("当前缩放比例为:" + currentScale + ".");
//	});
//	target.on('pinchend', function(ev){
//		initialScale = currentScale;
//	});
    target.pinch({
		pinchIn:function(){
			$(this).css('webkitTransform','scale(1.2)')
		},
		pinchOut:function() {
			$(this).css('webkitTransform','scale(0.5)')
		}
	})
