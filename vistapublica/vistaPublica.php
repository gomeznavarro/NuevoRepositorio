<?php
	include('protected/widgets/widgetPublicacion.php');
	include('protected/widgets/widgetCajaIndividuo.php');
	include('protected/widgets/widgetCuerpoPublicacion.php');
	Yii::app()->clientScript->registerScriptFile(Yii::app()->params['assetUrl'] . '/js/func.js');
	$nombreObligatorio=Yii::t('profind', 'Por favor, escriba su nombre. Ha de tener al menos dos caracteres');
	$nombreLargo=Yii::t('profind', 'El nombre es demasiado largo.');
	$nombreNumeros=Yii::t('profind', 'El nombre no puede contener números');
	$emailValido=Yii::t('profind', 'Introduzca un email válido');
	$emailLargo=Yii::t('profind', 'El email es demasiado largo');
	$yaInscrito=Yii::t('profind', 'Ya está inscrito con todas las opciones. Solo podrá enviar mensajes');
	$soloTwitter=Yii::t('profind', 'Solo podrá inscribirse con Twitter');
	$soloCv=Yii::t('profind', 'Solo podrá enviar un Cv');
	$soloLinkedIn=Yii::t('profind', 'Solo podrá inscribirse con LinkedIn');
	$soloFacebook=Yii::t('profind', 'Solo podrá inscribirse con Facebook');
	$inscritoAlgunas=Yii::t('profind', 'Ya está inscrito con algunas opciones; no podrá inscribirse de nuevo con ellas');
	$mensajeLargo=Yii::t('profind', 'Se permiten sólo 500 caracteres');
	$sinMensaje=Yii::t('profind', 'No ha escrito nada...');
	$yaCv=Yii::t('profind', 'Ya está inscrito con CV');
	$yaLinkedIn=Yii::t('profind', 'Ya está inscrito con LinkedIn');
	$yaFacebook=Yii::t('profind', 'Ya está inscrito con Facebook');
	$yaTwitter=Yii::t('profind', 'Ya está inscrito con Twitter');
?>
<script type="text/javascript" language="javascript">

var nombreObligatorio ='<?php echo $nombreObligatorio;?>';
var nombreLargo ='<?php echo $nombreLargo;?>';
var nombreNumeros ='<?php echo $nombreNumeros;?>';
var emailValido ='<?php echo $emailValido;?>';
var emailLargo ='<?php echo $emailLargo;?>';
var yaInscrito ='<?php echo $yaInscrito;?>';
var soloTwitter ='<?php echo $soloTwitter;?>';
var soloCv ='<?php echo $soloCv;?>';
var soloLinkedIn ='<?php echo $soloLinkedIn;?>';
var soloFacebook ='<?php echo $soloFacebook;?>';
var inscritoAlgunas ='<?php echo $inscritoAlgunas;?>';
var mensajeLargo ='<?php echo $mensajeLargo;?>';
var sinMensaje ='<?php echo $sinMensaje;?>';
var yaCv ='<?php echo $yaCv;?>';
var yaLinkedIn ='<?php echo $yaLinkedIn;?>';
var yaFacebook ='<?php echo $yaFacebook;?>';
var yaTwitter ='<?php echo $yaTwitter;?>';

</script>

<!--<link rel="stylesheet" href="<?php //echo Yii::app()->params['assetUrl']; ?>/css/vistaPublica.css" />-->

