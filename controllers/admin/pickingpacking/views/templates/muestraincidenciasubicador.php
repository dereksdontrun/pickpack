<?php
require_once(dirname(__FILE__).'/../../../../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../../../../init.php');

//https://lafrikileria.com/modules/pickpack/controllers/admin/pickingpacking/views/templates/muestraincidenciasubicador.php
//https://lafrikileria.com/test/modules/pickpack/controllers/admin/pickingpacking/views/templates/muestraincidenciasubicador.php

//este script se alamcena con el módulo de pickpack y el ubicador pero se ejecutará mediante su url desde acceso rápido.
//sirve para revisar los producto sque  se hayan marcado como incidencia durante el uso del ubicador. Muestra los datos de los productos y la opción de quitar la marca de incidencia, poneindo ese campo a 0 en la tabla lafrips_localizaciones_log

//utilizamos la tarea descatalogados_con_stock.php para mostrar lso productos de forma similar


// $context = Context::getContext();
// //  var_dump($context);
//  var_dump($context->cookie);

//sacamos la cookie. Con ella sabremos si es un usuario logado y después generamos el token para dicho empleado y adminproducts
$cookie = new Cookie('psAdmin', '', (int)Configuration::get('PS_COOKIE_LIFETIME_BO'));
// echo '<pre>';
// var_dump($cookie);
// echo '</pre>';


