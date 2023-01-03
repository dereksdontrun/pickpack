<?php

include('herramientas.php');

//28/12/2022 Aquí procesaremos las ubicaciones

//si llegamos desde pickpackindex tras pasar por el login
if(isset($_GET['id_empleado'])){

    $id_empleado = $_GET['id_empleado'];

    if ($id_empleado){
        if ($nombre_empleado = obtenerEmpleado($id_empleado)) {
            //almaceno en una sesión el id y nombre del empleado para usarlo después 
            session_start();
            $_SESSION["id_empleado"] = $id_empleado;
            $_SESSION["nombre_empleado"] = $nombre_empleado;                     

            //con los datos del empleado en sesión, mostramos el formulario de búsqueda de producto, un input para ean con el cursor sobre el.            

            //localizaciones_log login
            // ubicacionLog('', 0, 0, '', '', '', 1);            

            require_once("../views/templates/buscaproducto.php");
        } else {
            //no se ha encontrado el nombre de usuario, queremos que vuelva de la pantalla de error al login              
            muestraErrorUbicaciones('No encuentro el usuario en el sistema');   
            return;     
        }
    } else {
        $token = Tools::getAdminTokenLite('AdminModules');
        $url_modulos = _MODULE_DIR_;
        
        $url = $url_modulos.'pickpack/controllers/admin/pickingpacking/pickpackindex.php?token='.$token;  
        
        header("Location: $url");
    }   


} elseif (isset($_POST['submit_ean'])) {
    //llegamos del formulario de buscar producto por ean
    if ($_POST['ean'] && $_POST['ean'] != '') {
        $ean = trim(pSQL($_POST['ean']));  
        
        if (strlen($ean) > 13) {
            //no se ha introducido nada en el formulario
            muestraErrorUbicaciones('El Ean introducido tiene longitud mayor de 13 (no es Ean)');  
            return;
        }

        //hacemos log del ean buscado
        // ubicacionLog('', 0, 0, $ean, '', '', 0, 0, 1);
        
        $busqueda = obtenerProducto($ean);
    } else {
        //no se ha introducido nada en el formulario
        muestraErrorUbicaciones('Debes introducir un Ean para buscar en el formulario');  
        return;        
    }    

    //obtenerProducto devuelve un array. El primer campo es "error". Si vale 1, en el segundo campo contendrá el mensaje de error, si vale 0 contendrá la info del producto. Si devuelve error, llamamos a error.php
    if ($busqueda[0]) {
        //enviamos la variable que contiene la descripción del error
        muestraErrorUbicaciones($busqueda[1]);    
        return;    
    } else {        
        //asignamos a la variable para la plantilla y la requerimos
        $producto = $busqueda[1]; 

        //guardamos log de lo mostrado una vez que se ha encontrado el producto
        ubicacionLog($producto['id'], $producto['id_product'], $producto['id_product_attribute'], $producto['ean'], $producto['localizacion'], $producto['reposicion'], 0, 0, 0, 1);

        require_once("../views/templates/muestraproducto.php");   
    }    

} elseif (isset($_POST['submit_producto_ok'])) {
    //o bien se ha pulsado el botón OK o se ha pulsado Return mediante el escaner de forma automática. Por defecto se "pulsa" el primer botón, en este caso OK. Leemos los valores de los inputs de localización y repo y los actualizamos en el producto.
    if ($_POST['id_producto']){
        //sacamos id_product e id_product_attribute
        $id_producto = explode("_", $_POST['id_producto']);
        $id_product = $id_producto[0];
        $id_product_attribute = $id_producto[1];

        //nos aseguramos de que el producto tiene entrada en tabla de localizaciones
        checkTablaLocalizaciones($id_product, $id_product_attribute);
    } else {
        muestraErrorUbicaciones('Error obteniendo datos del producto');    
        return;
    }
    
    if ($_POST['input_localizacion']){
        //se puede introducir algo o vaciar el input si queremos borrar la localización, así que admitimos vacío
        $localizacion = trim(pSQL($_POST['input_localizacion']));
    }

    if ($_POST['input_reposicion']){
        //se puede introducir algo o vaciar el input si queremos borrar la localización, así que admitimos vacío
        $reposicion = trim(pSQL($_POST['input_reposicion']));
        //como no le pasamos preg_match nos aseguramos de que no pasa de 64 caracteres que es lo que admite la BD
        if (strlen($reposicion) > 64) {
            muestraErrorUbicaciones('Error: ubicación de reposición demasiado larga (64 caracteres)');    
            return;
        }
    }

    //si en localización hay algo y no encaja con el regex mostramos error y no hacemos nada, si no, actualizamos ambas localizaciones a lo que venga en los inputs, o se vacia si no viene nada. Por ahora en reposición no hay norma así que no hacemos regex
    if (($localizacion) && !preg_match("/^[0-9a-zA-Z]{0,9}$/", $localizacion)){
        //$localizacion tiene algo pero no encaja en regex, lanzamos error
        muestraErrorUbicaciones('Error: el formato de la localización es incorrecto<br>Solo puede componerse de hasta 9 números y letras');    
        return;
    } else {        
        //hacemos log de lo contenido en los input una vez comprobado que los formatos son correctos
        ubicacionLog($_POST['id_producto'], $id_product, $id_product_attribute, '', $localizacion, $reposicion, 0, 0, 0, 0, 1);

        //$localizacion contiene algo y encaja en regex, lo metemos en location, o está vacio y por tanto lo vaciamos 
        if (actualizaLocalizaciones($id_product, $id_product_attribute, $localizacion, $reposicion)) {
            //localizaciones actualizadas, mostramos formulario para siguiente producto. Enviamos mensaje de OK            
            $mensaje_ok = 1;
            require_once("../views/templates/buscaproducto.php");
        } else {
            muestraErrorUbicaciones('Se produjo un error al actualizar las localizaciones');    
            return;
        }          
    }

} elseif (isset($_POST['submit_producto_incidencia'])) {
    //se ha producido algún tipo de problema, pej, ponía stock 10 y solo hay 2, o lo que sea. Insertamos el producto para poder mostrar luego los que han tenido problema y solucionarlo más comodamente con localizador o lo que sea.
    //meteremos en una tabla lafrips_localizaciones_log los ids del producto, marcando incidencia 1 y tendremos un check_incidencia a 0, que cuando se repasen las incidencias se pasará a 1 para ignorarla. Al introducir la incidencia se comprueba primero si ya existe una línea con incidencia a 1 y check incidencia a 0. Si existe no se introduce otra, si no se inserta nueva línea.
    $mensaje_incidencia = checkIncidencia($_POST['id_producto']);

    $incidencia = 1;   

    require_once("../views/templates/buscaproducto.php");

} elseif (isset($_POST['submit_volver'])) {
    //localizaciones_log  
    $id_producto = $_POST['id_producto'];   
    // si $id_producto = 0 es que venimos de la pantalla de error, por ejemplo por dar a buscar con el input vacío. No hacemos log ya que para errores lo hacemos en muestraErrorUbicaciones()
    //de moemtno no hacemos log
    // if ($id_producto) {
    //     $ids_producto = explode("_", $id_producto);
    //     $id_product = $ids_producto[0];
    //     $id_product_attribute = $ids_producto[1];

    //     ubicacionLog($id_producto, $id_product, $id_product_attribute, '', '', '', 0, 0, 0, 0, 0, 0, 1);
    // }  

    require_once("../views/templates/buscaproducto.php");
}