<div id="global_lateralycontenido"> <!-- igualar columnas -->

	<!-- CONTENIDO -->
	<div id="contenido" >
		<?php
		/*
		$confirmado=0-> error
		$confirmado=1-> inscripcion confirmada
		$confirmado=2-> mensaje confirmado
		$confirmado=3-> error de cv no en formato debido
		$confirmado=4-> error de cv no tamaño debido
		$confirmado=3-> error de cv no se ha subido nada
		*/
		$idCandidato=0;
		$idPublicacion=0;

		$idRed=0;
		if(isset($_GET['idRemitente'])){
		    $idCandidato=$_GET['idRemitente'];
		}
		if(isset($_GET['id'])){
		    $idPublicacion=$_GET['id'];
		}
		if(isset($_GET['idRed'])){
		    $idRed = $_GET['idRed'];
		}

		$titulo=$publicacion->getTitulo();

	if($publicacion->getBorrado()==1){
			echo "<div id=\"cajaConfirmacion\">";
			echo "<h2>".Yii::t('profind', 'La publicación ').strtoupper($titulo).Yii::t('profind', ' está cerrada.')."</h2>";
			echo "<p style=\"font-size:16px; margin-top:30px\">Para ver otras publicaciones en Profind, pulse el siguiente <a href=\"index.php?r=publicaciones/directorioPublicaciones \" style=\"cursor: pointer;\">enlace</a>.</p>";
			echo "</div>";
	}
	else{
		if($confirmado==3 ||$confirmado==4 ||$confirmado==5 ){
		
			$mensaje = Mensaje::model()->findByPk($idMensaje);
			if($mensaje)
			$mensaje->delete();
			
			$inscripcion = Inscripcion::model()->findByAttributes(array(
				'id_candidato' => $idCandidato,
				'id_publicacion' => $idPublicacion,
			));
			$mensaje1=Mensaje::model()->findByAttributes(array(
				'id_remitente' => $idCandidato,
				'id_cod_mens' => 1,
				'id_publicacion' => $idPublicacion,
			));
			$mensaje2=Mensaje::model()->findByAttributes(array(
				'id_remitente' => $idCandidato,
				'id_cod_mens' => 2,
				'id_publicacion' => $idPublicacion,
			));

			if(!$mensaje1 && !$mensaje2){			
				if($inscripcion)
					$inscripcion->delete();
		?>
		<div id="cajaConfirmacion">
		<?php
		if($confirmado==3 ){
		echo "<h3>".Yii::t('profind', 'Error')."</h3>";
		//echo "<p style=\"color:#fff; background-color:#F00; padding:2px;\">".Yii::t('profind', 'Ha habido un problema con la extensión del archivo enviado').".</p><p style=\"color:#fff; background-color:#F00; padding:2px;\">".Yii::t('profind', 'Por favor adjunte un archivo con extensión <strong>pdf, doc o docx</strong>')."</p>";	
		echo "<p style=\"color:#000; font-weight: bold; font-size:14px; padding:2px;\">".Yii::t('profind', 'Lo lamentamos, ha habido un problema con la extensión del archivo enviado y se ha suspendido el proceso.')."</p><p style=\"color:#000; font-weight:bold; font-size:14px; padding:2px;\">".Yii::t('profind', 'Por favor vuelva a la publicación, rellene sus datos y adjunte un archivo con extensión <span style="color: #900">pdf, doc o docx</span>')."</p>";	
		}
		if($confirmado==4 ){
		echo "<h3>".Yii::t('profind', 'Error')."</h3>";
		//echo "<p style=\"color:#fff; background-color:#F00; padding:2px;\">".Yii::t('profind', 'Ha habido un problema con el tamaño del archivo enviado').".</p><p style=\"color:#fff; background-color:#F00; padding:2px;\">".Yii::t('profind', 'Por favor adjunte un archivo de menos de <strong>500kb</strong>').".</p> ";
		echo "<p style=\"color:#000; font-weight: bold; font-size:14px; padding:2px;\">".Yii::t('profind', 'Lo lamentamos, ha habido un problema con el tamaño del archivo enviado y se ha suspendido el proceso.')."</p><p style=\"color:#000; font-weight:bold; font-size:14px; padding:2px;\">".Yii::t('profind', 'Por favor vuelva a la publicación, rellene sus datos y adjunte un archivo de menos de <span style="color: #900">500kb</span>').".</p> ";
		}
		if($confirmado==5 ){
		echo "<h3>".Yii::t('profind', 'Error')."</h3>";
		//echo "<p style=\"color:#fff; background-color:#F00; padding:2px;\">".Yii::t('profind', 'No se ha enviado ningún archivo').".</p><p style=\"color:#fff; background-color:#F00; padding:2px;\">".Yii::t('profind', 'Por favor, adjunte un archivo de menos de <strong>500kb</strong> con extensión <strong>pdf, doc o docx</strong>').".</p>";
		echo "<p style=\"color:#000; font-weight:bold; font-size:14px; padding:2px;\">".Yii::t('profind', 'Lo lamentamos, no se ha enviado ningún archivo y se ha suspendido el proceso.')."</p><p style=\"color:#000; font-weight:bold; font-size:14px; padding:2px;\">".Yii::t('profind', 'Por favor, vuelva a la publicación, rellene sus datos y adjunte un archivo de menos de <span style="color: #900">500kb</span> con extensión <span style="color: #900">pdf, doc o docx</span>').".</p>";
			}			
			
			$inscrip = Inscripcion::model()->findByAttributes(array(
				'id_candidato' => $idCandidato));	
			$mens1=Mensaje::model()->findByAttributes(array(
				'id_remitente' => $idCandidato,
				'id_cod_mens' => 1,
			));
			$mens2=Mensaje::model()->findByAttributes(array(
				'id_remitente' => $idCandidato,
				'id_cod_mens' => 2,
			));
			$mens3=Mensaje::model()->findByAttributes(array(
				'id_recipiente' => $idCandidato,
				'id_cod_mens' => 3,
			));
			if(!$inscrip && !$mens1 && !$mens2 && !$mens3 ){
				$candidato = Remitente::model()->findByPk($idCandidato);
				if($candidato)
					$candidato->delete();
			}
		}
		/*?>
		De momento no se contempla la posibilidad de que vuelvan a subir su archivo si hay un error, 
		sino que han de iniciar todo el proceso. Descomentar lo siguiente si se cambia de opinión
		*/
		/*
		 <form style="margin-top:30px" action="" method="post" id="cv2" name="cv" enctype="multipart/form-data">
		 <input type="hidden" name="id_publicacion" id="id_publicacion" value="<?php echo $idPublicacion?>" />
		 <input type="hidden" name="id_candidato" id="id_candidato" value="<?php echo $idCandidato?>" />
		 <fieldset>
		 <label for="file" style="float:left;margin-top:5px;color:#000000;"><?php echo Yii::t('profind', 'Archivo:')?></label>
		 <input style="width:200px; float:left;" type="file" name="file" id="file"><br>
		  </fieldset>
					<input class="envio" type="button" name="cv" value="Enviar CV" onclick="subirCv2();">
				</form>

		<?php */
		echo "<p style=\"font-weight:bold;float: right; margin-right:15px; text-align:center\">".Yii::t('profind', 'Volver a la publicación').": <a style=\"width:150px;\" class=\"btn btn-primary\" href=index.php?r=publicaciones/vistaPublica&id=".$publicacion->getId().">".strtoupper($titulo)."</a></p>";
	}

			if($confirmado==0 ){
				echo "<h3>".Yii::t('profind', 'Error')."</h3>";
				echo "<p>".Yii::t('profind', 'Ha habido algún problema en la recuperación de sus datos. Inténtelo más tarde por favor').".</p>";
				echo "<p style=\"font-weight:bold;float: right; margin-right:15px; text-align:center\">".Yii::t('profind', 'Volver a la publicación').": <a style=\"width:150px; \" class=\"btn btn-primary\" href=index.php?r=publicaciones/vistaPublica&id=".$publicacion->getId().">".strtoupper($titulo)."</a></p>";
			}


			if($confirmado==-1){

			widgetCuerpoPublicacion($publicacion);

		?>
			<div id="mensajes">
				<h2><?php echo Yii::t('profind', '¡ESTOY INTERESADO!'); ?></h2>
				<!--<p class="articulo" style="margin-top:20px;">Rellene sus datos y pulse el botón de la izquierda si sólo quiere enviarme un mensaje, o el botón de la derecha correspondiente a la forma en que desea inscribirse. Para inscribirse en la oferta puede enviarme su CV o inscribirse mediante sus datos en las redes, seleccionando el botón adecuado. Una vez inscrito mediante el envío de su CV o con una de las redes, podrá volver a la publicación e inscribirse con aquellas redes con las que aún no se haya inscrito, o enviarme su CV si no lo ha hecho antes.</p>-->
<noscript style="float:left">
        <p style="color:#900; margin-bottom:10px;font-size:1.2em; margin-left:30px"><?php echo Yii::t('profind', 'Esta p&aacute;gina requiere para su funcionamiento el uso de JavaScript. Si lo has deshabilitado intencionadamente, por favor vuelve a activarlo para disfrutar de todas las funcionalidades.')?></p>
    </noscript>
				<div id="formulario" >
					<form action="" method="post" id="registro" name="registro" >
						<fieldset style="width:100%;">
							<legend><?php echo Yii::t('profind', 'Datos del mensaje'); ?></legend>

							<div class="izdo" style="width:40%">
								<label for="nombre" style="margin:5px;color:#000;"><?php echo Yii::t('profind', 'Mi nombre'); ?>:</label>

								<input class="texto" style="color: rgb(102, 102, 102);background-color: rgb(236, 255, 210);width:90%;" type="text" maxlength="100" name="nombre" id="nombre" tabindex="1" value=""<?php echo (!Yii::app()->user->isGuest) ? ' disabled="disabled"' : ''; ?> />
								<div id="nombreInfo" class="info"></div>
							</div>
							<div class="dcho" style="width:40%">
								<label for="email" style="margin:5px;color:#000;"><?php echo Yii::t('profind', 'Mi email'); ?>:</label>

								<input class="texto" style="color: rgb(102, 102, 102);background-color: rgb(236, 255, 210);width:90%;" type="text" maxlength="100" name="email" id="email" tabindex="2" value=""<?php echo (!Yii::app()->user->isGuest) ? ' disabled="disabled"' : ''; ?> />
								<div id="emailInfo" class="info"></div>
							</div>
							<div class="clear"></div>
							<div class="espacio_superior" >
								<label for="descripcion" style="margin:5px;color:#000000;"><?php echo Yii::t('profind', 'Mi mensaje al Recruiter'); ?>:</label>
								<div id="descripcionInfo" class="info"></div>
								<textarea maxlength="501" name="descripcion" id="descripcion" style="background-color: rgb(236, 255, 210); " rows="4" cols="50" tabindex="3"<?php echo (!Yii::app()->user->isGuest) ? ' disabled="disabled"' : ''; ?>></textarea>
								<div class="clear"></div>
							</div>
							<span class="ayuda_form" style="color:#000"><?php echo Yii::t('profind', 'Máx. 500 caracteres'); ?></span>
						</fieldset>
						<!--distintas formas de inscribibrse según el boton pulsado y segun su 'name'-->
						<div style="clear: both; ">

							<div class="izdo">
							<!--<p style="color:#000;">Envío de mensaje:</p>-->
							<input class="envio" style="background-color: rgb(204, 102, 0); color:#000;font-family: 'Arial Narrow', Helvetica, sans-serif;font-stretch: condensed;margin-bottom:20px;" type="button" title="<?php echo Yii::t('profind', 'Solo enviar mensaje'); ?>" name="action" id="boton_mensaje" value="<?php echo Yii::t('profind', 'Solo enviar mensaje'); ?>" onclick="soloMensaje();"<?php echo (!Yii::app()->user->isGuest) ? ' disabled="disabled"' : ''; ?> />

							</div>
							<div class="dcho">

							<!--<p style="color:#000;">Inscripción:</p>-->
							<div class="envio">

								<span style="margin-bottom:20px"><?php echo Yii::t('profind', 'Inscribirse como candidato'); ?><br/></span>
								<p style="color:#000;text-transform:none"><?php echo Yii::t('profind', 'Puede elegir entre'); ?>:</p>
								<p style="color:#000;text-transform:none;  margin-left:20px;"><?php echo Yii::t('profind', 'Enviar CV'); ?>:</p>
								<input style="width:40px; margin-left:20px;" class="envio1" type="button" title="<?php echo Yii::t('profind', 'Inscripción con CV'); ?>"  name="action1" id="boton_inscripcion1" value="" onclick="inscripcionConCV();"<?php echo (!Yii::app()->user->isGuest) ? ' disabled="disabled"' : ''; ?> />
								<p style="color:#000;text-transform:none"><?php echo Yii::t('profind', 'o'); ?></p>
								<p style="color:#000;text-transform:none;  margin-left:20px;"><?php echo Yii::t('profind', 'Inscribirse mediante una de sus redes'); ?>:</p>
								<p style="margin-left:20px">
								<?php

									foreach ($redesSociales as $redSocial)
									{
											// Obtenemos los datos de la red social
											$datosRed = $redSocial->obtenerDatosRed();

											echo '<input style="width:40px;margin-top:5px;" class="envio' . $datosRed['id'] . '" type="button" title="' . Yii::t('profind', 'Inscripción con') . ' ' . $datosRed['nombre'] . '"  name="action' . $datosRed['id'] . '" id="boton_inscripcion' . $datosRed['id'] . '" value="" onclick="inscripcionConRedSocial(' . $datosRed['id'] . ');"' . ((!Yii::app()->user->isGuest) ? ' disabled="disabled"' : '') . ' />';
									}
									   ?>
								</p>
									<div class="clear"></div>
									<!--<p style="color:#000000;text-transform:none">Una vez inscrito con tu CV o con una de las redes, podrás volver a la publicación e inscribirte con las opciones que no hayas utilizado.</p>-->
								</div>
							</div>
						</div>
					</form>
				</div>
				<div class="clear"></div>
				
				<div id="infoCV" style="color:#333; font-size:14px;display:none;">
					<p class="articulo" style="color:#900; margin-bottom:30px">
					<?php echo Yii::t('profind', 'Por favor, adjunte un archivo de menos de <strong>500kb</strong> con extensión <strong>pdf, doc o docx</strong>'); ?>
					</p>
				</div>
				
				<form action="" method="post" id="cv" name="cv" enctype="multipart/form-data" style="display:none;">
					<input type="hidden" name="id_publicacion" id="id_publicacion" value="" />
					<input type="hidden" name="id_candidato" id="id_candidato" value="" />
					<input type="hidden" name="id_mensaje" id="id_mensaje" value="" />
					<fieldset>
						<label for="file" style="float:left;margin-top:5px;color:#000000;"><?php echo Yii::t('profind', 'Archivo'); ?>:</label>
						<input style="width:200px; float:left;" type="file" name="file" id="file"><br>
					</fieldset>
					<input class="envio" type="button" name="cv" value="<?php echo Yii::t('profind', 'Enviar CV'); ?>" onclick="subirCv();">
				</form>
			</div>
		   <div class="clear"></div>
		   <p class="articulo" style="margin-top:20px; font-family:'Trebuchet MS', Arial, Helvetica, sans-serif; font-size:.8em"><?php echo Yii::t('profind', 'Nota'); ?>: <br/>
		   <?php echo Yii::t('profind', 'Una vez inscrito mediante el envío de su CV o con una de las redes, podrá volver a la publicación si lo desea e inscribirse con aquellas redes con las que aún no se haya inscrito, o enviarme su CV si no lo ha hecho antes'); ?>.</p>
		</div>

		<?php }
		else if ($confirmado==1 || $confirmado==2 ) {?>
			<div id="cajaConfirmacion">
			<?php
			if(isset($_GET['idRemitente'])){
			    $idRemitente=$_GET['idRemitente'];
			    $remitente = Remitente::model()->findByPk($idRemitente);
			    $inscripcion = Inscripcion::model()->findByAttributes(array(
					    'id_candidato' => $idRemitente,
					    'id_publicacion' => $publicacion->getId(),

				    ));
			    if($confirmado==1){
					$inscripcion->nivel_comunicacion=1;
					$inscripcion->id_mensaje = $idMensaje;
					$inscripcion->save();
			
				global $db;
				$idRecipiente=$publicacion->idAgente;
				$agente = Usuario::model()->findByPk($idRecipiente);
				$idRemitente=$remitente->id;
				$remitente = Remitente::model()->findByPk($idRemitente);
				EMail::mensajeAAgente($agente->email, $publicacion->getTitulo(), $remitente->nombre);
									
				//$idMensaje=$inscripcion->id_mensaje;
			    }
			    if($confirmado==2){
				global $db;
				$idRecipiente=$publicacion->idAgente;
				$agente = Usuario::model()->findByPk($idRecipiente);
				$idRemitente=$remitente->id;
				$remitente = Remitente::model()->findByPk($idRemitente);
				EMail::mensajeAAgente($agente->email, $publicacion->getTitulo(), $remitente->nombre);
				
				$idMensaje=$_GET['idMensaje'];
				$inscripcion->id_mensaje = $idMensaje;
				$inscripcion->save();
			    }
			    $mensaje = Mensaje::model()->findByPk($idMensaje);
			    $titulo=$publicacion->getTitulo();
			    if($confirmado==2)
				    echo "<h2>".Yii::t('profind', 'Gracias por contactar con nosotros respecto a la publicación')." <br/><a style=\"text-transform: uppercase\" href=index.php?r=publicaciones/vistaPublica&id=".$publicacion->getId().">".$titulo."</a></h2> ";

			    if($confirmado==1){				
				echo "<h2>".Yii::t('profind', 'Gracias por inscribirse en la publicacion')." <br/><a style=\"text-transform: uppercase\" href=index.php?r=publicaciones/vistaPublica&id=".$publicacion->getId().">".$titulo."</a></h2>";

				$inscripcion = Inscripcion::model()->findByAttributes(array(
				'id_candidato' => $idRemitente,
				'id_publicacion' => $idPublicacion,
				));

				$fuentes= $inscripcion->fuentes;

					if($idRed==2 || $idRed==3 || $idRed==4){

						switch($idRed){

							case 2: { //si viene correctamente de linkedin

								$fuentes[1]='1';
								break;
							}
							case 3: {//si viene correctamente de facebook

								$fuentes[2]='1';
								break;
							}
							case 4: {//si viene correctamente de twitter

								$fuentes[3]='1';
								break;
							}
							default:{
								break;
							}
						}

					}
					else{
						 $fuentes[0]='1';
					}
				$inscripcion->fuentes=$fuentes;
				$inscripcion->save();
			    }
			}
			echo "<br/><br/>";
			?>

			<p><?php echo Yii::t('profind', 'A continuación le mostramos la información que hemos recibido.'); ?></p>
			<dl>
			  <dt><?php echo Yii::t('profind', 'Su nombre:'); ?></dt>
			  <dd ><?php echo $nombre=$remitente->nombre ?></dd>
			  <dt><?php echo Yii::t('profind', 'Su email:'); ?></dt>
			  <dd><?php echo $email=$remitente->email ?></dd>
			  <dt><?php echo Yii::t('profind', 'Su mensaje al Recruiter:'); ?></dt>
			  <dd><?php echo $mensajes=$mensaje->descripcion ?></dd>
			 </dl>
		<?php
			echo "<p style=\"text-transform: uppercase; font-weight:bold;float: right; margin-right:15px; text-align:center\">".Yii::t('profind', 'Volver a la publicación').": <a style=\"width:150px; \" class=\"btn btn-primary\" href=index.php?r=publicaciones/vistaPublica&id=".$publicacion->getId().">".$titulo."</a></p>";
			
		

 ?>
 </div> <!--fin caja gracias inscripción y mensajes-->

			<div class="clear"></div>

	  <?php
	  }
	  else {
		//echo Yii::t('profind', 'Ha habido algún problema para recuperar sus datos. Vuelva a intentarlo más tarde, por favor');
		?>
	
		</div>
	   <?php }

	} //final del else - publicación no borrada
	?>
	</div><!-- //fin contenido-->
	<!-- LATERAL -->
	<div id="lateral">

