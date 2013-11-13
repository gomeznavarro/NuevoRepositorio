<?php
/**
 * Controlador relativo a publicaciones
 * @class PublicacionesController
 * @brief Controlador relativo a publicaciones 
 * @package application.controllers
 */
class PublicacionesController extends Controller {
	
	/**
 	* La accion que se ejecuta por defecto.
 	* @var string
 	*/
	public $defaultAction = 'listado';

	private $configuracion;

	private $producto;
	
	private $usuario;

	public function __construct($id, CWebModule $module=NULL)
	{
		parent::__construct($id, $module);

		// Incluimos los parámetros de configuración
		$this->configuracion = Yii::app()->params['publicaciones'];

		// Obtenemos los datos del usuario
		if (Yii::app()->user->getIsGuest() == false)
		{
			$this->usuario = Usuario::model()->findByPk(Yii::app()->user->id);
		}
	}
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}


	public function accessRules()
	{
		return array(
			array('allow',
				'actions' => array(
					'vistaPublica',
					'entradaDirectorioPublicaciones',
					'directorioPublicaciones',
					'directorioAgentes',
					'obtenerRemitenteAjax',
					'encontrarFuentesAjax',
					'obtenerSoloRemitenteAjax',
					'cargaLocalidadesAjax'
				),
				'users' => array('*')
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions' => array(
					'listado',
					'listadoAjax',
					'nueva',
					'guardarAjax',
					'ver',
					'editar',
					'interesados',
					'matching',
					'matchingAjax',
					'abrir',
					'cerrar',
					'cambioEstadoAjax',
					'resumenAjax',
					'listarInteresadosAjax',
					'exportarInteresadosCsv',
					'eliminarCandidato',
					'eliminarPublicacion',
					'directorioCandidatos'
				),
				'users' => array('@'),
			),
			array('deny', // deny all users
				'users' => array('*'),
			),
		);
	}

    /**
     * Funcion publicaciones/listado.
     * Es la página principal de la plataforma, donde llega después de un login correcto.
	 *
     * Si ha caducado la subscripcion comprueba si tiene programado actualizar producto.
     * Si no esta programado le redirecciona a la pantalla de Productos.
     * Si hay mas publicaciones abiertas de las que tiene disponibles en el nuevo producto se cierran las mas antiguas.
     *
     * Carga la vista themes/profind/publicaciones/listado.php
     *
     * @param $estado
	 * @param $pagina
     * @return Listado Listado de publicaciones del agente
     */
	public function actionListado($estado = '', $pagina = 1)
	{
		global $bd;

		//Si ha caducado la subscripcion comprueba si tiene programado actualizar producto
		$subscripcion = Subscripcion::model()->findByPk(Yii::app()->user->subscripcion->id);

		if(calculaDias($subscripcion->fecha_fin, date('Y-m-d')) < 0) {
		    if($subscripcion->id_pedido!=null) {
			$pedido = Pedido::model()->findByPk($subscripcion->id_pedido);
			$productoNuevo = Producto::model()->findByPk($pedido->id_producto);
			$subscripcion->id_pedido = null;
			$subscripcion->save();
			$this->redirect(array('modificar', 'id' => $id, 'actualiza' => $pedido->id_producto, 'meses' => $pedido->meses));
		    }
		    else {
			//Si no esta programado le redirecciona a la pantalla de Productos
			Yii::app()->user->setFlash('error', Yii::t('profind', 'Su Subscripcion ha caducado<br>Contrate un Producto para continuar disfrutando de Profind'));
			$this->redirect(array('subscripcion/modificar', 'id' => $subscripcion->id));
		    }
		}

		$elemPorPagina = $this->configuracion['elemPorPagina'];

		// Opciones de paginación
		if ($pagina < 1)
		{
			$pagina = 1;
		}

		// Configuramos el estado
		if ($estado != Publicacion::ESTADO_CERRADA && $estado != Publicacion::ESTADO_ABIERTA)
		{
			$estado = '';
		}

		// Obtenemos las publicaciones del usuario
		$listaPublicaciones = new ListaPublicaciones($this->usuario['id'], $estado);

		// Si el usuario no tiene publicaciones, le mandamos a crear una
		if ($estado == '' && $listaPublicaciones->contar() == 0)
		{
			header('Location: index.php?r=publicaciones/nueva');
			exit;
		}

		// Obtenemos el número de publicaciones
		$total = $listaPublicaciones->contar();
		$paginas = ceil($total / $elemPorPagina);

		// Generamos la paginación sólo si hay más de una página
		$publicaciones = array();
		$paginacion = "";

		if ($paginas > 1)
		{
			$paginacion = Yii::t('profind', 'Página').": ";
			for ($i = 1; $i <= $paginas; $i++)
			{
				// Generamos la URL de la página (preservando los filtros si es posible)
				$urlPagina = 'index.php?r=publicaciones/listado&pagina=' . $i;
				if (!empty($estado))
				{
					$urlPagina .= '&estado=' . $estado;
				}

				// Generamos el número de página
				$paginacion .= (($pagina == $i) ? '<b>' : '') . '<a style="'.(($pagina == $i) ? 'color:#900; font-size:1.2em;margin-right:2px' : 'margin-right:2px').'" href="' . $urlPagina . '">' . $i . '</a>' . (($pagina == $i) ? '</b>' : '') . ' ';
			}

			$inicio = ($pagina-1) * $elemPorPagina;

			// Obtenemos las publicaciones a mostrar
			$publicaciones = $listaPublicaciones->getSubLista($inicio, $elemPorPagina);
		}
		else
		{
			// No hay paginación, cogemos todas...
			$publicaciones = $listaPublicaciones->getTodas();
		}

		// Calculamos el número de publicaciones abiertas
		$listaPublicaciones = new ListaPublicaciones(Yii::app()->user->id, '');
		$publicacionesActivas = 0;
		if($listaPublicaciones->contarAbiertas()) {
		    $publicacionesActivas = $listaPublicaciones->contarAbiertas();
		}

		//Si hay mas publicaciones abiertas de las que tiene disponibles en el nuevo producto se cierran las mas antiguas
		if($publicacionesActivas>Yii::app()->user->subscripcion->producto->max_publicaciones && Yii::app()->user->subscripcion->producto->max_publicaciones!=-1) {
		    $lista = new ListaPublicaciones(Yii::app()->user->id, 'A');

		    $numPublicacionesParaCerrar = $publicacionesActivas-Yii::app()->user->subscripcion->producto->max_publicaciones;

		    $publicacionesTmp=$lista->getTodas();
		    $i = 1;
		    foreach($publicacionesTmp as $publicacion) {
			if($i>Yii::app()->user->subscripcion->producto->max_publicaciones) {
			    $consulta = "UPDATE tbl_publicaciones SET estado = 'C' WHERE id = " . $publicacion->getId();
			    $bd->consulta($consulta);
			}
			$i++;
		    }
		}

		// Llamamos a la vista
		$this->render('listado', array(
			'publicaciones' => $publicaciones,
			'formatoFecha' => $this->configuracion['formatoFecha'],
			'estado' => $estado,
			'redesSociales' => $this->getRedesSociales(),
			'publicacionesActivas' => $publicacionesActivas,
			'publicacionesDisponibles' => $listaPublicaciones->getPublicacionesDisponibles(),
			'paginacion' => $paginacion,
			'total' => $total)
		);
	}

    /**
     * Funcion publicaciones/cargaLocalidadesAjax.
     * Genera un select con localidades de un pais.
     *
     * Puede obtener el pais buscando en la tabla por nombre o recibiendo directamente el id.
     * Si no encuentra localidades carga el select vacio.
     *
     * Se invoca por AJAX desde cargaComboSecundario() o cargaTodoComboSecundario() en func.js
     *
     * @param $pais
	 * @param $verMas
     * @return municipios
     */
	public function actionCargaLocalidadesAjax($pais = '', $verMas='')
	{
		global $bd;
		$idPais = 0;
		if(!is_numeric($pais)) {
		    $paises = Pais::model()->findAll();
		    foreach($paises as $paisTmp) {
			if($paisTmp->pais==$pais) {
			    $idPais = $paisTmp->id;
			}
		    }
		}
		else {
		    $idPais = intval($pais);
		}

		$criteria = new CDbCriteria();
		$criteria->condition = 'id_pais='.$idPais.' AND tipo!=0';
		$criteria->order = 'localidad_' . Yii::app()->language;
		$municipios = Municipio::model()->findAll($criteria);
		echo '<option selected="selected"  value="">'.Yii::t('profind', 'Seleccione localidad').'</option>';

		$noMunicipio = true;

		foreach ($municipios as $municipio)
		{
		    $noMunicipio = false;
		    $selected = "";

		    $claseElemento = "";
		    if ($verMas==''){
			if($municipio->tipo==2) {
			    $claseElemento = 'class="opcionVerMasLocalidad"';
			}
		    }
		    echo '<option '.$selected.' '.$claseElemento.' value="'.$municipio->id.'">'.$municipio->municipio.'</option>';
		}
		if($noMunicipio) {
		    echo '<option selected="selected"  value=""></option>';
		}
		exit;
	}

    /**
     * Funcion publicaciones/listadoAjax.
     * Genera el listado de publicaciones para un agente.
     * Igual que la accion principal pero sin ninguna vista
	 *
     * Se invoca por AJAX desde listarPublicaciones() de func.js
     *
     * @param $estado
	 * @param $pagina
     * @return Listado Listado de publicaciones del agente
     */
	public function actionListadoAjax($estado = '', $pagina = 1)
	{
		include('protected/widgets/widgetPublicacion.php');
		global $bd;

		$elemPorPagina = $this->configuracion['elemPorPagina'];

		// Opciones de paginación
		if ($pagina < 1)
		{
			$pagina = 1;
		}

		// Configuramos el estado
		if ($estado != Publicacion::ESTADO_CERRADA && $estado != Publicacion::ESTADO_ABIERTA)
		{
			$estado = '';
		}

		// Obtenemos las publicaciones del usuario
		$listaPublicaciones = new ListaPublicaciones($this->usuario['id'], $estado);

		// Si el usuario no tiene publicaciones, le mandamos a crear una
		if ($estado == '' && $listaPublicaciones->contar() == 0)
		{
			//header('Location: index.php?r=publicaciones/nueva');
			//exit;
		}

		// Obtenemos el número de publicaciones
		$total = $listaPublicaciones->contar();
		$paginas = ceil($total / $elemPorPagina);

		// Generamos la paginación sólo si hay más de una página
		$publicaciones = array();
		$paginacion = "";
		if ($paginas > 1)
		{
			$paginacion = Yii::t('profind', 'Página').": ";
			for ($i = 1; $i <= $paginas; $i++)
			{
				$urlPagina = 'index.php?r=publicaciones/listado&pagina=' . $i;
				if (!empty($estado))
				{
					$urlPagina .= '&estado=' . $estado;
				}
				$paginacion .= (($pagina == $i) ? '<b>' : '') . '<a style="'.(($pagina == $i) ? 'color:#900; font-size:1.2em;margin-right:2px' : 'margin-right:2px').'" href="' . $urlPagina . '">' . $i . '</a>' . (($pagina == $i) ? '</b>' : '') . ' ';
			}

			$inicio = ($pagina-1) * $elemPorPagina;

			// Obtenemos las publicaciones a mostrar
			$publicaciones = $listaPublicaciones->getSubLista($inicio, $elemPorPagina);
		}
		else
		{
			// No hay paginación, cogemos todas...
			$publicaciones = $listaPublicaciones->getTodas();
		}



		if (sizeof($publicaciones) == 0)
		{
				if ($estado == Publicacion::ESTADO_ABIERTA)
				{
						echo '<span>'.Yii::t('profind', 'No hay ninguna publicación activa').'. <a href="' . Yii::app()->getBaseUrl() . '/index.php?r=publicaciones/nueva">'.Yii::t('profind', 'Cree una ahora!').'</a></span>';
				}
				else
				{
						echo '<span>'.Yii::t('profind', 'No hay ninguna publicación cerrada').'.</span>';
				}
		}
		else
		{
				foreach ($publicaciones as $publicacion)
				{
						widgetPublicacion($publicacion, 1, $this->getRedesSociales());
				}
		}
		echo $paginacion;
		exit;
	}

	/**
     * Funcion publicaciones/entradaDirectorioPublicaciones.
     * Muestra un selector de país, para precargar sólo las publicaciones de cierto país en el direcotorio.
     * Es una pagina de acceso publico a la que cualquier usuario puede llegar sin necesidad de login.
     *
     * Carga la vista themes/profind/publicaciones/entradaDirectorioPublicaciones.php
     *
     * @return paises
     */
	public function actionEntradaDirectorioPublicaciones()
	{
		global $bd;

		$lg = Yii::app()->language;

		$sql = "SELECT pa.id, pa.pais_" . $lg . ", COUNT(pa.id) AS numPub FROM tbl_publicaciones p, tbl_localidades_multiidioma l, tbl_paises_multiidioma pa, tbl_subscripciones s WHERE p.localidad = l.id AND p.estado = 'A' AND p.borrado = 0 AND s.id_usuario = p.id_agente AND s.fecha_fin > NOW() AND l.id_pais = pa.id GROUP BY pa.id ORDER BY numPub DESC";
		$paises = array();
		$result = $bd->consulta($sql);

		$total = 0;
		while ($datos = $bd->obtenerfila($result)) {
			$paises[$datos['id']] = $datos['pais_' . $lg] . ' (' . $datos['numPub'] . ')';
			$total += $datos['numPub'];
		}

		$this->render('entradaDirectorioPublicaciones', array(
			'paises' => $paises)
		);
	}

    /**
     * Funcion publicaciones/directorioPublicaciones.     
     * Muestra un listado de publicaciones incluidas en profindtool
     * Es una pagina de acceso publico a la que cualquier usuario puede llegar sin necesidad de login.
     * Incorpora un sistema de filtrado para poder realizar busquedas de publicaciones
     *
     * Carga la vista themes/profind/publicaciones/directorioPublicaciones.php
     *
     * @param $estado
	 * @param $pagina
	 * @param $filtro
     * @return Listado Listado de publicaciones de Profind
     */
	public function actionDirectorioPublicaciones($estado = '', $pagina = 1, $filtro='')
	{
		global $bd;

		$elemPorPagina = $this->configuracion['elemPorPagina'];
		// Opciones de paginación
		if ($pagina < 1)
		{
			$pagina = 1;
		}

		// Configuramos el estado
		if ($estado != Publicacion::ESTADO_CERRADA && $estado != Publicacion::ESTADO_ABIERTA)
		{
			$estado = '';
		}

		$filtro .= (empty($filtro) ? '' : ' AND ') . ' id_agente IN (SELECT id_usuario FROM tbl_subscripciones WHERE fecha_fin > NOW())';

		//Se tratan los filtros
		$strFiltros = '';
		if(isset($_GET['fTitulo']) && !empty($_GET['fTitulo'])) {
			//Cambio criterio a OR
			//$filtro .= (empty($filtro) ? '' : ' AND ').' lower(titulo) LIKE "%'.strtolower($_GET['fTitulo']).'%"';
			$tituloMinuscula=strtolower($_GET['fTitulo']);
			$arrTitMinusc = explode(' ', $tituloMinuscula);
			for($i=1;$i<count($arrTitMinusc)+1;$i++) {
				    if(!isset($arrTitMinusc[$i-1])) {
					$arrTitMinusc[$i-1] = "";
				    }

			$filtro .= (empty($filtro) ? '(' : ' OR (').' lower(titulo) LIKE "%'.$arrTitMinusc[$i-1].'%")';
			}
			$strFiltros .= '&fTitulo='.$_GET['fTitulo'];
		}

		$filtro .= (empty($filtro) ? '' : ' AND ') ."borrado = 0 AND estado='A'";

		if(isset($_GET['comboPais']) && !empty($_GET['comboPais'])) {
			$consulta = "SELECT id FROM tbl_localidades_multiidioma WHERE id_pais =".$_GET['comboPais'];
			$resultado= $bd->consulta($consulta);

			//Cambiamos OR por AND para que no salgan más que los que cumplan todo
			$filtro .= (empty($filtro) ? '' : ' AND ').' (';
			//$filtro .= (empty($filtro) ? '' : ' OR ').' (';

			/*
			Se cambia:

			$filtro .= 'localidad = -1)';
			por
			$filtro .= 'localidad = -100)';

			para que no salgan los resultados en los que la localidad sea "no especificado"
			*/

			while ($datos = $bd->obtenerfila($resultado))
			{
			$filtro .= 'localidad = '.$datos['id'].' OR ';
			}
			$filtro .= 'localidad = -100)';

			$strFiltros .= '&comboPais='.$_GET['comboPais'];
		}
		if(isset($_GET['comboLocalidades']) && !empty($_GET['comboLocalidades'])) {
			$consulta = "SELECT id FROM tbl_localidades_multiidioma WHERE id =".$_GET['comboLocalidades'];
			$resultado= $bd->consulta($consulta);

			//Cambiamos OR por AND para que no salgan más que los que cumplan todo
			$filtro .= (empty($filtro) ? '' : ' AND ').' (';
			//$filtro .= (empty($filtro) ? '' : ' OR ').' (';

			/*
			Se cambia:

			$filtro .= 'localidad = -1)';
			por
			$filtro .= 'localidad = -100)';

			para que no salgan los resultados en los que la localidad sea "no especificado"
			*/

			while ($datos = $bd->obtenerfila($resultado))
			{
			$filtro .= 'localidad = '.$datos['id'].' OR ';
			}
			$filtro .= 'localidad = -100)';

			$strFiltros .= '&comboLocalidades='.$_GET['comboLocalidades'];
		}
			if(isset($_GET['comboAreas']) && !empty($_GET['comboAreas'])) {
			$consulta = "SELECT id FROM tbl_areas WHERE id =".$_GET['comboAreas'];
			$resultado= $bd->consulta($consulta);
			//$filtro .= (empty($filtro) ? '' : ' AND ').' ';
			if(isset($_GET['comboLocalidades']) && !empty($_GET['comboLocalidades']) or (isset($_GET['comboPais']) && !empty($_GET['comboPais'])))
			$filtro .= (empty($filtro) ? '' : ' AND ').' ';
			else
			//Cambiamos OR por AND para que no salgan más que los que cumplan todo
			$filtro .= (empty($filtro) ? '' : ' AND ').' ';
			//$filtro .= (empty($filtro) ? '' : ' OR ').' ';

			/*
			Se comenta lo siguiente para que no salgan los resultados en los que el área sea "no especificado"
			*/

			while ($datos = $bd->obtenerfila($resultado))
			{
			//$filtro .= 'area = '.$datos['id'].' OR ';
			$filtro .= 'area = '.$datos['id'];
			}
			//$filtro .= 'area = -1)';
			$strFiltros .= '&comboAreas='.$_GET['comboAreas'];
		}


	/*	if(isset($_GET['fArea']) && !empty($_GET['fArea'])) {
			$consulta = "SELECT id FROM tbl_areas WHERE lower(area) LIKE '%".strtolower($_GET['fArea'])."%'";
			$resultado= $bd->consulta($consulta);
			$filtro .= (empty($filtro) ? '' : ' AND ').' (';
			while ($datos = $bd->obtenerfila($resultado))
			{
			$filtro .= 'area = '.$datos['id'].' OR ';
			}
			$filtro .= 'area = -1)';
			$strFiltros .= '&fArea='.$_GET['fArea'];
		}
		*/
		/*if(isset($_GET['fTags']) && !empty($_GET['fTags'])) {
			$consulta = "SELECT id_publicacion FROM tbl_publicaciones_tags, tbl_tags WHERE tbl_publicaciones_tags.id_tag=tbl_tags.id AND lower(tag) LIKE '%".strtolower($_GET['fTags'])."%'";
			$resultado= $bd->consulta($consulta);
			$filtro .= (empty($filtro) ? '' : ' AND ').' (';
			while ($datos = $bd->obtenerfila($resultado))
			{
			$filtro .= 'id = '.$datos['id_publicacion'].' OR ';
			}
			$filtro .= 'id = -1)';

			$strFiltros .= '&fTags='.$_GET['fTags'];
		}*/

		if(isset($_GET['etiqueta1']) && !empty($_GET['etiqueta1']) || isset($_GET['etiqueta2']) && !empty($_GET['etiqueta2']) || isset($_GET['etiqueta3']) && !empty($_GET['etiqueta3']) || isset($_GET['etiqueta4']) && !empty($_GET['etiqueta4']) || isset($_GET['etiqueta5']) && !empty($_GET['etiqueta5']) || isset($_GET['etiqueta6']) && !empty($_GET['etiqueta6'])) {
		$filtro .= (empty($filtro) ? '(' : ' AND (').'  id = -1';
		}
		for($i=1;$i<7;$i++) {
			if(isset($_GET['etiqueta'.$i]) && !empty($_GET['etiqueta'.$i])) {
				$consulta = "SELECT id_publicacion FROM tbl_publicaciones_tags, tbl_tags WHERE tbl_publicaciones_tags.id_tag=tbl_tags.id AND lower(tag) LIKE '%".strtolower($_GET['etiqueta'.$i])."%'";
				$resultado= $bd->consulta($consulta);

				$filtro .= (empty($filtro) ? '' : ' OR ').' ';
				//$filtro .= (empty($filtro) ? '' : ' OR ').' (';
				while ($datos = $bd->obtenerfila($resultado))
				{
				$filtro .= 'id = '.$datos['id_publicacion'].' OR ';
				}
				$filtro .= 'id = -1 ';

				$strFiltros .= '&etiqueta'.$i.'='.$_GET['etiqueta'.$i];
			}
		}
		if(isset($_GET['etiqueta1']) && !empty($_GET['etiqueta1']) || isset($_GET['etiqueta2']) && !empty($_GET['etiqueta2']) || isset($_GET['etiqueta3']) && !empty($_GET['etiqueta3']) || isset($_GET['etiqueta4']) && !empty($_GET['etiqueta4']) || isset($_GET['etiqueta5']) && !empty($_GET['etiqueta5']) || isset($_GET['etiqueta6']) && !empty($_GET['etiqueta6'])) {
			$filtro .= ' ) ';
		}

		if((!isset($_GET['fTitulo']) || empty($_GET['fTitulo']) && !isset($_GET['comboLocalidades']) || empty($_GET['comboLocalidades']) && (!isset($_GET['comboPais']) || empty($_GET['comboPais'])) && (!isset($_GET['comboAreas']) || empty($_GET['comboAreas'])) && (!isset($_GET['etiqueta'.$i]) || empty($_GET['etiqueta'.$i]))) or (isset($_GET['comboLocalidades']) && !empty($_GET['comboLocalidades']) or (isset($_GET['comboPais']) && !empty($_GET['comboPais'])) or (isset($_GET['comboAreas']) && !empty($_GET['comboAreas'])) or (isset($_GET['etiqueta'.$i]) && !empty($_GET['etiqueta'.$i]))) )
		$filtro .= "";

			if(isset($_GET['fTitulo']) && !empty($_GET['fTitulo']) && (!isset($_GET['comboLocalidades']) || empty($_GET['comboLocalidades'])) && (!isset($_GET['comboPais']) || empty($_GET['comboPais'])) && (!isset($_GET['comboAreas']) || empty($_GET['comboAreas'])) && (!isset($_GET['etiqueta'.$i]) || empty($_GET['etiqueta'.$i])))
			$filtro .= "  ";

		if(isset($_GET['fAgente']) && !empty($_GET['fAgente'])) {
			$fAgente = intval($_GET['fAgente']);
			$filtro .= (empty($filtro) ? '' : ' AND ').' id_agente = '.$fAgente;
			$strFiltros .= '&fAgente='.$fAgente;
		}

		// Obtenemos las publicaciones
		$listaPublicaciones = new ListaPublicaciones(-1, Publicacion::ESTADO_ABIERTA, $filtro);

		$total ="<span class=\"num_publ_gr\">". $listaPublicaciones->contarTodas()."</span> ".Yii::t('profind', 'Publicaciones activas y')." ";

		$listaAgentes = new ListaAgentes('');
		$total .="<span class=\"num_publ_gr\">". $listaAgentes->contarTodos()."</span> ".Yii::t('profind', 'Recruiters seleccionando candidatos');

		$paginas = ceil($listaPublicaciones->contar() / $elemPorPagina);

		// Generamos la paginación sólo si hay más de una página
		$publicaciones = array();
		$paginacion = "";
		if ($paginas > 1)
		{
			$paginacion = Yii::t('profind', 'Página').": ";
			for ($i = 1; $i <= $paginas; $i++)
			{
				$paginacion .= (($pagina == $i) ? '<b>' : '') . '<a style="'.(($pagina == $i) ? 'color:#900; font-size:1.2em;margin-right:2px' : 'margin-right:2px').'" href="index.php?r=publicaciones/directorioPublicaciones&pagina=' . $i . $strFiltros. '">' . $i . '</a>' . (($pagina == $i) ? '</b>' : '') . ' ';
			}

			$inicio = ($pagina-1) * $elemPorPagina;

			// Obtenemos las publicaciones a mostrar
			$publicaciones = $listaPublicaciones->getSubLista($inicio, $elemPorPagina);
		}
		else
		{
			// No hay paginación, cogemos todas...
			$publicaciones = $listaPublicaciones->getTodas();
		}

		// Llamamos a la vista
		$this->render('directorioPublicaciones', array(
			'publicaciones' => $publicaciones,
			'paginacion' => $paginacion,
			'total' => $total)
		);
	}

    /**
     * Funcion publicaciones/directorioAgentes.
     * Muestra un listado de agentes de profindtool.
     * Es una página de acceso publico a la que cualquier usuario puede llegar sin necesidad de login.
     * Incorpora un sistema de filtrado para poder realizar busquedas de agentes
     *
     * Carga la vista themes/profind/publicaciones/directorioAgentes.php
     *
     * @param $estado
	 * @param $pagina
	 * @param $filtro
     * @return Listado Listado de agentes de Profind
     */
	public function actionDirectorioAgentes($estado = '', $pagina = 1, $filtro='')
	{
		global $bd;

		$elemPorPagina = $this->configuracion['elemPorPagina'];
		// Opciones de paginación
		if ($pagina < 1)
		{
			$pagina = 1;
		}

		//Se tratan los filtros
		$strFiltros = '';
		if(isset($_GET['fNombre']) && !empty($_GET['fNombre'])) {
			//$filtro .= (empty($filtro) ? '' : ' AND ').' (lower(nombre) LIKE "%'.strtolower($_GET['fNombre']).'%" OR lower(apellidos) LIKE "%'.strtolower($_GET['fNombre']).'%")';
			$nombreMinuscula=strtolower($_GET['fNombre']);
			$arrNomMinusc = explode(' ', $nombreMinuscula);
			for($i=1;$i<count($arrNomMinusc)+1;$i++) {
				    if(!isset($arrNomMinusc[$i-1])) {
					$arrNomMinusc[$i-1] = "";
				    }

			$filtro .= (empty($filtro) ? '(' : ' OR ').' (lower(nombre) LIKE "%'.$arrNomMinusc[$i-1].'%" OR lower(apellidos) LIKE "%'.$arrNomMinusc[$i-1].'%")';
			}
			$strFiltros .= '&fNombre='.$_GET['fNombre'];
		}

		if(isset($_GET['comboPais']) && !empty($_GET['comboPais'])) {
			$consulta = "SELECT id FROM tbl_localidades_multiidioma WHERE id_pais =".$_GET['comboPais'];
			$resultado= $bd->consulta($consulta);
			$filtro .= (empty($filtro) ? '' : ') AND ').' (';
			while ($datos = $bd->obtenerfila($resultado))
			{
			$filtro .= 'localidad = '.$datos['id'].' OR ';
			}
			$filtro .= 'localidad = -1)';
			$strFiltros .= '&comboPais='.$_GET['comboPais'];
		}
		if(isset($_GET['comboLocalidades']) && !empty($_GET['comboLocalidades'])) {
			$consulta = "SELECT id FROM tbl_localidades_multiidioma WHERE id =".$_GET['comboLocalidades'];
			$resultado= $bd->consulta($consulta);
			$filtro .= (empty($filtro) ? '' : ') AND ').' (';
			while ($datos = $bd->obtenerfila($resultado))
			{
			$filtro .= 'localidad = '.$datos['id'].' OR ';
			}
			$filtro .= 'localidad = -1)';
			$strFiltros .= '&comboLocalidades='.$_GET['comboLocalidades'];
		}
		if(isset($_GET['fEmpresa']) && !empty($_GET['fEmpresa'])) {
			$consulta = "SELECT id FROM tbl_empresas WHERE lower(nombre) LIKE '%".strtolower($_GET['fEmpresa'])."%'";
			$resultado= $bd->consulta($consulta);
			if(isset($_GET['comboLocalidades']) && !empty($_GET['comboLocalidades']) or (isset($_GET['comboPais']) && !empty($_GET['comboPais'])))
			$filtro .= (empty($filtro) ? '' : ' AND ').' (';
			else
			$filtro .= (empty($filtro) ? '' : ') AND ').' (';

			while ($datos = $bd->obtenerfila($resultado))
			{
			$filtro .= 'id_empresa = '.$datos['id'].' OR ';
			}
			$filtro .= 'id_empresa = -1)';
			$strFiltros .= '&fEmpresa='.$_GET['fEmpresa'];
		}
		if(isset($_GET['fNombre']) && !empty($_GET['fNombre']) && (!isset($_GET['comboLocalidades']) || empty($_GET['comboLocalidades'])) && (!isset($_GET['comboPais']) || empty($_GET['comboPais'])) && (!isset($_GET['fEmpresa']) || empty($_GET['fEmpresa'])) )
			$filtro .= " ) ";


		// Obtenemos los agentes
		$listaAgentes = new ListaAgentes($filtro);

		$listaPublicaciones = new ListaPublicaciones(-1, Publicacion::ESTADO_ABIERTA, '');
		$total ="<span class=\"num_publ_gr\">". $listaPublicaciones->contarTodas()."</span> ".Yii::t('profind', 'Publicaciones activas y')." ";

		// Obtenemos el número de publicaciones
		$total .="<span class=\"num_publ_gr\">". $listaAgentes->contarTodos()."</span> ".Yii::t('profind', 'Recruiters seleccionando candidatos');
		$paginas = ceil($listaAgentes->contar() / $elemPorPagina);

		// Generamos la paginación sólo si hay más de una página
		$agentes = array();
		$paginacion = "";
		if ($paginas > 1)
		{
			$paginacion = Yii::t('profind', 'Página').": ";
			for ($i = 1; $i <= $paginas; $i++)
			{
				$paginacion .= (($pagina == $i) ? '<b>' : '') . '<a style="'.(($pagina == $i) ? 'color:#900; font-size:1.2em;margin-right:2px' : 'margin-right:2px').'" href="index.php?r=publicaciones/directorioAgentes&pagina=' . $i . $strFiltros. '">' . $i . '</a>' . (($pagina == $i) ? '</b>' : '') . ' ';
			}

			$inicio = ($pagina-1) * $elemPorPagina;

			// Obtenemos los agentes a mostrar
			$agentes = $listaAgentes->getSubLista($inicio, $elemPorPagina);
		}
		else
		{
			// No hay paginación, cogemos todos...
			$agentes = $listaAgentes->getTodos();
		}


		// Llamamos a la vista
		$this->render('directorioAgentes', array(
			'agentes' => $agentes,
			'paginacion' => $paginacion,
			'total' => $total)
		);
	}

    /**
     * Funcion publicaciones/nueva.
     * Carga el formulario para dar de alta una nueva publicacion.
     *
     * Si ha caducado la subscripcion le redirecciona a la pantalla de Productos.
     * Si no tiene publicaciones Disponibles redirecciona a la pantalla de Productos.
     *
     * Carga la vista themes/profind/publicaciones/form_publicacion.php
     *
     * @return Pantalla Pantalla de alta de una publicacion
     */
	public function actionNueva()
	{
		// Calculamos el número de publicaciones disponibles
		$listaPublicaciones = new ListaPublicaciones(Yii::app()->user->id, '');
		// Si ha caducado la subscripcion le redirecciona a la pantalla de Productos
		if (!$this->comprobarSubscripcionActiva())
		{
			Yii::app()->user->setFlash('error', Yii::t('profind', 'Su Subscripcion ha caducado<br>Contrate un Producto para continuar disfrutando de Profind'));
			$this->redirect(array('subscripcion/modificar', 'id' => Yii::app()->user->subscripcion->id));
		}
		// Si no tiene publicaciones Disponibles redirecciona a la pantalla de Productos
		if ($listaPublicaciones->getPublicacionesDisponibles() <= 0 && Yii::app()->user->subscripcion->producto->max_publicaciones != -1)
		{
			Yii::app()->user->setFlash('error', Yii::t('profind', 'Ha superado su limite de Publicaciones abiertas a la vez<br>Contrate un Producto Superior para continuar disfrutando de Profind'));
			$this->redirect(array('subscripcion/modificar', 'id' => Yii::app()->user->subscripcion->id));
		}
		$publicacion = new Publicacion;
		$this->render('form_publicacion', array(
			'titulo' => Yii::t('profind', 'Nueva publicación'),
			'modo' => 'nueva',
			'publicacion' => $publicacion,
			'puedePonerLinks' => (Yii::app()->user->subscripcion->id_producto > 1) ? 'true' : 'false',
			'idSubscripcion' => Yii::app()->user->subscripcion->id)
		);
	}

    /**
     * Funcion publicaciones/guardarAjax.
     * Guarda los cambios realizados sobre una publicacion nueva o existente
     * Valida los datos de los campos.
     * Las etiquetas se guardan en una tabla diferente y se relacionan con las publicaciones con otra tabla de cruce.
	 *
	 * Input: 
	 *
     * $_POST['modo']
	 *
	 * $_POST['id']
	 *
	 * $_POST['titulo']
	 *
	 * $_POST['tipo_contrato']
	 *
	 * $_POST['area']
	 *
	 * $_POST['localidad']
	 *
	 * $_POST['salario']
	 *
	 * $_POST['descripcion']
	 *
	 * $_POST['etiquetas']
	 
     * @return string Ok | Error dependiendo del proceso
     */
	public function actionGuardarAjax()
	{
		global $bd;

		// Inicializamos el array de datos y de errores
		$datos = array();
		$errores = array();

		// Si estamos editando, comprobamos si podemos editar
		$modo = $_POST['modo'];
		$idPublicacion = 0;
		if ($modo == 'editar')
		{
			$idPublicacion = intval($_POST['id']);
			$publicacion = new Publicacion($idPublicacion);
			if ($publicacion === false || !$publicacion->puedeEditar($this->usuario->id))
			{
				$errores[] = array(
					'mensaje' => Yii::t('profind', 'No tiene permisos para editar esta publicación. La publicación debe estar Abierta y usted debe ser su propietario').'.'
				);
			}
		}

		// Revisamos si el usuario ha rellenado todos los campos
		$camposPublicacion = array('titulo', 'tipo_contrato', 'area', 'localidad', 'salario', 'descripcion');
		foreach ($camposPublicacion as $campo)
		{
			// Si el campo está vací­o, mostramos un error
			if (empty($_POST[$campo]) && $campo!='tipo_contrato'&& $campo!='area' && $campo!='localidad' && $campo!='salario' && $campo!='descripcion' )
			{
				$errores[] = array(
					'campo' => Yii::t('profind', $campo),
					'mensaje' => Yii::t('profind', 'No ha rellenado el campo') . Yii::t('profind', $campo)
				);
			}
			else
			{
				$datos[$campo] = $_POST[$campo];
			}
		}

		// Validamos el resto de los campos
		if (sizeof($errores) == 0)
		{
			$idProducto = Yii::app()->user->subscripcion->id_producto;
			$idSubscripcion = Yii::app()->user->subscripcion->id;

			// Comprobamos los caracteres del título y si existe alguna publicación con ese título
			if(!preg_match("/^[A-z0-9áéíóúüÁÉÍÓÚüÜçÇñÑ,.+#-\s\/]{3,100}$/", $datos['titulo']))
			{
				$errores[] = array(
					'campo' => 'titulo',
					'mensaje' => Yii::t('profind', 'El título de la publicación sólo puede usar caracteres alfanuméricos, comas, puntos, almohadillas, guiones, símbolo de adición y espacios (máximo 100 caracteres)').'.'
				);
			}
			elseif ($modo == 'nueva' || ($modo == 'editar' && $publicacion->getTitulo() != $datos['titulo']))
			{
				$consulta = "SELECT * FROM tbl_publicaciones WHERE titulo LIKE '" . $bd->escapar($datos['titulo']) . "' AND id_agente = " . Yii::app()->user->id;
				$resultado = $bd->consulta($consulta);
				if ($bd->numfilas($resultado))
				{
					$errores[] = array(
						'campo' => Yii::t('profind', 'titulo'),
						'mensaje' => Yii::t('profind', 'Ya tiene una publicación con ese título').'.'
					);
				}
			}
			// Buscamos emails y url en la descripción
			elseif($idProducto <= 1 && (preg_match('/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/i', $datos['descripcion']) == 1 || preg_match('/[a-z0-9-]+(\.[a-z]{2,3})+/i', $datos['descripcion'])) == 1)
			{
				$errores[] = array(
					'campo' => Yii::t('profind', 'descripcion'),
					'mensaje' => Yii::t('profind', 'La introducción de direcciones de e-mail o urls es una opción exclusiva de las suscripciones Profesional y Profesional Plus') . Yii::t('profind', 'Clique') . ' <a href="index.php?r=subscripcion/modificar&id=' . $idSubscripcion . '">' . Yii::t('profind', 'aquí') . '</a> ' . Yii::t('profind', 'para actualizar su cuenta') . '. ' . Yii::t('profind', 'En cualquier caso, las candidatos inscritos y sus mensajes podrá siempre verlos en su cuenta de Profind y cada vez que se produzca uno nuevo se le notificará a la dirección de correo con la que se ha registrado en Profind.').'.'
				);
			}
			// Buscamos emails y url en el titulo
			elseif($idProducto <= 1 && (preg_match('/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/i', $datos['titulo']) == 1 || preg_match('/[a-z0-9-]+(\.[a-z]{2,3})+/i', $datos['titulo'])) == 1)
			{
				$errores[] = array(
					'campo' => Yii::t('profind', 'titulo'),
					'mensaje' => Yii::t('profind', 'La introducción de direcciones de e-mail o urls es una opción exclusiva de las suscripciones Profesional y Profesional Plus') . Yii::t('profind', 'Clique') . ' <a href="index.php?r=subscripcion/modificar&id=' . $idSubscripcion . '">' . Yii::t('profind', 'aquí') . '</a> ' . Yii::t('profind', 'para actualizar su cuenta') . '. ' . Yii::t('profind', 'En cualquier caso, las candidatos inscritos y sus mensajes podrá siempre verlos en su cuenta de Profind y cada vez que se produzca uno nuevo se le notificará a la dirección de correo con la que se ha registrado en Profind.').'.'
				);
			}

			// Comprobamos que la localidad sea de la lista
			$municipios = Municipio::model()->findAll();
			$idMunicipio = 0;
			foreach($municipios as $municipio) {
				if($municipio->id==$datos['localidad']) {
				$idMunicipio = $municipio->id;
				}
			}
			$datos['localidad'] = $idMunicipio;

			// Comprobamos que el area sea de la lista
			$listaAreas = new ListaAreas();
			$idArea = $listaAreas->getId($datos['area']);
			$datos['area'] = $idArea;

			// Comprobamos el salario
			//$datos['salario'] = intval($datos['salario']);
			/*if ($datos['salario'] == '')
			{
				$errores[] = array(
					'campo' => 'salario',
					'mensaje' => 'Debe introducir un salario para continuar.'
				);
			}*/

			// Tratamos las etiquetas
			$etiquetas = array();
			if (!empty($_POST['etiquetas']))
			{
				$etiquetas = explode(',', $_POST['etiquetas']);
				foreach ($etiquetas as $etiqueta)
				{
					if (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ+#?!.-]{3,30}$/', $etiqueta))
					{
						$errores[] = array(
							'campo' => Yii::t('profind', 'etiquetas'),
							'mensaje' => Yii::t('profind', 'Compruebe sus tags. Un tag puede tener entre 3 y 30 caracteres, los caracteres deben ser alfanúmericos o caracteres permitidos como # - . ñ').'.'
						);
						break;
					}
				}
			}
			else
			{
				// TODO - Por hacer (auto-generar etiquetas)
			}
		}

		$resultado = array();
		// Ejecutamos la consulta y guardamos
		if (sizeof($errores) == 0)
		{
			// Completamos los datos que faltan
			$datos['id_agente'] = Yii::app()->user->id;

			//if (!Yii::app()->user->esCoordinador)
			//{
				// TODO - No hay coordinadores en el sistema
				// $datos['id_agente_coordinador'] = Yii::app()->user->id;
			//}

			if ($modo == 'editar')
			{
				$datos['fecha_modificacion'] = time();

				// Guardamos la publicación en la base de datos
				$bd->modificar('tbl_publicaciones', $datos, 'id = ' . $idPublicacion);

				// Borramos las etiquetas que haya activas
				$consulta = "DELETE FROM tbl_publicaciones_tags WHERE id_publicacion = " . $idPublicacion;
				$bd->consulta($consulta);
			}
			else
			{
				// Guardamos la publicación en la base de datos
				$datos['fecha_creacion'] = time();
				$datos['estado'] = Publicacion::ESTADO_ABIERTA;
				$datos['difusion_basica'] = '';
				$idPublicacion = $bd->insertar('tbl_publicaciones', $datos);

				// Generamos el enlace a la vista pública
				$enlace = new EnlacePublicacion('', 'index.php?r=publicaciones/vistaPublica&id=' . $idPublicacion);
				$datos = array(
					'enlace_publico' => $enlace->getCodigoEnlace()
				);
				$bd->modificar('tbl_publicaciones', $datos, 'id = ' . $idPublicacion);
			}

			// Guardamos las etiquetas en la base de datos
			$campos = array('id_publicacion', 'id_tag');

			$datosEtiquetas = array();
			foreach ($etiquetas as $etiqueta)
			{
				$etiquetaTmp = strtolower($etiqueta);

				// Comprobamos que la etiqueta sea de la lista
				$tag = Tag::model()->findByAttributes(array(), "LOWER(tag)='".$etiquetaTmp."'");

				$idTag = 0;
				if ($tag===null)
				{
					// Si el tag no existe, creamos un nuevo tag
					$datosTag = array(
						'tag' => $bd->escapar($etiqueta),
					);
					$bd->insertar('tbl_tags', $datosTag);
					$idTag = $bd->insert_id();
				}
				else
				{
					$idTag = $tag->id;
				}
				$datosEtiquetas[] = array($idPublicacion, $idTag);
			}

			if (sizeof($datosEtiquetas) > 0)
			{
				$bd->insertarVarios('tbl_publicaciones_tags', $campos, $datosEtiquetas);
			}

			// Todo bien, devolvemos un OK
			$resultado = array(
				'resultado' => 'ok',
				//'redirigir' => 'index.php?r=difusion/configurar&id=' . $idPublicacion
				'redirigir' => 'index.php?r=network/index&id=' . $idPublicacion
			);
		}
		else
		{
			// Ha ocurrido un error
			$resultado = array(
				'resultado' => 'error',
				'errores' => $errores
			);
		}

		// Imprimimos el resultado en JSON
		echo json_encode($resultado);
		exit;
	}

    /**
     * Funcion publicaciones/ver.
     * Muestra el resumen de una publicacion con todos los datos que tienen que ver con ella.
     * Tambien se carga un resumen de los candidatos y de las difusiones en redes sociales
     *
     * Carga la vista themes/profind/publicaciones/ver.php
     *
     * @param int $id ID de la publicación
     * @return Resumen Pagina resumen de una publicación
     */
	public function actionVer($id)
	{
		include('protected/widgets/widgetCajaIndividuo.php');
		global $bd;

		// Comprobamos los permisos
		$id = intval($id);
		$publicacion = new Publicacion($id);
		if ($publicacion === false)
		{
			$this->render('error', array(
				'mensaje' => Yii::t('profind', 'Lo sentimos, pero la publicación que ha solicitado no existe').'.')
			);
		}

		if ($publicacion->getAgente()->id != Yii::app()->user->id && $publicacion->getAgenteCoordinador() !== false && $publicacion->getAgenteCoordinador()->id != Yii::app()->user->id)
		{
			$this->render('error', array(
				'mensaje' => Yii::t('profind', 'Lo sentimos, pero usted no tiene permiso para ver esta publicación').'.')
			);
		}

		$lista = new ListaInteresados($id);
		$candidatos=$lista->inscripciones;

		// Mandamos las variables a la vista
		$this->render('ver', array(
			'publicacion' => $publicacion,
			'candidatos' => $candidatos,
			'id' => $id,
			'redesSociales' => $this->getRedesSociales())
		);
	}

    /**
     * Funcion publicaciones/editar.
     * Formulario con los datos de la publicacion cargados y editables.
     *
     * Carga la vista themes/profind/publicaciones/form_publicacion.php
     *
     * @param int $id ID de la publicación
     * @return Edicion Pagina de edicion de una publicacion
     */
	public function actionEditar($id)
	{
		// Comprobamos los permisos
		$id = intval($id);
		$publicacion = new Publicacion($id);
		if ($publicacion === false)
		{
			$this->render('error', array(
				'mensaje' => Yii::t('profind', 'Lo sentimos, pero la publicación que ha solicitado no existe').'.')
			);
		}

		if ($publicacion->getAgente()->id != Yii::app()->user->id)
		{
			$this->render('error', array(
				'mensaje' => Yii::t('profind', 'Lo sentimos, pero usted no tiene permiso para editar esta publicación').'.')
			);
		}

		$this->render('form_publicacion', array(
			'titulo' => Yii::t('profind', 'Editar publicación'),
			'modo' => 'editar',
			'publicacion' => $publicacion,
			'puedePonerLinks' => (Yii::app()->user->subscripcion->id_producto > 1) ? 'true' : 'false',
			'idSubscripcion' => Yii::app()->user->subscripcion->id)
		);
	}

    /**
     * Funcion publicaciones/matching.
     * Pantalla de posibles candidatos sugeridos por la plataforma a partir de los tags relacionados.
     * Permite modificar los filtros dinámicamente.
     *
     * Carga la vista themes/profind/publicaciones/matching.php
     *
     * @param int $id ID de la publicación
	 * @param int $pagina
     * @return Pagina matching de una publicacion
     */
	public function actionMatching($id, $pagina = 1)
	{
		global $bd;

		// Comprobamos los permisos
		$id = intval($id);
		$publicacion = new Publicacion($id);
		if ($publicacion === false)
		{
			$this->render('error', array(
				'mensaje' => 'Lo sentimos, pero la publicación que ha solicitado no existe.')
			);
		}

		/*$arrayBusquedas = new MotorBusquedas();

		$total = $arrayBusquedas->obtenerTotalCoincidencias($publicacion->getEtiquetas()->getLista());
		$elemPorPagina = 40;
		$inicio = 0;
		$paginas = ceil($total / $elemPorPagina);

		// Generamos la paginación sólo si hay más de una página
		$publicaciones = array();
		$paginacion = "";

		if ($paginas > 1)
		{
			$paginacion = Yii::t('profind', 'Página').": ";
			for ($i = 1; $i <= $paginas; $i++)
			{
				$paginacion .= (($pagina == $i) ? '<b>' : '') . '<a href="index.php?r=publicaciones/matching&id=' . $id . '&pagina=' . $i . '">' . $i . '</a>' . (($pagina == $i) ? '</b>' : '') . ' ';
			}

			$inicio = ($pagina-1) * $elemPorPagina;
		}
		$listaCandidatos =  array();
		if($publicacion->getEtiquetas()->getLista()!=""){
			// Obtenemos las publicaciones a mostrar
			$listaCandidatos = $arrayBusquedas->obtenerArrCandidatosXTag($publicacion->getEtiquetas()->getLista(), $enlaceCondiciones, $inicio, $elemPorPagina);
			//$publicaciones = $listaPublicaciones->getSubLista();
		}*/
		//if ($publicacion->getEstado() == Publicacion::ESTADO_ABIERTA)
		//{
			//$listaUsuariosMatching = new ListaUsuariosMatching(Yii::app()->user->id);

			$this->render('matching', array(
				'publicacion' => $publicacion,
				//'listaCandidatos' => $listaCandidatos,
				//'total' => $total,
				//'paginacion' => $paginacion,
				//'matching' => $listaUsuariosMatching
				)
			);
		/*}
		else
		{
			$this->render('error', array(
				'mensaje' => 'Usted no puede acceder al matching de una publicación cerrada. <a href="index.php?r=publicaciones/abrir&id=' . $publicacion->getId() . '&return=difusion">Abrir publicación</a>.')
			);
		}*/
	}

    /**
     * Funcion publicaciones/matchingAjax.
     * Recarga candidatos sugeridos por la plataforma al modificar los filtros cruces entre tags
	 *
     * Se invoca por AJAX desde generaListaMatchingTag() de func.js
     *
     * Input:
	 *
	 * int $_POST['id']
	 *
	 * int $_POST['pagina']
	 *
	 * string $_POST['strTags']
	 *
	 * string $_POST['condicion']
	 
     * @return Listado Listado de candidatos en HTML
     */
	public function actionMatchingAjax()
	{
		global $bd;

		$filtroPublicacion = "";
		if(isset($_POST['id'])) {
			$id=$_POST['id'];
			$filtroPublicacion = "OR id_publicacion=".$id;
		}
		$pagina=$_POST['pagina'];
		$strTags=$_POST['strTags'];
		$enlaceCondiciones=$_POST['condicion'];

		$elemPorPagina = 40;
		$arrClasesTags = array();

		// Comprobamos los permisos
		$id = intval($id);
		$publicacion = new Publicacion($id);
		if ($publicacion === false)
		{
			$this->render('error', array(
				'mensaje' => Yii::t('profind', 'Lo sentimos, pero la publicación que ha solicitado no existe').'.')
			);
		}
		if (!$strTags) {
			$strTags = $publicacion->getEtiquetas()->getLista();
			$enlaceCondiciones='OR';
		}

		$arrayBusquedas = new MotorBusquedas();
		$arrTags = array();
		$arrTagsTmp = explode(',', $strTags);
		foreach($arrTagsTmp as $tagTmp) {
			$etiqueta = Tag::model()->findByAttributes(array('tag' => $tagTmp));
			if(isset($etiqueta->tag)) {
			$arrTags[$etiqueta->id] = $etiqueta->tag;
			}
		}

		$total = $arrayBusquedas->obtenerTotalCoincidencias($strTags, $enlaceCondiciones, $filtroPublicacion);

		$inicio = 0;
		$paginas = ceil($total / $elemPorPagina);

		// Generamos la paginación sólo si hay más de una página
		$publicaciones = array();
		$paginacion = "";

		if ($paginas > 1)
		{
			$paginacion = Yii::t('profind', 'Página').": ";
			for ($i = 1; $i <= $paginas; $i++)
			{
				$paginacion .= (($pagina == $i) ? '<b>' : '') . '<a style="'.(($pagina == $i) ? 'color:#900; font-size:1.2em; cursor:pointer;margin-right:2px' : 'margin-right:2px').'" onclick="generaListaMatchingTag('.$id.', \''.$strTags.'\', \''.$enlaceCondiciones.'\', '.$i.')">' . $i . '</a>' . (($pagina == $i) ? '</b>' : '') . ' ';
			}

			$inicio = ($pagina-1) * $elemPorPagina;
		}

		$htmlRespuesta = "";
		$listaCandidatos =  array();
		if($publicacion->getEtiquetas()->getLista()!=""){
			// Obtenemos las publicaciones a mostrar
			$listaCandidatos = $arrayBusquedas->obtenerArrCandidatosXTag($strTags, $enlaceCondiciones, $filtroPublicacion, $inicio, $elemPorPagina);

			if(count($listaCandidatos)) {

			}
			else {
			//$listaCandidatos = "error";
			}
		}
		else {
			$htmlRespuesta = "<span style='color:red'>".Yii::t('profind', 'Todavía no tiene tags en su Publicación, haga click en editar para insertarlos')."</span>";
		}

		$arrClasesTags = array();
		$iTmp = 0;
		foreach($arrTags as $key => $tag) {
			$arrClasesTags[$key] = 'coincidencia_'.$iTmp;
			$iTmp++;
		}

		if($htmlRespuesta==""){
			$htmlRespuesta .= '<table style="color:#000000;" cellpadding="3" width="100%">
			<tr style="background-color:#b0fa95;text-align:center;font-weight:bold;">';
			$htmlRespuesta .= '<td>'.Yii::t("profind", "Candidato").'</td>';
			$htmlRespuesta .= '<td>'.Yii::t("profind", "Email").'</td>';
			$htmlRespuesta .= '<td>'.Yii::t("profind", "Publicación").'</td>';
			$htmlRespuesta .= '<td>'.Yii::t("profind", "Fecha").'</td>';
			$htmlRespuesta .= '<td>'.Yii::t("profind", "Estado").'</td>';
			$htmlRespuesta .= '<td>'.Yii::t("profind", "Fuentes").'</td>';
			$htmlRespuesta .= '<td>'.Yii::t("profind", "Tags").'</td>';
			$htmlRespuesta .= '<td>'.Yii::t("profind", "Acciones").'</td>';
			$htmlRespuesta .= '</tr>';

			if(count($listaCandidatos)) {
			$par = 0;
			foreach ($listaCandidatos as $idCandidato => $filaTagxCandidato) {
				foreach ($filaTagxCandidato as $idPubTmp => $filaTag) {
				$publicacionTemp = new Publicacion($idPubTmp);

				$strEstiloFondoFila = "";
				if($par%2==0) {
					$strEstiloFondoFila = "background-color:#EFF2FB;";
				}
				$strTitulo = '<a href="index.php?r=publicaciones/ver&id='.$idPubTmp.'">'.$publicacionTemp->getTitulo().'</a>';
				if($idPubTmp==$publicacion->getId()) {
					$strEstiloFondoFila .= "color:#088A29;";
					//$strTitulo = '<a>-</a>';
				}
				$modelo = Inscripcion::model()->findByPk($filaTag['idInscripcion']);
				$fuentes=$modelo->fuentes;
				$iconosRedes = '';

				if($fuentes[0]=='1') {
					$filename1='cv/'.$modelo->id_candidato.'_cv.pdf';
					$filename2='cv/'.$modelo->id_candidato.'_cv.docx';
					$filename3='cv/'.$modelo->id_candidato.'_cv.doc';
					if (file_exists ($filename1))
					$iconosRedes .= '<a target="_blank" href="cv/'.$modelo->id_candidato.'_cv.pdf"><img src="images/email.png" title="'.Yii::t('profind', 'Ver CV').'" style="width:13px; height:13px; cursor:pointer; margin-right:2px;" alt="'.Yii::t('profind', 'CV').'"></a>';
					else if(file_exists ($filename2))
					$iconosRedes .= '<a target="_blank" href="cv/'.$modelo->id_candidato.'_cv.docx"><img src="images/email.png" title="'.Yii::t('profind', 'Ver CV').'" style="width:13px; height:13px; cursor:pointer; margin-right:2px;" alt="'.Yii::t('profind', 'CV').'"></a>';
					else if(file_exists ($filename3))
					$iconosRedes .= '<a target="_blank" href="cv/'.$modelo->id_candidato.'_cv.doc"><img src="images/email.png" title="'.Yii::t('profind', 'Ver CV').'" style="width:13px; height:13px; cursor:pointer; margin-right:2px;" alt="'.Yii::t('profind', 'CV').'"></a>';
				}

				// Iconos de perfiles de redes
				$enlacesRedes = $modelo->getEnlacesRedes();
				$redesSociales = getRedesSociales();
				foreach ($redesSociales as $redSocial)
				{
					// Obtenemos los datos de la Red Social
					$datosRedSocial = $redSocial->obtenerDatosRed();

					// Pintamos los datos de la red
					if (!empty($enlacesRedes[$redSocial->getIdRed()]))
					{
					$iconosRedes .= '<a href="' . $enlacesRedes[$redSocial->getIdRed()] . '" target="_blank"><img src="images/social/' . strtolower($datosRedSocial['nombre_clase']) . '.png" style="width:18px; height:18px;" alt="' . $datosRedSocial['nombre'] . '"></a>&nbsp';
					}
				}

				$arrTagsTmp = $publicacionTemp->getEtiquetas()->getTodas();

				$arrEstados = array();
				$arrEstados[0] = Yii::t("profind", "Mensaje");
				$arrEstados[1] = Yii::t("profind", "Pendiente");
				$arrEstados[2] = Yii::t("profind", "Aprobado");
				$arrEstados[3] = Yii::t("profind", "Declinado");
				$arrEstados[4] = Yii::t("profind", "Invitado");

				if($publicacion->getId()!=$idPubTmp) {

					$htmlRespuesta .= '<tr id="fila_'.$idCandidato.'_'.$idPubTmp.'" style="'.$strEstiloFondoFila.'">
					<td><a style="cursor:pointer;" onclick="despliegaInscripciones('.$idCandidato.','.$idPubTmp.')">'.$filaTag['nombre'].'</a></td>
					<td>'.$filaTag['email'].'</td>
					<td>'.$strTitulo.'</td>
					<td>'.referenciaFecha(strtotime($filaTag['fechaInscripcion'])).'</td>
					<td>'.$arrEstados[$filaTag['estado']].'</td>
					<td>'.$iconosRedes.'</td>
					<td>';
					$primeraVuelta=0;
					foreach($arrTagsTmp as $key => $tag) {
					$coma = "";
					if($primeraVuelta!=0) {
						$coma = ",";
					}
					$claseTmp = "";
					if(isset($arrClasesTags[$key])) {
						$claseTmp = $arrClasesTags[$key];
					}
					$primeraVuelta++;

					$htmlRespuesta .= '<span style="float:left;margin:1px;" class="'.$claseTmp.'">'.$coma.$tag.'</span>';
					}
					$htmlRespuesta .= '</td>
					<td><input  type="button" title="'.Yii::t('profind', 'Invitar').'"  value="'.Yii::t('profind', 'Invitar').'" onclick="invitacion('.$publicacion->getId().', '.$publicacionTemp->getAgente()->id.','.$idCandidato.');"/></td>
					</tr>';
					$par++;
				}
				}
			}
			}
			else {
			$htmlRespuesta .= '<tr><td colspan=8 style="color:red">'.Yii::t('profind', 'No se han encontrado coincidencias').'<td></tr>';
			}

			$htmlRespuesta .= '</table>';

			$htmlRespuesta .= $paginacion;
		}
		echo $htmlRespuesta;
		exit;
	}

    /**
     * Funcion publicaciones/cerrar.
     * Cierra una publicacion
     *
     * @param $id ID de la publicación
	 * @param $return
     */
	public function actionCerrar($id, $return)
	{
		$this->cambioEstado($id, Publicacion::ESTADO_CERRADA, $return);
	}

    /**
     * Funcion publicaciones/abrir.
     * Abre una publicacion comprobado previamente la subscripcion del agente.
	 *
     * Si ha caducado la subscripcion le redirecciona a la pantalla de Productos.
     * Si no tiene publicaciones Disponibles redirecciona a la pantalla de Productos
     *
     * @param int $id ID de la publicación
	 * @param string $return
     */
	public function actionAbrir($id, $return)
	{
		$listaPublicaciones = new ListaPublicaciones(Yii::app()->user->id, '');

		// Si ha caducado la subscripcion le redirecciona a la pantalla de Productos
		if (!$this->comprobarSubscripcionActiva() || ($listaPublicaciones->getPublicacionesDisponibles() <= 0 && Yii::app()->user->subscripcion->producto->max_publicaciones != -1))
		{
			Yii::app()->user->setFlash('error', Yii::t('profind', 'Su Subscripcion ha caducado<br>Contrate un Producto para continuar disfrutando de Profind'));
			$this->redirect(array('subscripcion/modificar', 'id' => Yii::app()->user->subscripcion->id));
		}
		else
		{
			// Si no tiene publicaciones Disponibles redirecciona a la pantalla de Productos
			if ($listaPublicaciones->getPublicacionesDisponibles() <= 0 && Yii::app()->user->subscripcion->producto->max_publicaciones != -1)
			{
				Yii::app()->user->setFlash('error', Yii::t('profind', 'Ha superado su limite de Publicaciones abiertas a la vez<br>Contrate un Producto Superior para continuar disfrutando de Profind'));
				$this->redirect(array('subscripcion/modificar', 'id' => Yii::app()->user->subscripcion->id));
			}
			// Cambiamos el estado
			$this->cambioEstado($id, Publicacion::ESTADO_ABIERTA, $return);
		}
	}

    /**
     * Funcion publicaciones/cambioEstado.
     * Cambia el estado de la publicacion, si se esta cerrando se borra la difusion
     *
     * @param int $id ID de la publicación
	 * @param int $estadoFinal de la publicación
	 * @param string $return PAGINA a la que se redirecciona
     */
	private function cambioEstado($id, $estadoFinal, $return)
	{
		global $bd;

		// Actualizamos la publicación si el usuario tiene permisos
		$idAgente = Yii::app()->user->id;
		$consulta = "UPDATE tbl_publicaciones SET estado = '" . $estadoFinal . "' WHERE id = " . $id . " AND id_agente = " . $idAgente;
		$bd->ejecutar($consulta);

		// Borramos la difusión
		if ($estadoFinal != Publicacion::ESTADO_ABIERTA)
		{
			$id = intval($id);
			$publicacion = new Publicacion($id);
			$difusion = $publicacion->getDifusion(false);
			$difusion->borrarProgramacion($id, $idAgente, '*', '*', '*', '*', '*');
		}

		// Redirigimos
		$return = ($return == 'difusion') ? 'difusion' : ('publicaciones/' . $return);
		header('Location: index.php?r=' . $return . '&id=' . $id);
		exit;
	}

    /**
     * Funcion publicaciones/cambioEstadoAjax.
     * Cambia el estado de la publicacion, si se esta cerrando se borra la difusion.
	 
     * Se invoca por AJAX desde cambioEstadoPublicacion() de func.js
     *
     * @param int $id ID de la publicación
	 * @param int $estadoFinal de la publicación
     */
	public function actionCambioEstadoAjax($id, $estadoFinal)
	{
		$idAgente = Yii::app()->user->id;
		$listaPublicaciones = new ListaPublicaciones($idAgente, '');
		// Si ha caducado la subscripcion le redirecciona a la pantalla de Productos
		if (!$this->comprobarSubscripcionActiva() || $listaPublicaciones->getPublicacionesDisponibles() <= 0)
		{
			//echo false;
		}

		global $bd;

		// Actualizamos la publicación si el usuario tiene permisos
		$consulta = "UPDATE tbl_publicaciones SET estado = '" . $estadoFinal . "' WHERE id = " . $id . " AND id_agente = " . Yii::app()->user->id;
		$bd->ejecutar($consulta);

		// Borramos la difusión
		if ($estadoFinal != Publicacion::ESTADO_ABIERTA)
		{
			$id = intval($id);
			$publicacion = new Publicacion($id);
			$difusion = $publicacion->getDifusion(false);
			$difusion->borrarProgramacion($id, $idAgente, '*', '*', '*', '*', '*');
		}

		echo true;
		exit;
	}

    /**
     * Funcion publicaciones/vistaPublica.
     * Presentacion de una publicacion de cara a los candidatos.
     * Es una pagina de acceso publico a la que cualquier usuario puede llegar sin necesidad de login.
     * Controla el registro de los candidatos a traves de redes sociales o con CV.
     *
     * Carga la vista themes/profind/publicaciones/vistaPublica.php
     *
     * @param int $id ID de la publicación
	 * @param int $idProc ID de procedencia
	 * @param int $confirmado CONFIRMACION de la inscripción 
	 * @param int idRemitente ID del candidato
	 * @param int $idMensaje ID del mensaje
	 * @param int $idRed ID de la red social
     * @return Vista publica  Vista publica de una publicacion
     */
	public function actionVistaPublica($id, $idProc=-1, $confirmado=-1, $idRemitente=-1, $idMensaje=-1, $idRed=-1)
	{
		global $bd;
		//Obtenemos la publicación
		$id = intval($id);
		$publicacion = new Publicacion($id);
		//Obtenemos los datos del agente de esa publicación
		$agente = $publicacion->getAgente();
		if (!empty($agente))
		{
			$empresa = null;
			if (!empty($agente->id_empresa))
			{
				$empresa = Empresa::model()->findByPk($agente->id_empresa);
			}

			// Activamos la fuente si se trata de una red social
			if ($confirmado == 1 && $idRed > 0)
			{
				$inscripcion = Inscripcion::model()->findByAttributes(array(
					'id_candidato' => $idRemitente,
					'id_publicacion' => $id,
				));

				$fuentes = '';
				for ($i = 0; $i < strlen($inscripcion->fuentes); $i++)
				{
					if ($idRed-1 == $i)
					{
						$fuentes .= '1';
					}
					else
					{
						$fuentes .= $inscripcion->fuentes{$i};
					}
				}
				$inscripcion->fuentes = $fuentes;

				$inscripcion->save();
			}

			$publicacionesRecientes = 0;
			$listaPublicaciones = new ListaPublicaciones($agente->id, Publicacion::ESTADO_ABIERTA);
			if($listaPublicaciones->numPublicaciones > 1)
			{
				$publicacionesRecientes = $listaPublicaciones->getTodas();
			}

			$redesSociales = $this->getRedesSociales(false);

		/*Envío automático de mensaje a candidato tras inscripción, suprimido de momento
			if ($confirmado==1) {

				$remitente = Remitente::model()->findByPk($idRemitente);
				EMail::mensajeACandidatoTrasMensaje($remitente->email, $publicacion, $remitente->nombre, 1);
			}

			if ($confirmado==2) {

				$remitente = Remitente::model()->findByPk($idRemitente);
				EMail::mensajeACandidatoTrasMensaje($remitente->email, $publicacion, $remitente->nombre, 2);
			}
		*/
		}

		$this->render('vistaPublica', array(
			'publicacion' => $publicacion,
			'agente' => $agente,
			'empresa' => $empresa,
			'confirmado' => $confirmado,
			'idRemitente' => $idRemitente,
			'idMensaje' => $idMensaje,
			'idProc' => $idProc,
			'publicacionesRecientes' => $publicacionesRecientes,
			'redesSociales' => $redesSociales
		));
	}

    /**
     * Funcion publicaciones/interesados.
     * Lista de candidatos interesados en una publicacion.
     * Se extrae con una paginacion dinamica basada en la posicion del scroll para aligerar la carga en bbdd.
     *
     * Carga la vista themes/profind/publicaciones/interesados.php
     *
     * @param int $id ID de la publicación 
	 * @param string $filtro
     * @return Listado de interesados Listado de interesados en una publicación
     */
	public function actionInteresados($id=-1, $filtro="")
	{
		$id = intval($id);
		$publicacion = new Publicacion($id);

		$agente = Usuario::model()->findByPk(Yii::app()->user->id);
		$empresa = Empresa::model()->findByPk($agente->id_empresa);
		//Listamos los candidatos para la publicación o para todas las publicaciones, dependiendo de que le pasemos $id o no (será $id=-1)
		$lista = new ListaInteresados($id);
		$candidatos=$lista->inscripciones;

				$fechaUltimoListado=$lista->fechaUltimaInscripcion;

				$this->render('interesados', array(
				'publicacion' => $publicacion,
				'agente' => $agente,
				'empresa' => $empresa,
				'candidatos' => $candidatos,
				'filtro' => $filtro,
				'id' => $id,
				'fechaUltimoListado' => $fechaUltimoListado)
			);
	}

    /**
     * Funcion publicaciones/listarInteresadosAjax.
     * Extrae una lista de interesados de la bbdd para ir componiendo la pantalla interesados.
	 *
     * Se invoca por AJAX desde listarInscripciones() de func.js
     *
     * @param int $id
	 * @param string $filtro
	 * @param $fechaU
     * @return Listado de interesados en HTML
     */
	public function actionListarInteresadosAjax($id=-1, $filtro="", $fechaU=-1)
	{
		include('protected/widgets/widgetCajaIndividuo.php');
		global $bd;

				$lista = new ListaInteresados($id, $fechaU, $filtro);
				//$lista->getListaPorFiltro($id, $filtro);
				$candidatos=$lista->inscripciones;

		// Cargamos la publicación (sólo si estamos filtrando por una en concreto)
		$publicaciones = array();
		$id = intval($id);
		if ($id != -1)
		{
			$publicacion = new Publicacion($id);
		}
		foreach ($candidatos as $modelo) :
			// Cargamos la publicación si no estaba cargada ya
			$idPub = $modelo->getIdPublicacion();
			if ($id == -1 && empty($publicaciones[$idPub]))
			{
				$publicaciones[$idPub] = new Publicacion($idPub);
			}

			// Si ID = -1, guardamos en publicación la publicación que corresponde al candidato
			if ($id == -1)
			{
				$publicacion = $publicaciones[$idPub];
			}

			// Mostramos el widget
			widgetCajaIndividuo($modelo, $publicacion, 2, $id, $filtro);
		endforeach;
		echo "__||__".$lista->fechaUltimaInscripcion;
		exit;
	}

	/*public function actionListarInscripcionEstadoAjax($id=-1, $estado=0)
	{
		include('protected/widgets/widgetCajaIndividuo.php');
		global $bd;

		$id = intval($id);
		$lista = new ListaInteresados($id);
		$candidatos=$lista->getListaPorEstado($id, $estado);

		// Cargamos la publicación (sólo si estamos filtrando por una en concreto)
		$publicaciones = array();
		if ($id != -1)
		{
			$publicacion = new Publicacion($id);
		}

		foreach ($candidatos as $modelo) :
			// Cargamos la publicación si no estaba cargada ya
			$idPub = $modelo->getIdPublicacion();
			if ($id == -1 && empty($publicaciones[$idPub]))
			{
				$publicaciones[$idPub] = new Publicacion($idPub);
			}

			// Si ID = -1, guardamos en publicación la publicación que corresponde al candidato
			if ($id == -1)
			{
				$publicacion = $publicaciones[$idPub];
			}

			// Mostramos el widget
			widgetCajaIndividuo($modelo, $publicacion, 2, $id);

		endforeach;

		exit;
	}*/

    /**
     * Funcion publicaciones/exportarInteresadosCsv.
     * Extrae una lista de interesados de la bbdd para ir componiendo la pantalla interesados.
	 *
     * Se invoca por AJAX desde exportarInteresados() de func.js
     *
     * @param int $id ID de la publicación
	 * @param string $filtro
     * @return Fichero_csv Fichero csv con la lista de interesados
     */
	public function actionExportarInteresadosCsv($id=-1, $filtro="")
	{

			include('protected/widgets/widgetCajaIndividuo.php');
			global $bd;

			$lista = new ListaInteresados($id);
			$lista->getListaPorFiltro($id, $filtro);
			$candidatos=$lista->inscripciones;

			$separador = ";";

			$linea = Yii::t('profind', 'Candidato'). $separador .
					Yii::t('profind', 'Email'). $separador .
					Yii::t('profind', 'Tipo'). $separador .
					Yii::t('profind', 'Fuentes'). $separador .
					Yii::t('profind', 'Publicación'). $separador .
					Yii::t('profind', 'Estado')."\n\n";

			foreach ($candidatos as $modelo) :
				$strTipo = "";
				if($modelo->nivel_comunicacion==2) {
					$strTipo = Yii::t('profind', 'Solo Mensaje');
				}
				else {
					$strTipo = Yii::t('profind', 'Inscripción');
				}

				$fuentes = $modelo->fuentes;
				$strFuentes = "";

				if($fuentes[0]==1) {
					$strFuentes = "Cv";
				}
				if($fuentes[1]==1) {
					if($strFuentes=="") {
						$strFuentes = "Linkedin";
					}
					else {
						$strFuentes .= "/Linkedin";
					}
				}
				if($fuentes[2]==1) {
					if($strFuentes=="") {
						$strFuentes = "Facebook";
					}
					else {
						$strFuentes .= "/Facebook";
					}
				}
				if($fuentes[3]==1) {
					if($strFuentes=="") {
						$strFuentes = "Twitter";
					}
					else {
						$strFuentes .= "/Twitter";
					}
				}

				$strEstado = '';
				switch($modelo->estado) {
					case 1: {
						$strEstado = Yii::t('profind', 'Pendiente');
						break;
					}
					case 2: {
						$strEstado = Yii::t('profind', 'Aprobado');
						break;
					}
					case 3: {
						$strEstado = Yii::t('profind', 'Declinado');
						break;
					}
					default: {

						break;
					}
				}

				$linea .= $modelo->getNombre() . $separador .
						$modelo->getEmail() . $separador .
						$strTipo . $separador .
						$strFuentes . $separador .
						$modelo->getPublicacion()->getId()." ". $modelo->getPublicacion()->getTitulo() . $separador .
						$strEstado."\n";

			endforeach;

			header("Content-Type: application/csv; utf8");
			//header('Content-type: application/vnd.ms-excel');
			header("Content-Disposition: attachment; filename=PROFIND_inscritos_".date('Ymd').".csv");
			header("Pragma: no-cache");
			header("Expires: 0");

			print $linea;

			exit();
	}

    /**
     * Funcion publicaciones/resumenAjax.
     * Genera un resumen de una publicacion que se invoca dinamicamente.
	 *
     * Se invoca por AJAX desde resumenPublicacion() de func.js
     *
     * @param int $id ID de una publicación
     * @return Resumen_publicación Resumen de una publicacion en HTML
     */
	public function actionResumenAjax($id)
	{
		include('protected/widgets/widgetCuerpoPublicacion.php');
		global $bd;

		$id = intval($id);
		$publicacion = new Publicacion($id);
		widgetCuerpoPublicacion($publicacion);
		echo '<br><br><br>';

		exit;
	}

    /**
     * Funcion publicaciones/obtenerRemitenteAjax
     *
     * Devuelve el id de un remitente en la bbdd.
     * Si el remitente existe extrae el id, sino lo inserta en la bbdd y devuelve el nuevo id.
	 *
     * Se invoca por AJAX desde inscripcionConCV(), inscripcionConRedSocial(), soloMensaje() de vistaPublica.js
     *
     * @param string $email EMAIL del candidato
	 * @param string $nombre NOMBRE del candidato
	 * @param int $idPublicacion ID de la publicación
     * @return int ID del remitente - Confirmacion de insercion 
     */
	public function actionObtenerRemitenteAjax($email,$nombre,$idPublicacion=0)
	{ //obtiene el idRemitente y lo inserta si no estaba en la bbdd

		$remitente = new Remitente;
		$remitente->email=$email;
		$remitente->nombre=$nombre;
		if($remitente->existe()) {

			if($idPublicacion){
				$idCandidato=$remitente->insert_candidato()->id;
				if($inscripcion = Inscripcion::model()->findByAttributes(array(
					'id_candidato' => $idCandidato,
					'id_publicacion' => $idPublicacion,
				))){

					echo "1_";
				}
				else echo "0_";

			}
			else echo "0_";

		}
		else echo "0_";
		//devuelve el id del candidato
		echo $remitente->insert_candidato()->id;
		exit;
	}

    /**
     * Funcion publicaciones/obtenerSoloRemitenteAjax
     *
     * Devuelve el id de un remitente en la bbdd.
	 *
     * Se invoca por AJAX desde validarCampos() de vistaPublica.js
     *
     * @param string $email EMAIL del candidato
	 * @param string $nombre NOMBRE del candidato
     * @return int $id ID del candidato
     */
	public function actionObtenerSoloRemitenteAjax($email,$nombre)
	{

		$remitente = new Remitente;
		$remitente->email=$email;
		$remitente->nombre=$nombre;
		if($remitente->existe()) { //si ya existe el candidato, pintará 1_idRemitente
		echo "1_";

		$remitente = Remitente::model()->findByAttributes(array(
			'email' => $email
		));
		echo $remitente->id;
		}
		else {
			echo "0_"; //si no, solo 0_
		}
		exit;
	}

    /**
     * Funcion publicaciones/comprobarSubscripcionActiva.
     * Comprueba si la subscripcion del agente esta activa
     *
     * @return boolean True | False dependiendo del estado
     */
	private function comprobarSubscripcionActiva()
	{
		$subscripcion = Subscripcion::model()->findByPk(Yii::app()->user->subscripcion->id);
		if (calculaDias($subscripcion->fecha_fin, date('Y-m-d')) < 0)
		{
			return false;
		}
		return true;
	}

    /**
     * Funcion publicaciones/encontrarFuentesAjax.
     * Obtiene las fuentes desde las que se ha inscrito un candidato
     *
     * @param int $idCandidato ID del candidato
	 * @param int $idPublicacion ID de la publicación
     * @return string FUENTES Cadena con el valor de las fuentes
     */
	public function actionEncontrarFuentesAjax($idCandidato, $idPublicacion) {

		//obtenemos las fuentes de la tbl_candidatos_publicaciones
		 if($inscripcion = Inscripcion::model()->findByAttributes(array(
				'id_candidato' => $idCandidato,
				'id_publicacion' => $idPublicacion,
			))){
					$fuentes=$inscripcion->fuentes;
					echo $fuentes;

			}
			else{

				echo $fuentes='0000';
				}
		exit;
	}

    /**
     * Funcion publicaciones/eliminarCandidato.
     * Realiza un borrado logico de un candidato en la bbdd haciendo borrado=1
     *
     * @param int $idCandidato ID del candidato
	 * @param int $idPublicacion ID de la publicación
     */
	public function actionEliminarCandidato($idCandidato, $idPublicacion) {

		//obtenemos las fuentes de la tbl_candidatos_publicaciones
		if($inscripcion = Inscripcion::model()->findByAttributes(array(
				'id_candidato' => $idCandidato,
				'id_publicacion' => $idPublicacion,
		))){


		//Busca los mensajes de este Candidato referentes a esta Publicacion y los elimina
		/*$mensajes = Mensaje::model()->findAllByAttributes(array(
			'id_remitente' => $idCandidato,
			'id_publicacion' => $idPublicacion,
		));
		foreach($mensajes as $mensaje){

			$mensaje->delete();
		}*/

			/*if($mensajes = Mensaje::model()->findAllByAttributes(array(
				'id_candidato' => $idCandidato,
				'id_publicacion' => $idPublicacion,
			))){

			}*/
			//Se implementa borrado logico
			$inscripcion->borrado=1;
			$inscripcion->save();
		}

		exit;
	}

    /**
     * Funcion publicaciones/eliminarPublicacion.
     * Realiza un borrado logico de una publicacion en la bbdd, haciendo borrado=1, estado=C
     *
     * @param int $idPublicacion ID de la publicación
     */
	public function actionEliminarPublicacion($idPublicacion) {

		global $bd;
		// Al borrar la publicación, además la cerramos (necesario para fin de difusiones)
		$datos = array(
		'borrado' => 1,
		'estado' => 'C'
		);
		// Actualizamos la publicación en la base de datos
		$bd->modificar('tbl_publicaciones', $datos, 'id = ' . $idPublicacion);

		exit;
	}

    /**
     * Funcion publicaciones/getRedesSociales.
     * Obtiene las vinculaciones de un agente con sus redes sociales
     *
     * @param $reqVinculacion
     * @return Redes Array de redes sociales del agente
     */
	private function getRedesSociales($reqVinculacion = true)
	{
		global $bd;

		// Incluimos las redes sociales
		$sql = "SELECT * FROM tbl_redes_sociales";
		$bd->consulta($sql);

		// Añadimos todas las redes sociales al array de redes
		$redes = array();
		$filas = $bd->obtenertodo();
		for ($i = 0; $i < sizeof($filas); $i++)
		{
			// Incluimos la red social
			$nombreClase = $filas[$i]['nombre_clase'];
			$rutaClase = dirname(__FILE__).'/../classes/Network/RedesSociales/' . $nombreClase . '.php';
			include_once($rutaClase);

			// Inicializamos la clase
			eval('$redSocial = new ' . $nombreClase . '($filas[$i]["id"], Yii::app()->user->id, RedSocial::CTA_AGENTE, array());');

			// Añadimos la red social sólo si la tiene vinculada
			if ($redSocial->estaVinculado(RedSocial::CTA_AGENTE) || !$reqVinculacion)
			{
				$redes[] = $redSocial;
			}
		}

		// Devolvemos el array de redes sociales
		return $redes;
	}

    /**
     * Funcion publicaciones/directorioCandidatos
     *
     * Lista de candidatos relacionados con las publicaciones de un agente.
     * Permite invitar a un candidato a otras publicaciones
     *
     * Carga la vista themes/profind/publicaciones/directorioCandidatos.php
     *
     * @param string $estado (
	 * @param int $pagina
	 * @param string $filtro
	 * @param int $id
	 * @param int $idRecipiente
     * @return Listado Listado de candidatos
     */
	public function actionDirectorioCandidatos($estado = '', $pagina = 1, $filtro='',$id=-1, $idRecipiente=-1, $orden=" ORDER BY nombre ASC")
	{
		global $bd;

		//$elemPorPagina = $this->configuracion['elemPorPagina'];
		//en este caso queremos que sean 30
		$elemPorPagina = 30;
		// Opciones de paginación
		if ($pagina < 1)
		{
			$pagina = 1;
		}

		//Se tratan los filtros
		$strFiltros = '';
		if(isset($_GET['fNombre']) && !empty($_GET['fNombre'])) {
			$filtro .= (empty($filtro) ? '' : ' AND ').' (lower(nombre) LIKE "%'.strtolower($_GET['fNombre']).'%" OR lower(nombre) LIKE "%'.strtolower($_GET['fNombre']).'%")';
			$strFiltros .= '&fNombre='.$_GET['fNombre'];
		}
		if(isset($_GET['fTitulo']) && !empty($_GET['fTitulo'])) {
			$filtro .= (empty($filtro) ? '' : ' AND ').' lower(titulo) LIKE "%'.strtolower($_GET['fTitulo']).'%"';
			$strFiltros .= '&fTitulo='.$_GET['fTitulo'];
		}
		
		if(isset($_GET['comboPais']) && !empty($_GET['comboPais'])) {
			$consulta = "SELECT id FROM tbl_localidades_multiidioma WHERE id_pais =".$_GET['comboPais'];
			$resultado= $bd->consulta($consulta);

			//Cambiamos OR por AND para que no salgan más que los que cumplan todo
			$filtro .= (empty($filtro) ? '' : ' AND ').' (';
			//$filtro .= (empty($filtro) ? '' : ' OR ').' (';

			/*
			Se cambia:

			$filtro .= 'localidad = -1)';
			por
			$filtro .= 'localidad = -100)';

			para que no salgan los resultados en los que la localidad sea "no especificado"
			*/

			while ($datos = $bd->obtenerfila($resultado))
			{
			$filtro .= 'localidad = '.$datos['id'].' OR ';
			}
			$filtro .= 'localidad = -100)';

			$strFiltros .= '&comboPais='.$_GET['comboPais'];
		}
		if(isset($_GET['comboLocalidades']) && !empty($_GET['comboLocalidades'])) {
			$consulta = "SELECT id FROM tbl_localidades_multiidioma WHERE id =".$_GET['comboLocalidades'];
			$resultado= $bd->consulta($consulta);

			//Cambiamos OR por AND para que no salgan más que los que cumplan todo
			$filtro .= (empty($filtro) ? '' : ' AND ').' (';
			//$filtro .= (empty($filtro) ? '' : ' OR ').' (';

			/*
			Se cambia:

			$filtro .= 'localidad = -1)';
			por
			$filtro .= 'localidad = -100)';

			para que no salgan los resultados en los que la localidad sea "no especificado"
			*/

			while ($datos = $bd->obtenerfila($resultado))
			{
			$filtro .= 'localidad = '.$datos['id'].' OR ';
			}
			$filtro .= 'localidad = -100)';

			$strFiltros .= '&comboLocalidades='.$_GET['comboLocalidades'];
		}
			if(isset($_GET['comboAreas']) && !empty($_GET['comboAreas'])) {
			$consulta = "SELECT id FROM tbl_areas WHERE id =".$_GET['comboAreas'];
			$resultado= $bd->consulta($consulta);
			//$filtro .= (empty($filtro) ? '' : ' AND ').' ';
			if(isset($_GET['comboLocalidades']) && !empty($_GET['comboLocalidades']) or (isset($_GET['comboPais']) && !empty($_GET['comboPais'])))
			$filtro .= (empty($filtro) ? '' : ' AND ').' ';
			else
			//Cambiamos OR por AND para que no salgan más que los que cumplan todo
			$filtro .= (empty($filtro) ? '' : ' AND ').' ';
			//$filtro .= (empty($filtro) ? '' : ' OR ').' ';

			/*
			Se comenta lo siguiente para que no salgan los resultados en los que el área sea "no especificado"
			*/

			while ($datos = $bd->obtenerfila($resultado))
			{
			//$filtro .= 'area = '.$datos['id'].' OR ';
			$filtro .= 'area = '.$datos['id'];
			}
			//$filtro .= 'area = -1)';
			$strFiltros .= '&comboAreas='.$_GET['comboAreas'];
		}

		if(isset($_GET['fSalario']) && !empty($_GET['fSalario'])) {
			$filtro .= (empty($filtro) ? '' : ' AND ').' lower(salario) LIKE "%'.strtolower($_GET['fSalario']).'%"';
			$strFiltros .= '&fSalario='.$_GET['fSalario'];
		}
		/*
		if(isset($_GET['fTags']) && !empty($_GET['fTags'])) {
			$consulta = "SELECT id FROM tbl_tags WHERE lower(tag) LIKE '%".strtolower($_GET['fTags'])."%'";
			$resultado= $bd->consulta($consulta);
			$filtro .= (empty($filtro) ? '' : ' AND ').' (';
			while ($datos = $bd->obtenerfila($resultado))
			{
				$filtro .= 'tbl_publicaciones_tags.id_tag = '.$datos['id'].' OR ';
			}
			$filtro .= 'tbl_publicaciones_tags.id_tag = -1)';

			$strFiltros .= '&fTags='.$_GET['fTags'];
		}
		*/
		if(isset($_GET['etiqueta1']) && !empty($_GET['etiqueta1']) || isset($_GET['etiqueta2']) && !empty($_GET['etiqueta2']) || isset($_GET['etiqueta3']) && !empty($_GET['etiqueta3']) || isset($_GET['etiqueta4']) && !empty($_GET['etiqueta4']) || isset($_GET['etiqueta5']) && !empty($_GET['etiqueta5']) || isset($_GET['etiqueta6']) && !empty($_GET['etiqueta6'])) {
		$filtro .= (empty($filtro) ? '(' : ' AND (').'  tbl_publicaciones_tags.id_publicacion = -1';
		}
		for($i=1;$i<7;$i++) {
			if(isset($_GET['etiqueta'.$i]) && !empty($_GET['etiqueta'.$i])) {
				$consulta = "SELECT id_publicacion FROM tbl_publicaciones_tags, tbl_tags WHERE tbl_publicaciones_tags.id_tag=tbl_tags.id AND lower(tag) LIKE '%".strtolower($_GET['etiqueta'.$i])."%'";
				$resultado= $bd->consulta($consulta);

				$filtro .= (empty($filtro) ? '' : ' OR ').' ';
				//$filtro .= (empty($filtro) ? '' : ' OR ').' (';
				while ($datos = $bd->obtenerfila($resultado))
				{
				$filtro .= 'tbl_publicaciones_tags.id_publicacion = '.$datos['id_publicacion'].' OR ';
				}
				$filtro .= 'tbl_publicaciones_tags.id_publicacion = -1 ';

				$strFiltros .= '&etiqueta'.$i.'='.$_GET['etiqueta'.$i];
			}
		}
		if(isset($_GET['etiqueta1']) && !empty($_GET['etiqueta1']) || isset($_GET['etiqueta2']) && !empty($_GET['etiqueta2']) || isset($_GET['etiqueta3']) && !empty($_GET['etiqueta3']) || isset($_GET['etiqueta4']) && !empty($_GET['etiqueta4']) || isset($_GET['etiqueta5']) && !empty($_GET['etiqueta5']) || isset($_GET['etiqueta6']) && !empty($_GET['etiqueta6'])) {
			$filtro .= ' ) ';
		}
		if(isset($_GET['forden']) && !empty($_GET['forden'])) {
			if($_GET['forden']=="fechaAsc")
			$orden=" ORDER BY max(fecha) ASC";
			if($_GET['forden']=="fechaDesc")
			$orden=" ORDER BY max(fecha) DESC";
			$strFiltros .= '&forden='.$_GET['forden'];

		}
		
		// Obtenemos los agentes
		$id = intval($id);
		$publicacion = new Publicacion($id);

		$agente = Usuario::model()->findByPk(Yii::app()->user->id);
		$lista = new ListaInteresados($id, -1,"");
		$interesados=$lista->inscripciones;
		$fechaUltimoListado=$lista->fechaUltimaInscripcion;

		$listaCandidatos = new ListaCandidatos($filtro);
		//$listaPublicaciones = new ListaPublicaciones(-1, Publicacion::ESTADO_ABIERTA, '');
		//$total ="<span class=\"num_publ_gr\">". $listaPublicaciones->contarTodas()."</span> ".Yii::t('profind', 'Publicaciones activas y')." ";

		// Obtenemos el número de publicaciones y candidatos
		$total ="<span class=\"num_publ_gr\">". $listaCandidatos->contarTodos()."</span> ".Yii::t('profind', 'Candidatos.');
		$paginas = ceil($listaCandidatos->contar() / $elemPorPagina);
		$rep = $listaCandidatos->contar() ;
		// Generamos la paginación sólo si hay más de una página
		$agentes = array();
		$paginacion = "";
		if ($paginas > 1)
		{
			$paginacion = Yii::t('profind', 'Página').": ";
			for ($i = 1; $i <= $paginas; $i++)
			{
				$paginacion .= (($pagina == $i) ? '<b>' : '') . '<a style="'.(($pagina == $i) ? 'color:#900; font-size:1.2em;margin-right:2px' : 'margin-right:2px').'" href="index.php?r=publicaciones/directorioCandidatos&pagina=' . $i . $strFiltros. '">' . $i . '</a>' . (($pagina == $i) ? '</b>' : '') . ' ';
			}

			$inicio = ($pagina-1) * $elemPorPagina;

			// Obtenemos los candidatos a mostrar
			$candidatos = $listaCandidatos->getSubLista($inicio, $elemPorPagina, $orden);
		}
		else
		{
			// No hay paginación, cogemos todos...
			$candidatos = $listaCandidatos->getTodos($orden);
		}

		$invitac=new Invitacion();
		$invitacion=$invitac->getPublicacionesInvitacion();

		// Llamamos a la vista
		$this->render('directorioCandidatos', array(
			'candidatos' => $candidatos,
			'paginacion' => $paginacion,
			'total' => $total,
			'invitacion' => $invitacion,
			'publicacion' => $publicacion,
			'agente' => $agente,
			'interesados' => $interesados,
			'filtro' => $filtro,
			'id' => $id,
			'fechaUltimoListado' => $fechaUltimoListado,
			'rep'=>$rep
			)
		);
	}

}

