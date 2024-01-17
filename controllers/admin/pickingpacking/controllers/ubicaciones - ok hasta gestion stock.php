<?php

include('herramientas.php');

//28/12/2022 Aquí procesaremos las ubicaciones

//si llegamos desde pickpackindex tras pasar por el login, existirá el parámetro GET en la url con el id de empleado. Solo debe estar si venimos desde login, ya que $_SESSION["funcionalidad"] solo debe ponerse una vez
//11/07/2023 Vamos a utilizar este controlador para ubicaciones y recepciones. Recepciones será una versión ampliada de ubicaciones. Además de ubicar, tendrá un input de cantidad recibida y un select para el pedido de materiales al que corrsponde. Llegamos aquí desde login, con un GET con el id de usuario, y otro GET funcionalidad, que indica si hacemos rececpión o solo ubicación, lo que creará la sesión con una funcionalidad u otra, que es lo que mostrará unos inputs y colores u otros.

if (isset($_GET['id_empleado']) && isset($_GET['funcionalidad'])){

    $id_empleado = $_GET['id_empleado'];
    $funcionalidad = $_GET['funcionalidad'];

    if ($id_empleado && $funcionalidad){
        if ($nombre_empleado = obtenerEmpleado($id_empleado)) {
            //almaceno en una sesión el id y nombre del empleado para usarlo después 
            session_start();
            $_SESSION["id_empleado"] = $id_empleado;
            $_SESSION["nombre_empleado"] = $nombre_empleado;  
            $_SESSION["funcionalidad"] = $funcionalidad;       
            //12/09/2023 creamos una variable de sesión id_pedido_materiales vacia. Cuando estemos en recepciones, el select de pedido de materiales se muestra por defecto con "Selecciona Pedido", cuando se seleccione uno y se pulse ok, su id_supply_order entrará en la variable de sesión, y cada vez que se busque un producto se seleccionará el pedido de materialles en el select cuyo id_supply_order coincida con el de sesión. Si se cambia de pedido se actualizará. De este modo solo habría que modificar el select la primera vez que escaneas un producto del pedido suponiendo que se escanea por pedidos y no los mezclan. Cada vez que se muestre un producto se mira la variable de sesión, si está vacía el select se pone por defecto en Selecciona, si no lo está se pone en el pedido cuyo id coincida, y no coincide ninguno, se pone en Selecciona
            $_SESSION["id_pedido_materiales"] = 0;                    

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


} elseif (!isset($_SESSION['id_empleado']) || empty($_SESSION['id_empleado']) 
        || !isset($_SESSION['nombre_empleado']) || empty($_SESSION['nombre_empleado']) 
        || !isset($_SESSION['funcionalidad']) || empty($_SESSION['funcionalidad'])){  //entramos al controlador, o bien desde login con GET de empleado y funcionalidad, o con sesión abierta. Si no hay GET ni sesión, volvemos a login

            $token = Tools::getAdminTokenLite('AdminModules');
            $url_modulos = _MODULE_DIR_;
            
            $url = $url_modulos.'pickpack/controllers/admin/pickingpacking/pickpackindex.php?token='.$token;  
            
            header("Location: $url");
}

//en este punto estamos trabajando con ubicaciones o recepciones+ubicaciones y tenemos una sesión abierta
if (isset($_POST['submit_ean'])) {
    //llegamos del formulario de buscar producto por ean
    if ($_POST['ean'] && $_POST['ean'] != '') {
        $ean = trim(pSQL($_POST['ean']));  
        // var_dump($_POST);
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

        //si estamos en Recepciones, buscamos el pedido de materiales donde se encuentra el producto, llamando a la función obtenerPedidoMateriales()
        $log_recepciones = 0;
        if ($_SESSION["funcionalidad"] == 'recepciones') {
            $log_recepciones = 1;

            $busqueda_recepcion = obtenerPedidoMateriales($ean);

            //obtenerPedidoMateriales devuelve un array. El primer campo es "error". Si vale 1, en el segundo campo contendrá el mensaje de error, si vale 0 contendrá la info del/los pedidos de materiales. Si devuelve error, probablemente que no encuentra el ean en ningún pedido de materiales en los estados correctos, llamamos a error.php, interrumpiendo tanto la ubicación como la recepción
            if ($busqueda_recepcion[0]) {
                //enviamos la variable que contiene la descripción del error
                muestraErrorUbicaciones($busqueda_recepcion[1]);    
                return;    
            } else {        
                //añadimos a la variable para la plantilla
                $producto['pedidos_materiales'] = $busqueda_recepcion[1]; 
            }
        }

        //guardamos log de lo mostrado una vez que se ha encontrado el producto. Lo ponemos aquí de modo que si estamos en recepciones y no se encuentra el producto en pedido de materiales no guardaríamos los datos al no llegar a mostrarlo.
        ubicacionLog($producto['id'], $producto['id_product'], $producto['id_product_attribute'], $producto['ean'], $producto['localizacion'], $producto['reposicion'], 0, 0, 0, 1, 0, 0, 0, '', $log_recepciones);

        require_once("../views/templates/muestraproducto.php");   
    }    

} elseif (isset($_POST['submit_producto_ok'])) {
    // var_dump($_POST);
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
        //comprobados los parámetros referidos a localización, son correctos, miramos si sesión nos indica que estamos haciendo rececpción y si es así sacamos también los datos para ello
        //hacemos log de lo contenido en los input una vez comprobado que los formatos son correctos. El retorno lo metemos en id_localizaciones_log. Si estamos en recepciones lo guardaremos en tabla recepciones
        $log_recepciones = 0;
        if ($_SESSION["funcionalidad"] == 'recepciones') {
            $log_recepciones = 1;
        }

        $id_localizaciones_log = ubicacionLog($_POST['id_producto'], $id_product, $id_product_attribute, '', $localizacion, $reposicion, 0, 0, 0, 0, 1, 0, 0, '', $log_recepciones);

        //$localizacion contiene algo y encaja en regex, lo metemos en location, o está vacio y por tanto lo vaciamos 
        if (actualizaLocalizaciones($id_product, $id_product_attribute, $localizacion, $reposicion)) {
            //localizaciones actualizadas, mostramos formulario para siguiente producto. Enviamos mensaje de OK            
            $mensaje_ok_ubicacion = 1;

            //con la ubicación gestionada pasamos a almacenar la información de recepción antes de continuar.
            if ($_SESSION["funcionalidad"] == 'recepciones') {
                if ($_POST['input_unidades_esperadas'] && !empty($_POST['input_unidades_esperadas']) && $_POST['select_pedido_materiales'] && !empty($_POST['select_pedido_materiales'])){
                    //en input_unidades_esperadas tenemos lo introducido en el input como recibido, en select_pedido_materiales tenemos idpedidomateriales_cantidadesperadaenpedido_unidadesyarecibidas.
                    //unidades ya recibidas es la suma de quantity_received en el pedido de materiales para el producto, si se ha recibido algo ya, más las que ya estén en la tabla recepciones para ese pedido y producto, con finalizado = 0, es decir, que aún no se han sumado al pedido de materiales. Todos estos datos los metemos en la tabla recepciones. Si la suma de input_unidades_esperadas y unidadesyarecibidas es superior a cantidadesperadaenpedido, pondremos un warning en la tabla.
                    $info_select = explode("_", $_POST['select_pedido_materiales']);
                    $id_supply_order = $info_select[0];

                    //12/09/2023 Metemos el id_supply_order del select en la variable de sesión que se usa para que si ya han escogido un pedido la siguiente vez les salga seleccionado el mismo pedido si el nuevo producto está en dicho pedido. Si la variable ya contiene un id y el pedido es el mismo se sobreescribe y la variable de sesión no cambia, así nos aseguramos.
                    $_SESSION["id_pedido_materiales"] = $id_supply_order; 

                    $quantity_expected = $info_select[1];
                    $unidades_ya_recibidas = $info_select[2];

                    $unidades_recibidas = (int)$_POST['input_unidades_esperadas'];

                    if (!is_int($unidades_recibidas) || ($unidades_recibidas < 1)) {
                        muestraErrorUbicaciones('Las unidades recibidas tienen que ser un número entero positivo. Ubicación procesada correctamente');
                        // muestraErrorUbicaciones('Las unidades recibidas tienen que ser un número entero positivo ('.$unidades_recibidas.' - '.gettype($unidades_recibidas).'). Ubicación procesada correctamente');    
                        return;
                    }

                    $warning = (($unidades_recibidas + $unidades_ya_recibidas) > $quantity_expected) ? 1 : 0;                    

                    if (almacenaRecepciones($id_localizaciones_log, $id_product, $id_product_attribute, $id_supply_order, $quantity_expected, $unidades_recibidas, $unidades_ya_recibidas, $warning)) {
                        if ($warning) {
                            $mensaje_warning_recepcion = 1;
                        } else {
                            $mensaje_ok_recepcion = 1;
                        }
                        
                    } else {
                        muestraErrorUbicaciones('Se produjo un error al almacenar la recepción de materiales pero la ubicación se procesó correctamente');    
                        return;
                    }

                } else {
                    muestraErrorUbicaciones('Se produjo un error al procesar la recepción de materiales pero la ubicación se procesó correctamente');    
                    return;
                }
            }

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
    // $id_producto = $_POST['id_producto'];   
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

    //enviamos variable action a error.php porque lo necesita para el formulario de botón volver, a ubicaciones.php, aunque estemos en recepciones, dado que es el mismo formulario
    $action = 'ubicaciones';

    require_once("../views/templates/error.php");
}

//función log de ubicaciones
function ubicacionLog($id_producto = '', $id_product = 0, $id_product_attribute = 0, $ean = '', $localizacion = '', $reposicion = '', $login = 0, $cerrar = 0, $buscar_ean = 0, $mostrar_ubicacion = 0, $submit_ok = 0, $incidencia = 0, $cancelar = 0, $mensaje_error = '', $es_recepciones = 0) {
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
    recepciones,
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
    $es_recepciones,
    NOW())";
  
    Db::getInstance()->Execute($sql_insert_localizaciones_log);
  
    //si estamos haceindo recepción queremos guardar el id del nuevo insert en localizaciones_log en la tabla recepciones
    if ($_SESSION["funcionalidad"] == 'recepciones') {
        return Db::getInstance()->Insert_ID(); 
    }

    return;
  
  }

//10/07/2023 Añadimos funciones para recepciones, dado que utilizamos la estructura del ubicador
//POR AHORA ESTA NO SE USA, SOLO BUSCAMOS PRODUCTO (debajo) - función que recibe un string introducido en el formulario de búsqueda y obtiene el o los pedidos de materiales que se adecuen a dicho string. Puede ser un ean de producto, o referencia de prestashop, o de proveedor, locual hará la búsqueda de pedidos que contengan dicho producto y estén en estado pendiente de recpción o parcialmente recibido, o directamente la referencia del pedido o parte de ella.
// function obtenerPedidoMateriales($identificador) {
//     $sql_pedidos = "SELECT sor.id_supply_order AS id_supply_order, sor.id_supplier AS id_supplier, sup.name AS supplier, sor.id_supply_order_state AS id_supply_order_state,
//     sol.name AS state, sor.reference AS supply_order, sor.date_add AS date_add, sor.date_upd AS date_upd
//     FROM lafrips_supply_order sor
//     JOIN lafrips_supply_order_detail sod ON sod.id_supply_order = sor.id_supply_order
//     JOIN lafrips_supply_order_state_lang sol ON sol.id_supply_order_state = sor.id_supply_order_state AND sol.id_lang = 1
//     JOIN lafrips_supplier sup ON sup.id_supplier = sor.id_supplier
//     WHERE sor.id_supply_order_state IN (3, 4) #buscamos solo pedidos pendientes de recpción o parcialmente recibidos
//     AND sor.id_warehouse = 1 #solo almacén online
//     AND (
//         sod.reference LIKE CONCAT('%', $identificador, '%') OR
//         sod.supplier_reference LIKE CONCAT('%', $identificador, '%') OR
//         sod.ean13 LIKE CONCAT('%', $identificador, '%') OR
//         sor.reference LIKE CONCAT('%', $identificador, '%')
//     )
//     GROUP BY sod.id_supply_order
//     ORDER BY sor.id_supply_order ASC";

//     $pedidos = Db::getInstance()->ExecuteS($sql_pedidos);

//     if (empty($pedidos) || is_null($pedidos)) {
//         return array(1, 'No pude encontrar ningún pedido de materiales sin completar con ese identificador');
//     }

//     return array(0, $pedidos);
// }

//función que busca un producto por su ean entre los pedidos de materiales sin recibir
//19/09/2023 Hemos creado un nuevo estado de pedido de materiales, 7, pedido entregado. Ahora, cuando llegue en pedido al almacén, que estará en 3 pendiente de recpeción, en el momento que lo vayan a recepcionar lo tendrán que pasar a 7 - pedido entregado. Esta función buscará el ean solo en pedido en pedido entregado o recibido parcialmente, de modo que no se dará el error de que nos salga un producto de otro pedido en espera de llegar. Además, con el sistema de guardar el id de pedido seleccionado para el siguiente producto a escanear se arreglan casi todos los errores.
//24/10/2023 Hemos añadido un campo supply_order_message a los pedidos de materiales donde se podrá almacenar un mensaje sobre el pedido. Queremos recoger dicho mensaje para mostrarlo en el rececpionador
function obtenerPedidoMateriales($ean) {
    //unidades_esperadas_reales es la cantidad que queda por recibir respecto a quantity_expected del pedido de materiales. Se tienen en cuenta las que ya estén recibidas en el pedido de materiales, quantity_received y las que estén en lafrips_recepciones como cantidad_recibida. Si la resta de expected menos total recibido es negativa, se pone 0, esto marcará error al mostrar en el front.
    //para el caso de los pedidos de materiales nuevos de Cerdá, que incluyen al final los ids de expedición que contenga el archivo de expedición, hacemos un recorte a 19 caracteres al nombre, para que en el select solo se muestre hasta la fecha y hora, dado que son muy grandes si no. IF(sor.id_supplier = 65, SUBSTRING(sor.reference, 1, 19), sor.reference) AS supply_order
    $sql_pedidos = "SELECT sod.id_product AS id_product, sod.id_product_attribute AS id_product_attribute, sor.id_supply_order AS id_supply_order, sor.id_supplier AS id_supplier, sor.supply_order_message AS supply_order_message,
    sod.quantity_expected AS quantity_expected, sod.quantity_received AS quantity_received, sod.id_supply_order_detail AS id_supply_order_detail,
    sup.name AS supplier, sor.id_supply_order_state AS id_supply_order_state,
    sol.name AS state, 
    IF(sor.id_supplier = 65, SUBSTRING(sor.reference, 1, 19), sor.reference) AS supply_order, 
    DATE_FORMAT(sor.date_add, '%d-%m-%Y %H:%i:%s') AS date_add, sor.date_upd AS date_upd,
    (IFNULL((SELECT SUM(cantidad_recibida)
        FROM lafrips_recepciones 
        WHERE error = 0 
        AND finalizado = 0
        AND id_product = sod.id_product
        AND id_product_attribute = sod.id_product_attribute
        AND id_supply_order = sod.id_supply_order), 0) + sod.quantity_received)
        AS unidades_ya_recibidas,
    IF((sod.quantity_expected - (IFNULL((SELECT SUM(cantidad_recibida)
        FROM lafrips_recepciones 
        WHERE error = 0 
        AND finalizado = 0
        AND id_product = sod.id_product
        AND id_product_attribute = sod.id_product_attribute
        AND id_supply_order = sod.id_supply_order), 0) + sod.quantity_received)) < 1, 0, (sod.quantity_expected - (IFNULL((SELECT SUM(cantidad_recibida)
        FROM lafrips_recepciones 
        WHERE error = 0 
        AND finalizado = 0
        AND id_product = sod.id_product
        AND id_product_attribute = sod.id_product_attribute
        AND id_supply_order = sod.id_supply_order), 0) + sod.quantity_received)) ) AS unidades_esperadas_reales 
    FROM lafrips_supply_order sor
    JOIN lafrips_supply_order_detail sod ON sod.id_supply_order = sor.id_supply_order
    JOIN lafrips_supply_order_state_lang sol ON sol.id_supply_order_state = sor.id_supply_order_state AND sol.id_lang = 1
    JOIN lafrips_supplier sup ON sup.id_supplier = sor.id_supplier
    WHERE sor.id_supply_order_state IN (4, 7) #buscamos solo pedidos entregados o parcialmente recibidos
    AND sod.ean13 LIKE CONCAT('%', $ean, '%')
    GROUP BY sod.id_supply_order
    ORDER BY sor.id_supply_order ASC";

    $pedidos = Db::getInstance()->ExecuteS($sql_pedidos);

    if (empty($pedidos) || is_null($pedidos)) {
        return array(1, 'No pude encontrar un producto con ese Ean en ningún pedido de materiales en estado Entregado o Recibido parcialmente');
    }

    return array(0, $pedidos);
}

//función para almacenar los datos de recepción en la tabla recepciones
function almacenaRecepciones($id_localizaciones_log, $id_product, $id_product_attribute, $id_supply_order, $quantity_expected, $cantidad_recibida, $cantidad_avisada_recibida, $warning) {
    //obtenemos el ean del producto, el proveedor y el nombre del pedido de proveedores para id_supply_order
    $sql_info = "SELECT sor.id_supplier AS id_supplier, sup.name AS supplier, sor.reference AS supply_order_reference, sor.date_add AS supply_order_date_add,  
    sod.id_supply_order_detail AS id_supply_order_detail, sod.ean13 AS ean
    FROM lafrips_supply_order sor
    JOIN lafrips_supply_order_detail sod ON sod.id_supply_order = sor.id_supply_order    
    JOIN lafrips_supplier sup ON sup.id_supplier = sor.id_supplier
    WHERE sor.id_supply_order = $id_supply_order
    AND sod.id_product = $id_product
    AND sod.id_product_attribute = $id_product_attribute";

    if (!$info = Db::getInstance()->getRow($sql_info)) {
        return false;
    }

    $id_supplier = $info['id_supplier'];
    $supplier = $info['supplier'];
    $supply_order_reference = $info['supply_order_reference'];
    $supply_order_date_add = $info['supply_order_date_add'];
    $id_supply_order_detail = $info['id_supply_order_detail'];
    $ean = $info['ean'];

    //insertamos todos los  datos
    //$id_empleado y $nombre_empleado los sacamos de $_SESSION
    $id_empleado = $_SESSION['id_empleado'];
    $nombre_empleado = $_SESSION['nombre_empleado'];       
  
    $sql_insert_recepciones = "INSERT INTO lafrips_recepciones
    (id_localizaciones_log,
    id_product,
    id_product_attribute,
    ean,
    id_supply_order,
    id_supply_order_detail,
    supply_order_reference,
    supply_order_date_add,
    id_supplier,
    supplier,
    quantity_expected,
    cantidad_recibida,
    cantidad_avisada_recibida,
    id_employee,
    nombre_employee,
    warning,    
    date_add)
    VALUES
    ($id_localizaciones_log,
    $id_product,
    $id_product_attribute,
    '$ean',
    $id_supply_order,
    $id_supply_order_detail,
    '$supply_order_reference',
    '$supply_order_date_add',
    $id_supplier,
    '$supplier',
    $quantity_expected, 
    $cantidad_recibida, 
    $cantidad_avisada_recibida,    
    $id_empleado,
    '$nombre_empleado', 
    $warning,    
    NOW())";
  
    if (Db::getInstance()->Execute($sql_insert_recepciones)) {
        return true;
    }

    return false;
}




?>