<?php

	    widgetCajaIndividuo($agente, 0, 0, -1, "");
	?>
		<div class="clear"></div>
	<?php
	    if($publicacionesRecientes)
	    {
        ?>
		    <div class="izdo">
			    <span class="nivela"><?php echo Yii::t('profind', 'OTRAS PUBLICACIONES DE ESTE RECRUITER'); ?></span>
			    <div style="border:0px solid #ccc;max-height:200px;	overflow-x:hidden;
	overflow-y:scroll; text-align:left">
				    <div class="agentes" style="margin:0px;width:100%; text-align:left">
					    <?php
					    $numPublicaciones = 0;
					    foreach ($publicacionesRecientes as $publicacionTmp):
					    if ($publicacion->getId()==$publicacionTmp->getId()) continue;
						    $numPublicaciones++;
						    widgetPublicacion($publicacionTmp, 0);
					    endforeach;
		    ?>
				    </div>
			    </div>
		    </div>
		    <div class="clear"></div>
		<?php }
	if($publicacion->getBorrado()!=1){
		?>
		<div class="envio_amigo">
			<?php $url = $publicacion->getEnlacePublico(); ?>
			<span class="nivela"><?php echo Yii::t('profind', 'ENVIAR LA PUBLICACION A UN AMIGO'); ?></span>
			<!-- al pinchar el enlace abre outlook o gestor de correo predeterminado usuario para enviar correo externo con link a la publicacion-->
			<a lang="es" charset="UTF-8" <?php echo (Yii::app()->user->isGuest) ? (' href="mailto:?subject=He visto un empleo que te puede interesar&body=Este trabajo parece encajar con tu perfil... ' . $url . '"') : ''; ?>><img src="images/mens_amigo.jpg" title="Enviar a amigo" alt="Foto Env&iacute;o"/></a>
		</div>

		<div class="compartir">
			<span class="nivela"><?php echo Yii::t('profind', 'COMPARTIR ESTA PUBLICACIÓN EN '); ?><br/><?php echo Yii::t('profind', 'MIS REDES:'); ?></span>
			<div class="enlaces_compartir">
				<?php
				foreach ($redesSociales as $redSocial)
				{
					echo '<div class="boton-red">';
					$enlace = new EnlacePublicacion('', 'index.php?r=publicaciones/vistaPublica&id=' . $publicacion->getId() . "&redProcedencia=" . $redSocial->getIdRed(), $redSocial->getIdRed());
					$botonCompartir = $redSocial->obtenerBotonCompartir($enlace->getURLCodificada(), $publicacion->getTitulo());
					if ($botonCompartir != false)
					{
						echo $botonCompartir;
					}
					echo '</div>';
				}
				?>
				<div class="clear"></div>
			</div>
		</div>
	<?php }?>
	</div><!-- //LATERAL -->
	<div class="clear"></div>
</div><!-- //igualar columnas -->
<?php
if($confirmado!=1 || $confirmado!=2){?>
</div><?php }
?>
<script type="text/javascript" language="javascript">
var idPublicacion = <?php echo $publicacion->getId();?>;
var idProcedencia = <?php echo $idProc;?>;
</script>

<script type="text/javascript" src="<?php echo Yii::app()->params['assetUrl']; ?>/js/vistaPublica.js"></script>


<?php

$detect = new MobileDetect();
if ($detect->isMobile() || $detect->isTablet()) {?>
<style type="text/css">
  /*body {
	width:99%;
	font-size:.2em;
 }
body {font-size:.2em;}*/
.cuerpoPagina{width:99%;}
#contenido {border-right:0;}
#lateral {float:left;}
.titulo {font-size:1.2em;}
.logoEmpresaVP {width:50px;}

/*

 	.clearfix {width:30%;}
 	.espacio_superior {margin-top: 3px;}
 	.cab_izda {width: 10%;}
	.cab_dcha {width: 63%;text-align: center;margin-right: 2px;}*/
</style>


<?php }
?>

