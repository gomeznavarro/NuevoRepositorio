<?php
/**
 * Controla todo lo relativo a mensajes entre recruiters y candidatos 
 * @class MensajeController
 * @brief Controla todo lo relativo a mensajes entre recruiters y candidatos 
 * @package application.controllers
 */
class MensajeController extends Controller {

    public $defaultAction = '';

    /**
     * @return array action filters
     */
    public function filters() {
        return array(
            'accessControl', // perform access control for CRUD operations
        );
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
        return array(

            array('allow',
                'actions' => array('nuevoAjax','mensajeAgenteAjax','formAgenteAjax','enviaEmailAjax'),
                'users' => array('*')
            ),
            array('allow',
                'actions' => array('listadoAjax'),
                'users' => array('@'),
                'expression' => 'Yii::app()->user->esCoordinador',
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

	/**
	* Función para enviar mensajes candidato-agente e emails para invitar a candidatos
	*
	* Se invoca por AJAX desde vistaPublica.js y desde func.js
	*
	* Input: 
	*
	* int $_POST['idCodMens'] CÓDIGO del mensaje (1=inscripcion, 2=mensaje, 3=mensaje de agente)
	*
	* int $_POST['idPublicacion'] ID de la publicación
	*
	* int $_POST['idRemitente'] ID de quien envía el mensaje (agente o candidato)
	*
	* int $_POST['idRecipiente'] => si  no lo recibe, lo iguala a 0 y será el agente que esté logado
	*
	* int $_POST['sendEmail'] => si no lo recibe, lo iguala a 0 y hará que se le envíe un mensaje al agente avisándole de que tiene un nuevo mensaje - si viene desde vistaPublica, vendrá con valor 1, por lo que no enviará el email (lo enviará desde vistaPublica una vez que vuelva con confirmado=1 o con confirmado=2)
	*
	* int $_POST['invitacion'] => si lo recibe, envía email de invitación a candidato
	*
	* string $_POST['descripcion'] CONTENIDO DEL MENSAJE
	
	* @return int $id ID del mensaje guardado, 
	* guarda mensaje en tbl_mensajes y si procede, envía los emails descritos

	*/
	
    public function actionNuevoAjax() {
	$idCodMens = $_POST['idCodMens']; 
	$idPublicacion=$_POST['idPublicacion'];   
	$idRemitente=$_POST['idRemitente'];
	if(isset ($_POST['idRecipiente'])){ 
	    $idRecipiente=$_POST['idRecipiente'];
	}
	else {
	    $idRecipiente=0;
	}
	if(isset ($_POST['sendEmail'])){ 
	    $sendEmail=$_POST['sendEmail'];
	}
	else {
	    $sendEmail=0;
	}
	if(isset ($_POST['descripcion']))
	$descripcion=$_POST['descripcion'];

	    $id = intval($idPublicacion);
	    $publicacion = new Publicacion($id);

	if($idRecipiente==0 && $sendEmail==0){
	    global $db;
	    $idRecipiente=$publicacion->idAgente;
	    $agente = Usuario::model()->findByPk($idRecipiente);
	    $remitente = Remitente::model()->findByPk($idRemitente);
	    EMail::mensajeAAgente($agente->email, $publicacion->getTitulo(), $remitente->nombre);
	}
	
	//Comprobamos si se trata de una invitacion
	if(isset ($_POST['invitacion'])){ 
	    $remitente = Remitente::model()->findByPk($idRecipiente);
	    EMail::invitaAPublicacion($remitente->email, $publicacion);
		$tituloPublicacion=$publicacion->getTitulo();
		$descripcion=Yii::t('profind', 'Le invito a inscribirse en nuestro proceso de selección de ').strtoupper($tituloPublicacion);	
	}
     
	 if($idRecipiente==0){
	    $idRecipiente=$publicacion->idAgente;
	}
   
   		//guardamos los datos del mensaje en la bbdd
		$mensaje = new Mensaje;
        $mensaje->id_cod_mens = $idCodMens;
        $mensaje->id_publicacion = $idPublicacion;
        $mensaje->id_remitente = $idRemitente;        
        $mensaje->id_recipiente = $idRecipiente;
        $mensaje->fecha = date( "Y-m-d H:i:s" );
        $mensaje->descripcion = $descripcion;
		        
        $mensaje->save();
		// retornamos el id del mensaje
        echo $mensaje->id;
		
		/*Esto era para guardar el mensaje automático que se enviaba al candidato al inscribirse como enviado también al agente
		//De momento se quita
		$mensajeAg = new Mensaje;
        $mensajeAg->id_cod_mens = 3;
        $mensajeAg->id_publicacion = $idPublicacion;
        $mensajeAg->id_remitente = $idRecipiente;        
        $mensajeAg->id_recipiente = $idRemitente;
        $mensajeAg->fecha = date( "Y-m-d H:i:s" );
		
		$agente = Usuario::model()->findByPk($idRecipiente);
		$remitente = Remitente::model()->findByPk($idRemitente);
		$publicacion = new Publicacion($id);
		
		$nombreAgente=$agente->nombre;
		$nombreCandidato=$remitente->nombre;
		$tituloPublicacion=$publicacion->getTitulo();
		$email=$remitente->email;
		if ($idCodMens==1)
		$mensajeenviado="Hola ".$nombreCandidato.", gracias por inscribirse en mi publicación ".strtoupper($tituloPublicacion).". Paso a estudiar su perfil y sus intereses.\n"."Puede enviarme sus mensajes a través de <a href=\"http://www.profindtool.com/profind/index.php?r=publicaciones/vistaPublica&id=$idPublicacion\">este enlace.</a> . Los míos los recibirá en ".$email.". \n Un saludo cordial:\n $nombreAgente";
		if ($idCodMens==2)
		$mensajeenviado="Hola ".$nombreCandidato.", gracias por comunicarse conmigo en relación con mi publicación ".strtoupper($tituloPublicacion).".\n"."Paso a leer su mensaje. Puede enviarme sus mensajes a través de <a href=\"http://www.profindtool.com/profind/index.php?r=publicaciones/vistaPublica&id=$idPublicacion\">este enlace.</a>. Los míos los recibirá en ".$email.". \n Un saludo cordial:\n $nombreAgente";
        $mensajeAg->descripcion = $mensajeenviado;
        
        $mensajeAg->save();
		*/
	exit;
    }
	
	/**
	* Función para el envío de emails a candidatos
	*
	* Se invoca por AJAX desde mensajeAgente()de func.js 
	*
	* Input: 
	*
	* int $_POST['idRecipiente'] ID del candidato
	*
	* int $_POST['idPublicacion'] ID de la publicación a la que se refiere
	*
	* string $_POST['descripcion'] CONTENIDO del email	
		
	* @return email al candidato
	*/
    public function actionEnviaEmailAjax(){	

	    $idRecipiente=$_POST['idRecipiente'];
	    $idPublicacion=$_POST['idPublicacion'];   
	    $descripcion=$_POST['descripcion'];	    
		$id = intval($idPublicacion);
	    $publicacion = new Publicacion($id);
	    global $db;       
            
            $remitente = new Remitente();
            $remitente = Remitente::model()->findByPk($idRecipiente);
            EMail::mensajeACandidato($remitente->email, $descripcion, $publicacion);
           
            exit;
	}
	
	/**
	* Función para listar los mensajes con un candidato
	*
	* Se invoca por AJAX desde verMensajes() de func.js
	
	* @param int $idPublicacion ID de la publicación
	* @param int $idRemitente ID del candidato 
	* @return listado de mensajes
	*/
    public function actionListadoAjax($idPublicacion, $idRemitente) {
        

        $lista = new ListaMensajes($idPublicacion, $idRemitente);
        $mensajes = $lista->mensajes;
        
	$id = intval($idPublicacion);
        $publicacion = new Publicacion($id);
        
        $candidato = Remitente::model()->findByPk($idRemitente);	
        $agente = Usuario::model()->findByPk(Yii::app()->user->id);	
        
		echo '<h1 style="font-size:14px; margin-left:100px">Publicacion '.$idPublicacion." - ".strtoupper ($publicacion->getTitulo())."</h1>";
		$inscripcion = Inscripcion::model()->findByAttributes(array(
            'id_candidato' => $idRemitente,
            'id_publicacion' => $idPublicacion,
        ));
		echo '<input class="button-secun" type="button" title="'.Yii::t('profind', 'Enviar mensajes').'"  name="action" id="boton_mensaje" style="margin-bottom:10px" value="'.Yii::t('profind', 'enviar mensajes').'" onclick="verForm('.$inscripcion->id_publicacion.','.$inscripcion->id_candidato.');"/>';	
        echo '<table class="cajaIndividuo-table" style="width:800px;" cellpadding=10px cellspacing=5px style="text-align:center;width:80%;margin:0 auto;">';
        /*
		echo '<tr><td>Agente: '.$agente->getNombreCompleto();
        echo '</td><td>Candidato: '.$candidato->nombre;
		 echo 'Foto';
		$picture=$candidato->foto;
		if ($picture)
			echo '<img src="'.$candidato->foto.'" alt="Foto del Candidato" width="80"/>';   
		else
		 	echo '<img src="images/candidate_photo.jpg" alt="Foto del Candidato" width="80"/>';	
        echo '</td><td>Fecha';
        echo '</td></tr>';
        echo '<tr><td>'.$agente->email;
        echo '</td><td>'.$candidato->email;
        echo '</td></tr>';
		*/
        foreach ($mensajes as $modelo) : 	
            if($modelo->id_remitente==$agente->id) {
                echo '<tr  style="background-color:rgb(255, 255, 255);"><td style="width: 80px; height:50px; vertical-align:middle;">';
				
				 $claseFoto = $agente->fotografia->tieneFotografia() ? 'fileupload-exists' : 'fileupload-new';
            echo '<div class="fileupload '.$claseFoto.'" data-provides="fileupload" >
                            <div class="fileupload-preview thumbnail" style="width: 30%;">
                                '. CHtml::image($agente->fotografia->getThumbnail()).'
                            </div>';

            echo '</div>';
				
				
				echo '</td><td >'.$agente->nombre.'</td><td >'.$modelo->descripcion.'</td>';
                
                echo '<td>';
                echo referenciaFecha(strtotime($modelo->fecha));
                echo '</td></tr>';
				 echo '<tr height="1px"><td style="width: 50px;">&nbsp</td><td>&nbsp</td><td>&nbsp</td><td>&nbsp</td><td>&nbsp</td></tr>';
				
            }
            else {
                echo '<tr  style="background-color:rgb(255, 180, 80);"><td style="width: 80px;height:50px; vertical-align:middle">';
				$picture =$candidato->foto;
				if ($picture){
					$strfoto = '<img src="'. $candidato->foto.'">';
				 	$claseFoto ='fileupload-new';
				}
				else{
		 			$strfoto = '<img src="images/user_photo.jpg">';
				 	$claseFoto = 'fileupload-exists';
				}
			          
            	echo 	'<div class="fileupload '.$claseFoto.'" data-provides="fileupload" style="height:30px">
                            <div class="fileupload-preview thumbnail" style="width: 30%;">
                                '.$strfoto.'
                            </div>
                  		</div>';
				
				echo '</td><td >'.$candidato->nombre.'</td><td>';
                echo $modelo->descripcion;
                echo '</td><td>';
                echo referenciaFecha(strtotime($modelo->fecha));
                echo '</td></tr>';
				
							
				 echo '<tr height="1px"><td style="width: 50px;">&nbsp</td><td>&nbsp</td><td>&nbsp</td></tr>';
            }
        endforeach;

        echo '</table>';
		
        exit;
    }

	/**
	* Función que rellena el contenido del popup de envío de mensajes a candidatos    
	*  
	* Se invoca por AJAX desde verMensajes() de func.js
	*
	* Input: 
	*
	* int $_POST['idPublicacion']ID de la publicación
	*
	* int $_POST['idCandidato']ID del candidato 
	
	* @return lista de mensajes
	*/
	public function actionFormAgenteAjax() {
		
        $idPublicacion = $_POST['idPublicacion'];
		$idCandidato = $_POST['idCandidato'];
 
 		$id = intval($idPublicacion);
        $publicacion = new Publicacion($id);
		echo '<form action="" method="post" id="registro" name="registro" >';
        echo '<fieldset>';
        echo '<legend>Datos del mensaje</legend>';
		echo '<label for="descripcion" style="margin:5px; color:rgb(204, 102, 0); font-size:18px; margin-top:30px">Mensaje al candidato:</label>';
        echo '<textarea name="descripcion" id="descripcion" rows="4" cols="50" ></textarea>';
		echo '</fieldset>';
		echo '<input style="" class="envio" type="button" title="Enviar mensajes"  name="action" id="boton_mensaje2" value="enviar mensajes" onclick="mensajeAgente('.$idPublicacion.','.$idCandidato.','.$publicacion->idAgente.');"/>';
		echo '</form>';
		echo '<h2 id="enviando" style="display:none"> Enviando...</h2>';

        exit;
    }
	
}
