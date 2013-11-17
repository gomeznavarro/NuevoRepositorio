<?php
//constantes conexión base de datos
define( "DB_DSN", "mysql:dbname=mypractico;host=localhost" );
define( "DB_USERNAME", "root" );
define( "DB_PASSWORD", "140573" );

//tablas utilizadas en la base de datos
define( "TBL_MEMBERS", "members" );
define( "TBL_ACCESS_LOG", "accessLog" );
define( "TBL_ARCHIVOS", "archivos_ruta" );
define( "TBL_FICHEROS", "ficheros" );

//número de resultados en tabla de ficheros (y en members de momento)
define( "PAGE_SIZE", 200 );

//clase Language a utilizar en LenguageController
define("LANGUAGE_CLASS","Language.class.php");

	
?>
