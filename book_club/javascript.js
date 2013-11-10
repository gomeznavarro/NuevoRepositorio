
$( document ).ready(function(){
$.post("view_amigos.php",function(theResponse){
     document.write(theResponse);
     var obj = $.parseJSON(theResponse);
	 document.write("<br/>");
	 for(var v in obj){
	 		document.write("<b>"+obj[v]['firstName']+"</b>");
			document.write("<hr>");
	  
		  for(i in obj[v]['amigos']){  
			 
			 document.write(obj[v]['amigos'][i]['firstName']); 
			 document.write("<br/>");
		  }
	 document.write("<hr>");
	 }
});
});
