<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Documento sin título</title>
<script type="text/javascript" src="jquery-1.8.2.min.js"></script>
<script type="text/javascript" src="import.js"></script>
</head>

<body>
<?php

	require_once("UploadController.php");


	$upload= new UploadController();

   if( isset($_GET['action'])&& $_GET['action']== 'save_file') {
 	//saves the csv file to the fichero's folder
   	$upload->actionSave();
 }
elseif( isset($_GET['action'])&& $_GET['action']== 'actionImport_csv' && isset($_GET['nombre'])){
 	//reads the file and records the data onto the database
   	$upload->actionImport_csv();
 }
else{
//$upload->display();
if(isset($_GET['confirmado']) && $_GET['confirmado']==3){
	echo "No es un tipo permitido, suba otro";
}
elseif(isset($_GET['confirmado']) && $_GET['confirmado']==4){
	echo "El tamaño máximo es 500kb, suba otro";
}
elseif(isset($_GET['confirmado']) && $_GET['confirmado']==5){
	echo "Ha habido algún problema con el archivo, suba otro";
}


?>
		<form enctype="multipart/form-data" action="upload_files.php?action=save_file" method="post" id="registro">
                 <div style="width:300px">
                    <fieldset>
               
                    <label for="archivito"><?php _("Browse your file")?></label> 
                    <input id="f" style="width:300px; float:right; margin-top:5px" type="file" name="archivito" />
                    </fieldset>
                    
                    <input type="submit" name="submitButton" id="submitButton" value="Enviar archivo" />
                    <div style="clear: both"></div>
                 </div>
            	</form>
		
		<!--<form>
<input id="f" name="f" type="file" />
</form>--->
<div id="hola"></div>
		
		<?php
}
?>

</body>
</html>