//si no venimos de un usuario logado en backoffice no continuamos
if (!empty($cookie->id_employee) && $cookie->id_employee) {

	//COMPROBAR este isset como condición
	if(isset($_POST['quitar_incidencia'])) {
		//entramos tras pulsar Quitar incidencia en un producto, sacamos el id que viene como value en el button de tipo submit y ponemos a 0 incidencia en lafrips_localizaciones_log para ese producto. El id es id_product_id_product_attribute
		//var_dump($_POST);
		$producto = explode("_", $_POST['quitar_incidencia']);

		$id_product = $producto[0];
		$id_product_attribute = $producto[1];

		//comprobamos si existe incidencia abierta para el producto por si estuvieran revisando en dos navegadores al mismo tiempo y se ha cerrado estando este abierto
		$sql_existe = "SELECT id_localizaciones_log FROM lafrips_localizaciones_log WHERE id_product = $id_product AND id_product_attribute = $id_product_attribute AND incidencia = 1 AND check_incidencia = 0";

		$id_localizaciones_log = Db::getInstance()->getValue($sql_existe);

		if($id_localizaciones_log) {
			//hacemos update a check_incidencia, empleado y fecha
			$id_employee = $cookie->id_employee;
			$nombre_employee = Db::getInstance()->getValue("SELECT CONCAT(firstname,' ',lastname) FROM lafrips_employee WHERE id_employee = $id_employee");

			$sql_update_localizacion_log = "UPDATE lafrips_localizaciones_log 
			SET 
			check_incidencia = 1,
			check_incidencia_id_employee = $id_employee,
			check_incidencia_nombre_employee = '$nombre_employee',
			check_incidencia_date = NOW(),
			date_upd = NOW()
			WHERE id_localizaciones_log = $id_localizaciones_log";

			Db::getInstance()->ExecuteS($sql_update_localizacion_log);
			
		}

		//recargamos la página
		$url_base = Tools::getHttpHost(true).__PS_BASE_URI__;
		$location = 'modules/pickpack/controllers/admin/pickingpacking/views/templates/muestraincidenciasubicador.php';
		header("Location: ".$url_base.$location);	

	} else {

		echo '<head><style>
		table {
		font-family: arial, sans-serif;
		border-collapse: collapse;
		width: 100%;
		}

		td, th {
		border: 1px solid #dddddd;  
		padding: 8px;
		}
		</style>
		<title>Incidencias Ubicador</title>
		<link rel="icon" type="image/vnd.microsoft.icon" href="/img/favicon.ico" />
		</head><body>';

		echo '<form method="post">';

		echo '<table>';
		//ponemos caption al final de la tabla para añadir el número de productos(no importa ponerlo ahí)

		//27/01/2023 Sacamos la info típica del producto, cuando se marcó incidencia y quién y sus stocks
		//también sacamos la fecha y características del último movimiento de stock positivo (esto en una consulta más abajo por cada producto)
		// ?token=536d092f9e1de3e6f3aa9a4ed7b45d80

		$sql_productos_incidencia_ubicador = "SELECT CONCAT('https://lafrikileria.com/', ima.id_image, '-small_default/', lol.id_product,'.jpg') AS url_imagen,
		CONCAT(lol.id_product,'_',lol.id_product_attribute) AS producto,
		lol.id_product AS id_product, lol.id_product_attribute AS id_product_attribute, IFNULL(pat.reference, pro.reference) AS referencia,
		pla.name AS nombre, 
		cla.name AS categoria_principal,		
		ava.quantity AS disponible , pro.active AS activo, 
		IFNULL(pat.ean13, pro.ean13) AS ean13, wpl.location AS localizacion,
		IFNULL((SELECT SUM(physical_quantity) FROM lafrips_stock WHERE id_product = ava.id_product AND id_product_attribute = IFNULL( ava.id_product_attribute, 0) AND id_warehouse = 1), 0)  AS stock_online,
		IFNULL((SELECT SUM(physical_quantity) FROM lafrips_stock WHERE id_product = ava.id_product AND id_product_attribute = IFNULL( ava.id_product_attribute, 0) AND id_warehouse = 4), 0) AS stock_tienda,  
		IFNULL(loc.r_location, '') AS loc_repo, 
		CASE
		WHEN ava.out_of_stock = 1 THEN 1
		ELSE 0
		END
		AS permitir_pedido,
		lol.nombre_employee AS empleado_incidencia, 
		DATE_FORMAT(lol.date_add, '%d-%m-%Y %H:%i:%s') AS fecha_incidencia
		FROM lafrips_localizaciones_log lol 
		JOIN lafrips_stock_available ava ON ava.id_product = lol.id_product AND ava.id_product_attribute = lol.id_product_attribute
		JOIN lafrips_product pro ON pro.id_product = lol.id_product
		LEFT JOIN lafrips_product_attribute pat ON pat.id_product = lol.id_product AND pat.id_product_attribute = lol.id_product_attribute
		JOIN lafrips_product_lang pla ON pro.id_product = pla.id_product 
			AND pla.id_lang = 1
		LEFT JOIN lafrips_warehouse_product_location wpl ON wpl.id_product = lol.id_product AND wpl.id_product_attribute = lol.id_product_attribute
			AND wpl.id_warehouse = 1
		LEFT JOIN lafrips_localizaciones loc ON loc.id_product = lol.id_product AND loc.id_product_attribute = lol.id_product_attribute		
		LEFT JOIN lafrips_category_lang cla ON cla.id_category = pro.id_category_default 
			AND cla.id_lang = 1
		LEFT JOIN lafrips_image ima ON ima.id_product = lol.id_product AND ima.cover = 1
		WHERE lol.incidencia = 1 
		AND lol.check_incidencia = 0";

		$num_productos = 0;

		$token = Tools::getAdminTokenLite('AdminModules');

		if ($productos = Db::getInstance()->ExecuteS($sql_productos_incidencia_ubicador)){
			//inicializamos el html de cada producto para pintarlo
			$productos_mostrar = '';	

			foreach ($productos as $producto){
				$url_imagen = $producto['url_imagen'];
				$el_producto = $producto['producto'];
				$id_product = $producto['id_product'];
				$id_product_attribute = $producto['id_product_attribute'];
				$referencia = $producto['referencia'];
				$nombre = $producto['nombre'];
				$categoria_principal = $producto['categoria_principal'];					
				$ud_disponible = $producto['disponible'];
				$activo = $producto['activo'];
				$ean13 = $producto['ean13'];
				$localizacion = $producto['localizacion'];
				$loc_repo = $producto['loc_repo'];
				$stock_online = $producto['stock_online'];
				$stock_tienda = $producto['stock_tienda'];
				$permitir_pedido = $producto['permitir_pedido'];
				$empleado_incidencia = $producto['empleado_incidencia'];	
				$fecha_incidencia = $producto['fecha_incidencia'];	

				if (is_null($url_imagen) || empty($url_imagen)) {
					$url_imagen = 'https://lafrikileria.com/img/logo_producto_medium_default.jpg';
				}

				if (is_null($categoria_principal) || empty($categoria_principal)) {
					$categoria_principal = 'NO DISPONIBLE';
				}								

				if (is_null($localizacion) || empty($localizacion)) {
					$localizacion = 'NO DISPONIBLE';
				}

				if (is_null($loc_repo) || empty($loc_repo)) {
					$loc_repo = 'NO DISPONIBLE';
				}

				if (is_null($stock_online) || empty($stock_online)) {
					$stock_online = 0;
				}

				if (is_null($stock_tienda) || empty($stock_tienda)) {
					$stock_tienda = 0;
				}

				if ($activo) {
					$activo = '<span style="font-weight:bold; color:green;">SI</span>';
				} else {
					$activo = '<span style="font-weight:bold; color:red;">NO</span>';
				}

				if ($permitir_pedido == 1) {
					$permitir = '<span style="font-weight:bold; color:green;">SI</span>';
				} else {
					$permitir = '<span style="font-weight:bold; color:red;">NO</span>';
				}

				$info_incidencia = $fecha_incidencia.'<br><br>'.$empleado_incidencia;

				//sacamos el último movimiento de suma de stock del producto
				$sql_ultimo_movimiento = "SELECT smrl.name AS movimiento, smv.physical_quantity AS cantidad, war.name AS almacen,
					DATE_FORMAT(smv.date_add, '%d-%m-%Y %H:%i:%s') AS fecha, smv.employee_firstname AS empleado
				FROM lafrips_stock_mvt smv
				JOIN lafrips_stock sto ON smv.id_stock = sto.id_stock
				JOIN lafrips_stock_mvt_reason_lang smrl ON smrl.id_stock_mvt_reason = smv.id_stock_mvt_reason AND smrl.id_lang = 1
				JOIN lafrips_warehouse war ON sto.id_warehouse = war.id_warehouse
				WHERE smv.sign = 1
				AND sto.id_product = ".$id_product."
				AND sto.id_product_attribute = ".$id_product_attribute."
				ORDER BY smv.date_add DESC LIMIT 1";

				if ($ultimo_movimiento = Db::getInstance()->ExecuteS($sql_ultimo_movimiento)){
					$movimiento = $ultimo_movimiento[0]['movimiento'];
					$cantidad = $ultimo_movimiento[0]['cantidad'];
					$almacen = $ultimo_movimiento[0]['almacen'];
					$fecha = $ultimo_movimiento[0]['fecha'];
					$empleado = $ultimo_movimiento[0]['empleado'];

					$ultimo_movimiento = $fecha.'<br>'.$movimiento.'<br>'.$cantidad.' ud.<br>'.'Almacén '.$almacen.'<br>'.$empleado;

				} else {
					$ultimo_movimiento = 'NO DISPONIBLE';
				}

				//generamos url para llevar al backoffice del producto
				//$token_adminproducts = Tools::getAdminTokenLite('AdminProducts');

				//sacamos el token para el empleado desde la cookie de arriba
				
				//esta es la forma de generar el token si como parece, aquí no hay context así que sacamos el id_employee del token. Es lo que devuelve la función de Tools.php, esta que muestro aquí debajo
				// public static function getAdminTokenLite($tab, Context $context = null)
				// {
				// 	if (!$context) {
				// 		$context = Context::getContext();
				// 	}

				// 	return Tools::getAdminToken($tab . (int) Tab::getIdFromClassName($tab) . (int) $context->employee->id);
				// }
				$tab = 'AdminProducts';
				$token_adminproducts = Tools::getAdminToken($tab . (int) Tab::getIdFromClassName($tab) . (int) $cookie->id_employee);

				//COMPROBAR TOKEN - no  me sale
				// $token_adminproducts = '536d092f9e1de3e6f3aa9a4ed7b45d80';
				$url_base = Tools::getHttpHost(true).__PS_BASE_URI__;
				$url_producto_back = $url_base.'lfadminia/index.php?controller=AdminProducts&id_product='.$id_product.'&updateproduct&token='.$token_adminproducts;
			
				//preparamos botón de Quitar incidencia. Como se tienen en cuenta atributos, le ponemos como value la combinación de id_product e id_product_attribute
				$quitar_incidencia_producto = '<button type="submit" name="quitar_incidencia" value="'.$el_producto.'" style="border: none;
				color: white;
				padding: 10px 20px;
				text-align: center;
				text-decoration: none;
				display: inline-block;
				font-size: 16px;
				margin: 4px 2px;
				cursor: pointer;
				background-color: #512E8C;">Quitar Incidencia</button>';	

				$num_productos++;		

				$productos_mostrar .= '<tr style="background-color: #512E8C;"><th colspan="14" style="text-align:center; color: #FFFFFF;"><span style="font-size: 200%; padding-right:20px;" title="'.$el_producto.'"><strong>'.$referencia.'</strong></span>    <button style="border: none;
				color: white;
				padding: 10px 20px;
				text-align: center;
				text-decoration: none;
				display: inline-block;
				font-size: 16px;
				margin: 4px 2px;
				cursor: pointer;
				background-color: #4CAF50;"><a href="'.$url_producto_back.'" target="_blank" title="Ver Producto" style="color: white; text-decoration: none;">Ver Producto</a></button>
				</th></tr>';

				$productos_mostrar .= '<tr style="background-color: #00B50D;"><td>Quitar Incidencia</td><td>Imagen</td><td>Nombre</td><td>Ean</td><td>Categoría Principal</td><td>Fecha Incidencia</td><td>Último Movimiento Positivo</td><td title="Permite pedidos sin stock">Permite<br>Pedidos</td><td>Ud. Disponibles</td><td>Localización</td><td>Stock<br>Online</td><td>Stock<br>Tienda</td><td>Reposición</td><td>Activo</td></tr>';

				$productos_mostrar .= '<tr><td>'.$quitar_incidencia_producto.'</td><td><img style="height: 100px;" src="'.$url_imagen.'" /></td><td>'.$nombre.'</td><td>'.$ean13.'</td><td>'.$categoria_principal.'</td><td style="text-align:center;">'.$info_incidencia.'</td><td style="text-align:center;">'.$ultimo_movimiento.'</td><td>'.$permitir.'</td><td style="text-align:center; font-size: 16px; font-weight: bold;">'.$ud_disponible.'</td><td>'.$localizacion.'</td><td style="text-align:center;">'.$stock_online.'</td><td style="text-align:center;">'.$stock_tienda.'</td><td>'.$loc_repo.'</td><td>'.$activo.'</td></tr>';
			}
		}	

				

		echo $productos_mostrar;		
					
					
		if ($num_productos == 0){
			echo '<tr style="background-color: #00B50D;"><th colspan="14" style="text-align:center; color: #FFFFFF;"><h1>¡¡No hay productos con incidencia de ubicación!!</h1></th></tr>';
			//echo '<tr><td colspan="12" style="text-align:center;"><img src="https://lafrikileria.com/img/rufi.gif" /></td></tr>';
		}
		$num_productos = '- '.$num_productos;
		echo '<caption><h1>Productos con Incidencias Ubicador a revisar '.$num_productos.'</h1></caption>';
		echo '</table>
				</form>
				</body>';
	}
} else {
    // Si no ha hecho login, le decimos que vaya a hacer login
    echo 'Por favor, inicia sesión en PrestaShop.';
}




?>


