<?php
	require_once "Conexion.class.php";
class UploadController extends Conexion{
	

	function actionSave(){

				$allowedMime = array("text/comma-separated-values", "text/csv", "application/csv", "application/excel", "application/vnd.ms-excel", "application/vnd.msexcel", "text/anytext");
                $archivo = $_FILES["archivito"]["tmp_name"]; 
                $tamanio = $_FILES["archivito"]["size"];
                $tipo    = $_FILES["archivito"]["type"];
                $nombre  = $_FILES["archivito"]["name"];
                $date=	date( 'Y-m-d' );
				$explode=explode(".", $nombre);
        		$extension = end($explode);
                 
                if ( $archivo != "none" ){
					 
					// Compruebo el tipo de archivo				 
					if (!in_array($tipo, $allowedMime)) {
						//header( "Location: upload_files.php?confirmado=3" );
						echo 3;
					}
					 // Compruebo el tamaño del archivo
					else if ($tamanio > 500000)
					{
						header( "Location: upload_files.php?confirmado=4" );
					}
					//si está todo bien, lo muevo a la carpeta "ficheros"
					else {
						move_uploaded_file($archivo, $_SERVER['DOCUMENT_ROOT']."/import_csv/ficheros/".$nombre);
	 					header( "Location: upload_files.php?action=actionImport_csv&nombre=$nombre" );
	                    }
				}
                else
				//Ha habido un error con el archivo
						header( "Location: upload_files.php?confirmado=5" );
               
	}


	static function getEmails(){
		  				$conn=parent::connect();
                        $qry = "SELECT firstName FROM amigos";
						try{
                        $res = $conn->query( $qry);                    
                        while($fila = $res->fetch()){ 
						$emails[]=$fila['firstName'];
						}
						parent::disconnect( $conn );

						return $emails;
						 } catch ( PDOException $e ) {
						parent::disconnect( $conn );
      					die( "Query failed: " . $e->getMessage() );
    					}
	}
	
	function actionImport_csv() {
	 
		$emails=self::getEmails();
		$nombre= $_GET['nombre']; 
		$file = "ficheros/".$nombre;
		if (($gestor = fopen("$file", "r")) !== FALSE) {
			//Second parameter=0 so that length is unlimited
			$conn = parent::connect();
			while (($data = fgetcsv($gestor, 0, ";"))) {
				if (!in_array($data[1], $emails)) {
					$sql = sprintf('INSERT INTO `amigos` (`id`, `firstName`, `idmember`) VALUES ("%s", "%s", "%s")', $data[0], $data[1], $data[2]);
					try {
					$st = $conn->query( $sql);	
					echo "Inserto $data[1]";	
					echo "<br/>";	
					} catch ( PDOException $e ) {
					parent::disconnect( $conn );
					die( "Query failed: " . $e->getMessage() );
					}
				}
				/* Si no les comunicamos si ya estaba insertado, quitar lo siguiente */
				
				else{
						echo "Ya está $data[1]";	
						echo "<br/>";			
					}
			}
			fclose($gestor);
			parent::disconnect( $conn );
		}
		//borramos el archivo
		unlink ("ficheros/".$nombre);
    
  }
  
}
?>