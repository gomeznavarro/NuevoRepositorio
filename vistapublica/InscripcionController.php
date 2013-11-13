<?php
/**
 * Controlador relativo a inscripciones e invitaciones
 * @class InscripcionController
 * @brief Controlador relativo a las inscripciones de candidatos en ofertas, actualización de datos e invitación de candidatos a publicaciones
 * @package application.controllers
 */
class InscripcionController extends Controller {

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
                'actions' => array('nuevoAjax', 'updateAjax'),
                'users' => array('*')
            ),
            array('allow',
                'actions' => array('cambiarEstadoAjax', 'invitarAjax'),
                'users' => array('@'),
                'expression' => 'Yii::app()->user->esCoordinador',
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }
	/**
	 * Controla la inscripcion de candidatos (desde vista publica de la publicación) - Para candidatos nuevos
	 *
 	 * Se invoca por AJAX desde guardaInscripcion() y guardaInscripcionRed() en vistaPublica.js
	 
	 * @param int $idCandidato ID del candidato
	 * @param int $idPublicacion ID de la Publicación
	 * @param int $nivel NIVEL DE COMUNICACION
	 * @param int $fuentes FUENTES de la inscripcion (4 dígitos: digito1=se inscribe con CV, digito2=se inscribe con LinkedIn, 
	 * digito3=se inscribe con Facebook, digito4=se inscribe con Twitter)
	 * @param int $estado ESTADO DEL CANDIDATO (1=pendidente, 2=aprobado, 3=declinado)
	 * @param int $idMensaje ID del mensaje
	 * @param int int $idProcedencia PROCEDENCIA DEL CANDIDATO (directamente a aplicación o desde una de las redes)
	 * @return int $id ID de la inscripción
	 */
    public function actionNuevoAjax($idCandidato, $idPublicacion, $nivel, $fuentes, $estado, $idMensaje, $idProcedencia) {
        global $db;

        $id = intval($idPublicacion);
        $publicacion = new Publicacion($id);

		//si no se especifica procedencia, no procede de las redes sociales
        if($idProcedencia==-1) {
            $idProcedencia = 0;
        }
		
		//Si tras inscribirse, la vista pública no me devuelve confirmado=1 o confirmado=2 la inscripción  se borrará
		//Si me devuelve confirmado=1, será una inscripción; si me devuelve confirmado=2, será sólo un mensaje
		//Si confirmado=1 o confirmado=2, guardamos la inscripción en la bbdd
        $inscripcion = new Inscripcion;
        $inscripcion->id_candidato = $idCandidato;
        $inscripcion->id_publicacion = $idPublicacion;
        $inscripcion->id_procedencia = $idProcedencia;
        $inscripcion->id_agente = $publicacion->idAgente;      
        $inscripcion->id_mensaje = $idMensaje;
        $inscripcion->nivel_comunicacion = $nivel;
        $inscripcion->fuentes = $fuentes;
        $inscripcion->estado = $estado;
        $inscripcion->fecha = date( "Y-m-d H:i:s" );

        $inscripcion->save();
        echo $inscripcion->id;
        exit;
    }
	
	/**
	 * Controla la inscripcion de candidatos (desde vista publica de la publicación) - Para candidatos ya existentes
	 *
 	 * Se invoca por AJAX desde vistaPublica.js
	 * @param int $idCandidato ID del candidato
	 * @param int int $idPublicacion ID de la Publicación
	 * @param int int $nivel NIVEL DE COMUNICACION
	 * @param int int $fuentes FUENTES de la inscripcion (4 dígitos: digito1=se inscribe con CV, digito2=se inscribe con LinkedIn, 
	 * digito3=se inscribe con Facebook, digito4=se inscribe con Twitter)
	 * @param int int $estado ESTADO DEL CANDIDATO (1=pendidente, 2=aprobado, 3=declinado)
	 * @param int int $idMensaje ID del mensaje
	 */
    public function actionUpdateAjax($idCandidato, $idPublicacion, $nivel, $fuentes, $estado, $idMensaje) {
        global $db;

        $id = intval($idPublicacion);
        $publicacion = new Publicacion($id);

		//obtenemos la inscripción del candidato ya existente en la bbdd
        $inscripcion = Inscripcion::model()->findByAttributes(array(
            'id_candidato' => $idCandidato,
            'id_publicacion' => $idPublicacion,
        ));

        $inscripcion->id_candidato = $idCandidato;
        $inscripcion->id_publicacion = $idPublicacion;
        $inscripcion->id_agente = $publicacion->idAgente;
        //$inscripcion->id_mensaje = $idMensaje;
		//no lo guardo aquí sino en vistaPublica con confirmado=0 o 1 (si no, se queda con el que tenga hasta ahora)
        $inscripcion->nivel_comunicacion = $nivel;
        $inscripcion->fuentes = $fuentes;
        $inscripcion->estado = $estado;
        $inscripcion->fecha = date( "Y-m-d H:i:s" );
		//guardamos su nueva inscripción
        $inscripcion->save();
        exit;
    }

	/**
	 * Cambia el estado de un candidato
	 *
 	 * Se invoca por AJAX desde cambiarEstadoInscripcion() en func.js
	 * @param int $idCandidato ID del candidato
	 * @param int $estadoFinal nuevo ESTADO del candidato (1=pendidente, 2=aprobado, 3=declinado)
	 */
    public function actionCambiarEstadoAjax($id, $estadoFinal) {


        $inscripcion = Inscripcion::model()->findByPk($id);

        $inscripcion->estado = $estadoFinal;

        $inscripcion->save();

        exit;
    }

		/**
	 	* Controla la invitacion a un candidato a una publiación
		*
 	 	* Se invoca por AJAX desde invitacion() en func.js
	 	*
	 	* Input: 
	 	*
		* int $_POST['idPublicacion'] ID de la publicación 
	 	*
		* int $_POST['idRemitente'] ID del agente
	 	*
		* int $_POST['idRecipiente'] ID del candidato 
	 	*/
	public function actionInvitarAjax() {
		
		//include(dirname(__FILE__).'/../classes/claseListaAreas.php');
		//include(dirname(__FILE__).'/../classes/Publicaciones/claseDifusionPublicacion.php');
		//include(dirname(__FILE__).'/../classes/Publicaciones/EnlacePublicacion.php');
		//include(dirname(__FILE__).'/../classes/Publicaciones/clasePublicacion.php');
		//include(dirname(__FILE__).'/../classes/Publicaciones/claseListaEtiquetas.php');
		//include(dirname(__FILE__).'/../classes/Publicaciones/claseListaDifusionesPublicacion.php');
		//include(dirname(__FILE__).'/../classes/Publicaciones/claseListaPublicaciones.php');
		
		$idPublicacion=$_POST['idPublicacion'];
		$idRemitente=$_POST['idRemitente'];
		$idRecipiente=$_POST['idRecipiente'];
		
			//	Si aún no se ha determinado la publicación (estamos en Candidatos) saldrá un select para elegirla
			if($idPublicacion==-1){
				
				$publicacion=new Publicacion();
				
				$invitac=new Invitacion();
				$invitacion=$invitac->getPublicacionesInvitacion($idRecipiente);
				$numero=count($invitacion);
				if ($numero==0)
					echo "<h2>".Yii::t('profind', 'No hay publicaciones a las que invitar al candidato')."</h2>";
				else{
					?>
					<h2><?php echo Yii::t('profind', 'Publicación a la que se invita al candidato'); ?></h2>
					<p><?php echo Yii::t('profind', 'Elija tantas publicaciones como desee manteniendo pulsada la tecla Ctrl'); ?></p>
					<form style="margin-top:30px;margin-left:100px" action="">
					<select multiple="multiple" name="idPublicacions[]" id="idPublicacions" >
								<!--<option selected="selected" value='0' ><?php //echo Yii::t('profind', 'Seleccione publicación'); ?>...</option>-->
					<?php
					foreach ($invitacion as $invit)			
						echo '<option value="'.$invit->getId().'">'.$invit->getTitulo().'</option>';
					?>
					</select>
					<br/>
					<input  style="font-size:16px"  class="button-secun" type="button" title="<?php echo Yii::t('profind', 'Invitar'); ?>"  value="<?php echo Yii::t('profind', 'Invitar'); ?>" onclick="enviarInvitacion(<?php echo Yii::app()->user->id ?>,<?php echo $idRecipiente?>)"/>		
						<!--<input  style="font-size:16px"  class="button-secun" type="button" title="<?php //echo Yii::t('profind', 'Invitar'); ?>"  value="Invitar" onclick="invitacion($('#idPublicacions').val() || [], <?php //echo Yii::app()->user->id ?>,<?php //echo $idRecipiente?>)"/>-->
					</form>
				<?php
				}
			}	
			//Si ya se ha determinado la publicación (estamos en Matching), se le pedirá confirmación.		
			else {
				echo '<div style="margin-top:50px; margin-left:100px;">';
				echo '<h2 style="margin-bottom:30px">'.Yii::t('profind', 'Confirmación de invitación').'</h2>';
				echo '<span style="font-size:16px; margin-right:30px;">'.Yii::t('profind', '¿Está seguro?').'</span>'; ?>
			   <input style="font-size:16px"  class="button-secun" type="button" title="<?php echo Yii::t('profind', 'Invitar'); ?>"  value="<?php echo Yii::t('profind', 'Invitar'); ?>" onclick="invitar(<?php echo $idPublicacion?>,<?php echo Yii::app()->user->id ?>,<?php echo $idRecipiente?>);"/> 
			<?php
				echo '</div>';			
			}
				//Yii::app()->user->setFlash('success', Yii::t('profind', 'El candidato ha sido invitado'));
				exit;
			}
	}