//función que recibe un ean o parte de el y busca el producto al que corresponde. Puede suceder que no encuentre producto correspondiente, que obtenga más de uno, ambos casos de error, o que encunetre uno solo, añadiendo el stock físico de almacén y su link a imagen y devolviendo los datos
function obtenerProducto($ean) {    

    $sql_producto = "SELECT CONCAT(pro.id_product, '_', IFNULL(pat.id_product_attribute, 0)) as id,
    IFNULL(pat.id_product, pro.id_product) AS id_product, 
    IF(ISNULL(pro.id_product), null, IFNULL(pat.id_product_attribute, 0)) AS id_product_attribute, #para evitar que ifnull devuelva siempre 0 como id atributo, con lo que siempre daría un resultado la sql, primero comprobamos si existe id_product 
    IFNULL(pat.reference, pro.reference) AS reference, 
    IFNULL(pat.ean13, pro.ean13) AS ean, pla.name AS product_name, pla.link_rewrite AS link_rewrite,
    IF(ISNULL(pro.id_product), null, IFNULL(GROUP_CONCAT(DISTINCT agl.name, ' - ', atl.name ORDER BY agl.name SEPARATOR ', '), '')) AS product_attributes,
    IFNULL(wpl.location, '') AS localizacion, IFNULL(loc.r_location, '') AS reposicion, 
    con.abc AS abc, 
    CASE
    WHEN con.abc = 'A' THEN 'danger'
    WHEN con.abc = 'B' THEN 'warning'
    ELSE 'success'
    END AS badge,
    con.consumo AS consumo
    FROM lafrips_stock_available ava
    JOIN lafrips_product pro ON pro.id_product = ava.id_product
    JOIN lafrips_product_lang pla ON pro.id_product = pla.id_product AND pla.id_lang = 1
    LEFT JOIN lafrips_product_attribute pat ON pat.id_product = ava.id_product AND pat.id_product_attribute = ava.id_product_attribute
    LEFT JOIN lafrips_warehouse_product_location wpl ON wpl.id_product = ava.id_product AND wpl.id_product_attribute = ava.id_product_attribute
        AND wpl.id_warehouse = 1
    LEFT JOIN lafrips_localizaciones loc ON loc.id_product = ava.id_product AND loc.id_product_attribute = ava.id_product_attribute
    LEFT JOIN lafrips_product_attribute_combination pac ON pac.id_product_attribute = pat.id_product_attribute
    LEFT JOIN lafrips_attribute att ON att.id_attribute = pac.id_attribute
    LEFT JOIN lafrips_attribute_lang atl ON atl.id_attribute = pac.id_attribute AND atl.id_lang = 1
    LEFT JOIN lafrips_attribute_group_lang agl ON agl.id_attribute_group = att.id_attribute_group AND agl.id_lang = 1
    LEFT JOIN lafrips_consumos con ON con.id_product = ava.id_product AND con.id_product_attribute = ava.id_product_attribute
    WHERE (pro.ean13 LIKE '%$ean%' OR pat.ean13 LIKE '%$ean%')
    GROUP BY pro.id_product, ava.id_product_attribute";

    $producto = Db::getInstance()->ExecuteS($sql_producto);

    if (empty($producto) || is_null($producto)) {
        return array(1, 'No pude encontrar ningún producto con ese Ean');
    } elseif (count($producto) > 1) {
        //dependiendo del número de productos encontrados mostramos los nombres o no. Si son más de 8 solo mensaje, si no una lista de nombres
        if (count($producto) > 10) {
            return array(1, 'El Ean introducido corresponde a un número muy elevado de productos');
        } else {
            $mensaje = 'El Ean introducido corresponde a más de un producto:<br>';
            foreach ($producto AS $prod) {
                // usamos if ternario, si tiene atributos añadimos y si no no
                $mensaje .= $prod['product_attributes'] != '' ? $prod['product_name'].'<br>'.$prod['product_attributes'].'<br>' : $prod['product_name'].'<br>';
            }

            return array(1, $mensaje);
        }        
        
    } else {
        //devuelve un producto y solo uno. Obtenemos su stock físico y devolvemos todos los datos
        $stock_manager = new StockManager();

        //el foreach solo se va a recorrer una vez, de modo que hacemos el return dentro devolviendo $prod
        foreach ($producto as &$prod) {
            $prod['stock_fisico'] = (int) $stock_manager->getProductPhysicalQuantities($prod['id_product'], $prod['id_product_attribute'], 1,true);
            			
            $image = Image::getCover((int)$prod['id_product']);			
            $link = new Link;
            $prod['image_link'] = 'http://'.$link->getImageLink($prod['link_rewrite'], $image['id_image'], 'home_default');

            return array(0, $prod);
        }       
        
    }
}

