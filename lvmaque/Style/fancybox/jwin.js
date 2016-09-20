 
 function preventDefault(e){
		if(document.all)window.event.returnValue=false;
		e.preventDefault();
		return false;
 }
 
 