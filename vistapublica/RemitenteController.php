<?php
	/**
	 * Controlador relativo a candidatos, subida de CV y estado 
	 * @class RemitenteController
	 * @brief Controlador relativo a candidatos, subida de CV y estado 
	 * @package application.controllers
	 */
class RemitenteController extends Controller {
	
	/**
 	* La accion que se ejecuta por defecto.
 	* @var string
 	*/
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
	 *
     * @return array access control rules
     */
    public function accessRules() {
        return array(
            array('allow',
                'actions' => array('subirCvAjax', 'listadoInscripcionesAjax'),
                'users' => array('*'),

            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Funcion para subir el cv del candidato - Admite pdf, doc y docx, con tamaño < 500kb
	 *
     * Se invoca por AJAX desde subirCv() y subirCv2() de vistaPublica.js
	 
     * @return cv Si todo va bien, el cv subido es del formato y tamaño debidos, se guarda en la carpeta cv y se vuelve a vista pública con confirmado=1. Si hay algún error, redirige a vista pública, de donde viene, con confirmado =3 (mal formato), confirmado=4 (mal tamaño), ocnfirmado=5 (no subido archivo)
     que borrarán lo que se haya grabado y harán que haya que iniciar la inscripción de nuevo. 
     */
    public function actionSubirCvAjax($idCandidato, $idPublicacion, $idMensaje) {


		$allowedExts = array("doc", "docx", "pdf");
        $id_candidato=$idCandidato;
		$id_mensaje=$idMensaje;

		$archivo = $_FILES["file"]["tmp_name"];
        $file_nombre=$_FILES["file"]["name"];
		$file_type=$_FILES["file"]["type"];
        $file_size=$_FILES["file"]["size"];
        $explode=explode(".", $file_nombre);
        $extension = end($explode);

		if ( $archivo != "" ){

			if  (($file_type != "application/msword")
			&& ($file_type != "application/vnd.openxmlformats-officedocument.wordprocessingml.document")
			&& ($file_type != "application/pdf"))
			{

			/*
			if  ($file_type != "application/pdf")
			{
			*/
				//Yii::app()->user->setFlash('success', Yii::t('profind', 'Error al subir el cv, por favor, compruebe el formato de archivo'));

				$this->redirect(array('publicaciones/vistaPublica', 'id' => $idPublicacion, 'idRemitente' => $id_candidato,'idMensaje' => $id_mensaje,'confirmado' => 3));
			}
			else if ($file_size > 500000)
			{
				//Yii::app()->user->setFlash('success', Yii::t('profind', 'Error al subir el cv, por favor, compruebe el tamaño de archivo'));
				$this->redirect(array('publicaciones/vistaPublica', 'id' => $idPublicacion, 'idRemitente' => $id_candidato,'idMensaje' => $id_mensaje,'confirmado' => 4));
			}
			else {
					$cv = "cv/" . $id_candidato . '_cv.' . $extension;
					move_uploaded_file($_FILES["file"]["tmp_name"], $cv);
					$candidato = Remitente::model()->findByPk($id_candidato);

					$candidato->cv = $cv;
					$candidato->save();
					Yii::app()->user->setFlash('success', Yii::t('profind', 'Se ha subido el Cv Correctamente'));

					$this->redirect(array('publicaciones/vistaPublica', 'id' => $idPublicacion, 'idRemitente' => $id_candidato,'idMensaje' => $id_mensaje,'confirmado' => 1));
				}
		}
		else{
					$this->redirect(array('publicaciones/vistaPublica', 'id' => $idPublicacion, 'idRemitente' => $id_candidato,'idMensaje' => $id_mensaje,'confirmado' => 5));
		}

    }
	
	/**
     * Función para listar las inscripciones de un candidato
     * 
     * Se utiliza para listar las inscripciones de un candidato al pulsar sobre su nombre en la pantalla de matching de una publicación.
     * La versión actual no la utiliza, pues no está habilitada la pantalla de matching
     * 
     * Se invoca por AJAX desde despliegaInscripciones() de func.js
     * 
	 * Input: 
	 *
	 * int $_POST['idRemitente'] ID del candidato
	 *

     * @return Listado Listado de inscripciones 
     * 
     */
    public function actionListadoInscripcionesAjax() {

	include(dirname(__FILE__).'/../classes/claseListaAreas.php');
	include(dirname(__FILE__).'/../classes/Publicaciones/EnlacePublicacion.php');
	include(dirname(__FILE__).'/../classes/Publicaciones/clasePublicacion.php');
	include(dirname(__FILE__).'/../classes/Publicaciones/claseListaEtiquetas.php');

	global $db;
	$idRemitente=$_POST['idRemitente'];


        $inscripciones = Inscripcion::model()->findAllByAttributes(array(
            'id_candidato' => $idRemitente,
            //'id_publicacion' => $idPublicacion,
        ));
	$arrEstados = array();
	$arrEstados[0] = Yii::t("profind", "Mensaje");
	$arrEstados[1] = Yii::t("profind", "Pendiente");
	$arrEstados[2] = Yii::t("profind", "Aprobado");
	$arrEstados[3] = Yii::t("profind", "Declinado");
	$arrEstados[4] = Yii::t("profind", "Invitado");

	$par = 0;
	$remitente=Remitente::model()->findByPk($idRemitente);
	echo "<h2>".Yii::t("profind", "Inscripciones de")." ".$remitente->nombre."</h2>";
	echo "<br>";
	echo "<table cellpadding='3' width='90%'>";
	echo "<tr style='font-weight:bold;background-color:#b0fa95;'>";
	echo "<td>".Yii::t('profind', 'Publicación')."</td>";
	echo "<td>".Yii::t('profind', 'Fecha')."</td>";
	echo "<td>".Yii::t('profind', 'Estado')."</td>";
	echo "<td></td>";
	echo "</tr>";
	foreach($inscripciones as $inscripcion) {
	    $id = intval($inscripcion->id_publicacion);
	    $publicacion = new Publicacion($id);

	    $strEstiloFondoFila = "";
	    if($par%2==0) {
		$strEstiloFondoFila = "background-color:#EFF2FB;";
	    }
	    if($publicacion->getAgente()->id==Yii::app()->user->id) {
		echo "<tr style='".$strEstiloFondoFila."'>";
		echo "<td><a href='index.php?r=publicaciones/ver&id=".$publicacion->getId()."'>".$publicacion->getTitulo()."</a></td>";
		echo "<td>".referenciaFecha($publicacion->getFechaCreacion())."</td>";
		echo "<td>".$arrEstados[$inscripcion->estado]."</td>";
		echo '<td><input class="button-secun" type="button" title="'.Yii::t('profind', 'Ver mensajes').'"  value="'.Yii::t('profind', 'ver mensajes').'" onclick="verMensajes('.$publicacion->getId().','.$inscripcion->id_candidato.');" /></td>';
		echo "</tr>";
		$par++;
	    }
	}
	echo "</table>";
	exit;
    }

}