//función que comprueba si la combinación de id_product e id_product_attribute ya tiene entrada en lafrips_localizaciones. Si es nuevo se hace un insert para poder almacenar después las localizaciones mediante updates
function checkTablaLocalizaciones($id_product, $id_product_attribute) {
    //comprobar si el producto está en la tabla localizaciones, si no está (el resultado de buscarlo en la tabla es empty()), hacer un insert
    $sql_existe = "SELECT id_product, id_product_attribute FROM lafrips_localizaciones WHERE id_product = $id_product AND id_product_attribute = $id_product_attribute";

    $existe = Db::getInstance()->ExecuteS($sql_existe); 

    if(empty($existe)){
        $sql_insert_producto = "INSERT INTO lafrips_localizaciones(id_product, id_product_attribute, date_add) 
        VALUES ($id_product, $id_product_attribute,  NOW())";

        Db::getInstance()->ExecuteS($sql_insert_producto);
    }

    return;
}

//función que comprueba si ya existe una incidencia marcada para el producto en lafrips_localizaiones_log y si está o no marcada como finalizada (check_incidencia). Si no existe hace insert, si existe y tiene check_incidencia hace insert. Si existe y no tiene check no hace nada. Devuelve mensaje si no existía incidencia abierta y si existe si ya hay una, para mostrar mensaje en buscaproducto
function checkIncidencia($id_producto) {
    $ids_producto = explode("_", $id_producto);
    $id_product = $ids_producto[0];
    $id_product_attribute = $ids_producto[1];

    //comprobamos si existe incidencia abierta para el producto
    $sql_existe = "SELECT id_localizaciones_log FROM lafrips_localizaciones_log WHERE id_product = $id_product AND id_product_attribute = $id_product_attribute AND incidencia = 1 AND check_incidencia = 0";

    $existe = Db::getInstance()->ExecuteS($sql_existe); 

    if(empty($existe)){
        ubicacionLog($id_producto, $id_product, $id_product_attribute, '', '', '', 0, 0, 0, 0, 0, 1);

        return '¡Incidencia almacenada!';
    }

    return '¡Ya tenía incidencia abierta!';
}


//función que recibe los datos de producto e inputs y actualiza tanto loclaización de Prestashop como de tabla lafrips_localizaciones. 
//vamos a comparar primero la localización actual, si varía localizacion pero no repo actualizamos ambas, si solo varía repo actualizamos tabla localizaciones
function actualizaLocalizaciones($id_product, $id_product_attribute, $localizacion, $reposicion) {
    //sacamos location actual en almacén online 1
    // $localizacion_actual = WarehouseCore::getProductLocation($id_product, $id_product_attribute, 1);

    $actual = Db::getInstance()->getRow("SELECT p_location, r_location FROM lafrips_localizaciones WHERE id_product = $id_product AND id_product_attribute = $id_product_attribute");

    $localizacion_actual = $actual['p_location'];
    
    $reposicion_actual = $actual['r_location'];

    //hacemos update si alguna es diferente
    if (($localizacion != $localizacion_actual) || ($reposicion != $reposicion_actual)) {
        //actualizamos en Prestashop para almacén 1 online
        WarehouseCore::setProductLocation($id_product, $id_product_attribute, 1, $localizacion);

        //después de introducir la localización en lafrips_products, hacerlo en tabla auxiliar lafrips_localizaciones
        $sql_update_localizacion = "UPDATE lafrips_localizaciones 
        SET 
        date_upd = NOW(), 
        p_location = '$localizacion',
        r_location = '$reposicion' 
        WHERE id_product = $id_product 
        AND id_product_attribute = $id_product_attribute";

        if (Db::getInstance()->ExecuteS($sql_update_localizacion)) {
            return true;
        }

        return false;
    }

    return true;    
}


//función que llama a la página de error. La separo de la de pickpack ya que el log lleva a otra tabla, pero la plantilla de error.php la usamos
function muestraErrorUbicaciones($mensaje_error, $id_producto = '', $id_product = 0, $id_product_attribute = 0, $ean = '') {
    //localizaciones_log               
    ubicacionLog($id_producto, $id_product, $id_product_attribute, $ean, '', '', 0, 0, 0, 0, 0, 0, 0, $mensaje_error);

    //enviamos variable action a error.php porque lo necesita para el formulario de botón volver, a ubicaciones.php
    $action = 'ubicaciones';

    require_once("../views/templates/error.php");
}

//función log de ubicaciones
function ubicacionLog($id_producto = '', $id_product = 0, $id_product_attribute = 0, $ean = '', $localizacion = '', $reposicion = '', $login = 0, $cerrar = 0, $buscar_ean = 0, $mostrar_ubicacion = 0, $submit_ok = 0, $incidencia = 0, $cancelar = 0, $mensaje_error = '') {
    //$id_empleado y $nombre_empleado los sacamos de $_SESSION
    $id_empleado = $_SESSION['id_empleado'];
    $nombre_empleado = $_SESSION['nombre_empleado'];       
  
    $sql_insert_localizaciones_log = "INSERT INTO lafrips_localizaciones_log
    (id_producto,
    id_product,
    id_product_attribute,
    ean,
    localizacion,
    reposicion,
    `login`,
    cerrar,
    id_employee,
    nombre_employee,
    buscar_ean,
    mostrar_ubicacion,
    submit_ok,
    incidencia,
    cancelar,
    mensaje_error,
    date_add)
    VALUES
    ('$id_producto',
    $id_product,
    $id_product_attribute,
    '$ean',
    '$localizacion',
    '$reposicion',
    $login,
    $cerrar,    
    $id_empleado,
    '$nombre_empleado', 
    $buscar_ean,
    $mostrar_ubicacion,
    $submit_ok,
    $incidencia,
    $cancelar,    
    '$mensaje_error',
    NOW())";
  
    Db::getInstance()->Execute($sql_insert_localizaciones_log);
  
    return;
  
  }


?>