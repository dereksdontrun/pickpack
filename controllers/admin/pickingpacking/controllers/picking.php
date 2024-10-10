<?php

include('herramientas.php');

//09/11/2022 Añadimos la funcionalidad para sacar varios pedidos a la vez (uno tras otro). Lo llamo pickin-packing varios. Consiste en seleccionar el botón picking/packing varios en la pantalla de login e introducir un pedido a buscar. Se buscarán los ids de todos los pedidos del cliente de ese pedido que tengan el trasnportista Guárdamelo (promo de Black friday de 2022), aunque esto se podría poner como buscar pedidos no enviados o algo así. Con esos id orders iremos mostrando cada pedido uno detrás de otro pero procesándolos individualmente, para no tener que hacer un nuevo pickpack. Al finalizar un pedido deberá mostrarse el siguiente. Para diferenciar si hemos pulsado sobre el botón normal de Picking o sobre  Picking Varios, en la url que viene desde pickpackindex habremos añadido un parámetro varios con valor 1 junto al id de empleado

//si llegamos desde pickpackindex tras pasar por el login
if(isset($_GET['id_empleado'])){

    $id_empleado = $_GET['id_empleado'];

    if ($id_empleado){
        if ($nombre_empleado = obtenerEmpleado($id_empleado)) {
            //almaceno en una sesión el id y nombre del empleado para usarlo después 
            session_start();
            $_SESSION["id_empleado"] = $id_empleado;
            $_SESSION["nombre_empleado"] = $nombre_empleado;
            $_SESSION["varios"] = 0;            

            //con los datos del empleado en sesión, mostramos el formulario de búsqueda de pedido. Como vamos a usar el mismo formulario para picking y packing, tenemos una variable de sesión 'varios' que indica el proceso, para modificar el destino del formulario y los estilos, color etc
            $action = 'picking';            

            //comprobamos si la url contiene parámetro varios
            if(isset($_GET['varios']) && $_GET['varios'] == 1){
                $_SESSION["varios"] = 1;                
            }

            //pickpack_log login
            pickpackLog(0, 0, 'picking', 0, 0, 0, 0, 0, 1);

            require_once("../views/templates/buscapedido.php");
        } else {
            //no se ha encontrado el nombre de usuario, queremos que vuelva de la pantalla de error al login
            muestraError('login', 'No encuentro el usuario en el sistema');       
            return;     
        }
    } else {
        $token = Tools::getAdminTokenLite('AdminModules');
        $url_modulos = _MODULE_DIR_;
        
        $url = $url_modulos.'pickpack/controllers/admin/pickingpacking/pickpackindex.php?token='.$token;  
        
        header("Location: $url");
    }   


} elseif (isset($_POST['submit_pedido'])) {
    //llegamos del formulario de buscar pedido para picking
    if ($_POST['id_pedido'] && $_POST['id_pedido'] != '') {
        $pedido = $_POST['id_pedido'];
        $_SESSION["numero_pedidos"] = 0; 
        $_SESSION["ids_pedidos"] = array(); //en este array en la sesión meteremos los ids de pedidos a mostrar e iremos eliminando a medida que los terminamos, hasta que no quede ninguno      

        $busqueda = obtenerIdOrder($pedido);
    } else {
        //no se ha introducido nada en el formulario
        muestraError('picking', 'Debes introducir algo para buscar en el formulario, aquí no somos adivinos');  
        return;        
    }    

    //obtenerIdOrder devuelve un array con el id_order en primer lugar y el error en segundo. Si devuelve id_order como 0, es que hay error, llamamos a error.php
    if (!$busqueda[0]) {
        //enviamos la variable que contiene la descripción del error y la acción que estabamos haciendo
        muestraError('picking', $busqueda[1]);    
        return;    
    } else {
        //devuelve un id_order correcto, pasamos a procesar el picking
        $id_order = $busqueda[0];

        //lo metemos en un array para los casos de sacar varios pedidos
        $ids_pedidos = array();
        $ids_pedidos[] = $id_order;

        //si hemos utilizado la función de sacar varios pedidos $varios será 1
        if ($_SESSION["varios"]) {
            //tenemos el id_order de un pedido del cliente, tenemos que buscar el resto de id_order de pedidos del cliente, válidos y no enviados que a 11/11/2022, tengan asociado transporte Guárdamelo. 
            //cambio la función para que devuelva los pedidos así pero en lugar del transporte deben estar en la tabla lafrips_pedidos_guardados. Tendré que evitar el de $id_order repetido ya que devuelve todos
            //Usamos función en Herramientas. También devuelve el estado en que se halla cada pedido, y un marcador de error si el estado no es Pago Aceptado. Salvo el pedido "padre" que ya tiene etiqueta, el resto deberían estar en Pago aceptado indicando que están completos y preparados para enviar.
            //OJO, si introducimos otro pedido del cliente y no el de la etiqueta nos mostrará error al estar un pedido en Etiquetado GLS o lo que sea
            //primero comprobamos que el pedido entró como guardado
            if (!esGuardado($id_order)) {
                //no está en lafrips_pedidos_guardados
                muestraError('picking', 'El pedido introducido no corresponde a un pedido guardado', (int)$id_order);  
                return;
            }
            $pedidos_cliente = variosPedidos($id_order);            
            
            $estados_erroneos = array();
            if (count($pedidos_cliente) > 0) {
                foreach ($pedidos_cliente AS $pedido_cliente) {
                    //evitamos el pedido buscado para no repetir
                    if ($pedido_cliente['id_order'] != $id_order) {
                        $ids_pedidos[] = $pedido_cliente['id_order'];     
                    
                        if ($pedido_cliente['estado_erroneo']) {
                            $estados_erroneos[$pedido_cliente['id_order']] = $pedido_cliente['estado'];
                        }
                    }                    
                }
            }

            if (!empty($estados_erroneos)) {
                //algún pedido no está en estado Pago Aceptado, o Preparación en proceso, ya se verá, generamos error
                //OJO, si introducimos otro pedido del cliente y no el de la etiqueta nos mostrará error al estar un pedido en Etiquetado GLS o lo que sea
                $mensaje_estado_erroneo = 'El/los siguientes pedidos no se encuentran en Preparación en proceso 2:<br>';
                foreach ($estados_erroneos AS $key => $value) {
                    $mensaje_estado_erroneo .= $key.' - '.$value.'<br>';
                }
                
                muestraError('picking', $mensaje_estado_erroneo);   
                return;
            }

            $_SESSION["numero_pedidos"] = count($ids_pedidos);
            $_SESSION["ids_pedidos"] = $ids_pedidos;
            // var_dump($_SESSION);
            
        } else {
            //no es proceso de 'varios', de modo que metemos solo el id_order a $_SESSION["ids_pedidos"], que se encuentra en $ids_pedidos
            $_SESSION["numero_pedidos"] = 1;
            $_SESSION["ids_pedidos"] = $ids_pedidos;
        }
        
        //Llamamos a inicioPicking() teniendo en $_SESSION["ids_pedidos"] los id_order de todos los pedidos a hacer picking, que puede contener 1 o varios
        inicioPicking();         
        
    }    

} elseif (isset($_POST['submit_finpicking'])) {
    //procesamos el formulario del picking. Antes mostrabamos un mensaje de éxito pero para agilizar el picking lo hemos quitado
    //sacamos id y nombre de empleado de la sesión
    // $id_empleado = $_SESSION["id_empleado"];
    // $nombre_empleado = $_SESSION["nombre_empleado"];

    if ($_POST['id_pedido']){
        $id_order = $_POST['id_pedido'];
    }
    //sacamos los ids de pedidos cajas si los hay, es un array que vendría serializado por POST
    if ($_POST['ids_cajas']){
        $ids_cajas = unserialize($_POST['ids_cajas']);
    }
    //18/01/2024 Hemos añadido un inpit hidden donde almacenar el código de la gaveta usada para dejar productos en caso de incidencia. Comprobamos el value y si hay algo generaremos un mensaje en el pedido donde guardaremos el código de modo que este se verá en el siguiente picking del pedido. No debería llegar nada que no sea un número de máximo 8 cifras. Guardaremos la localización también en lafrips_pick_pack    
    if ($_POST['gaveta_incidencias'] && !is_null($_POST['gaveta_incidencias']) && $_POST['gaveta_incidencias'] !==''){
        $gaveta_incidencias = $_POST['gaveta_incidencias'];

        pickpackLog($id_order, 0, 'gaveta_incidencias_picking', 0, 0, 0, 1, 0, 0, '', $gaveta_incidencias);

        // mensajePedido($id_order, $gaveta_incidencias); Por ahora no generamos mensaje dentro del pedido, ya que la localización de la gaveta se verá tanto al hacer picking como dentro de gestion pickpack
    } else {
        $gaveta_incidencias = '';
    }
    //para no sobreescribir el comentario, lo añadiremos al anterior si fuera la segunda vez o más que pasamos por aquí o no insertaremos nada si no llega ninguno. Añadimos persona que lo escribe y fecha. Llamamos a función generaComentario() en herramientas
    if ($_POST['comentario'] && $_POST['comentario'] !==''){
        $comentario_picking = generaComentario('pedido', 'picking');        
    } else {
        $comentario_picking = '';
    }
    if ($_POST['obsequio'] && $_POST['obsequio']=='on'){
        $obsequio = 1;
    } else {
        $obsequio = 0;
    }

    //procesaProductos procesa $_POST y si encuantra algún producto marcado no ok devolverá incidencia = 1. $_POST es global y no hay que enviarlo como parámetro
    $incidencia = procesaProductos();
    
    //actualizamos los valores sobre el pedido en picking en la tabla lafrips_pick_pack. Cambiaremos la fecha date_fin_picking, si $incidencia es 0. Si $incidencia es 1, pondremos estado Incidencia Picking, si no Picking Finalizado. Insertaremos el id y nombre de empleado picking. También pasaremos 'comenzado' a 1, o lo ratificaremos si ya ha pasado por aquí. Si había obsequio lo marcamos a 1. Guardamos el comentario si lo hay.
    // $id_pickpack = PickPackOrder::getIdPickPackByIdOrder($id_pedido);
    // $order_pickpack = new PickPackOrder($id_pickpack);
    // $order_pickpack->id = $id_pickpack;
    // $order_pickpack->id_pickpack_order = $id_pedido;
    // $order_pickpack->comenzado = 1;
    // $order_pickpack->id_employee_picking = (int)$id_empleado;
    // $order_pickpack->nombre_employee_picking = $nombre_empleado;
    // $order_pickpack->comentario_picking = nl2br($comentario);
    // $order_pickpack->obsequio = (int)$obsequio;
    // if ($incidencia){
    //   //si hay incidencia ponemos o dejamos el estado Incidencia Picking
    //   $order_pickpack->id_estado_order = 3;
    // } else {
    //   //si no hay incidencia ponemos el estado Picking Finalizado y metemos la fecha
    //   $order_pickpack->id_estado_order = 4;
    //   $order_pickpack->date_fin_picking = date("Y-m-d H:i:s");
    // }
    // $order_pickpack->save();
       

    //08/09/2020 Además, si no hay incidencia tras el picking y el pedido contenía cajas sorpresa, se pondrá como picking finalizado cada pedido con caja sorpresa. Los id de esos pedidos estarían en el array ids_cajas. Si hay incidencia, pasamos los pedidos a incidencia, pero no ponemos mensaje.
    if (count($ids_cajas) > 0) {
        //creamos el mensaje
        if (!$incidencia){
            $comentario_picking_caja = generaComentario('caja', 'picking', $id_order);            
        } else {
            $comentario_picking_caja = '';
        }

        foreach ($ids_cajas AS $id_caja) {
            finalizaOrder($id_caja, 'picking', $incidencia, $comentario_picking_caja, 0, 0, 1);            
        }
    }
        
    //finalmente mandamos a procesar el pedido base. finalizaOrder() devuelve true si hizo el update correcto
    //19/01/2024 Añadimos el parámetro gaveta_incidencias para guardar la localización en caso de incidencia en picking. De moemnto solo para pedido base, no por cajas.
    if (finalizaOrder($id_order, 'picking', $incidencia, $comentario_picking, $obsequio, 0, 0, $gaveta_incidencias)){
        //una vez guardado el resultado del picking, volvemos a la función inicioPicking() para saber si hay que continuar con otro pedido o ya hemos finalizado
        inicioPicking();
        // if ($_SESSION['varios'] && !empty($_SESSION['ids_pedidos'])) {
        //     inicioPicking();
        // } else {
        //     $action = 'picking';
        //     require_once("../views/templates/buscapedido.php");
        // }
        
    } else {
        muestraError('picking', 'Hubo un problema al actualizar los datos del picking', (int)$id_order); 
        return;        
    }

} elseif (isset($_POST['submit_volver'])) {
    //pickpack_log  
    $id_order = $_POST['id_pedido'];   
    //si $id_order = 0 es que venimos de la pantalla de error, por ejemplo por dar a buscar con el input vacío. No hacemos log ya que para errores lo hacemos en muestraError()
    if ($id_order) {
        pickpackLog($id_order, 0, 'picking', 0, 0, 0, 0, 1);
    }      

    $action = 'picking';
    require_once("../views/templates/buscapedido.php");
}

//función que estudia el array de ids de pedido en $_SESSION['ids_pedidos'] y va lanzando el picking para cada pedido. Si el proceso no es 'varios' la variable de sesión solo contendrá un id_order
function inicioPicking() {
    //si el array de $_SESSION no está vacío, sacamos el primer elemento. De modo que si llegamos con varios id_order saca el primero, si solo hay uno saca ese, y se va vaciando, hasta que no quedan más. Cuando se llegue aquí con $_SESSION['ids_pedidos'] vacío se considera finalizado el picking    
    // echo '<br>';
    if (!empty($_SESSION['ids_pedidos'])) {
        $id_order = array_shift($_SESSION['ids_pedidos']);
        // print_r($id_order);
        $info_pedido = infoOrder($id_order);
        if (!$info_pedido) {
            muestraError('picking', 'No pude encontrar el pedido', (int)$id_order);       
            return;     
        } else {
            //enviamos los datos a la función procesaOrder() para que procese
            procesaOrder($info_pedido);
        }   
    } else {
        //picking finalizado, ya no quedan pedidos
        $action = 'picking';
        require_once("../views/templates/buscapedido.php");

    } 
    
    

    // foreach ($_SESSION['ids_pedidos'] AS $id_order) {
    //     $info_pedido = infoOrder($id_order);
    //     if (!$info_pedido) {
    //         muestraError('picking', 'No pude encontrar el pedido');            
    //     } else {
    //         //enviamos los datos a la función procesaOrder() para que procese
    //         procesaOrder($info_pedido);
    //     }    

    // }

    // $info_pedido = infoOrder($id_order);
        // if (!$info_pedido) {
        //     muestraError('picking', 'No pude encontrar el pedido');            
        // } else {
        //     //enviamos los datos a la función procesaOrder() para que procese
        //     procesaOrder($info_pedido, $varios);

        // }       
}



function procesaOrder($info_pedido) {
    //sacamos la info para el picking y para buscar los mensajes de cliente
    $id_order = $info_pedido[0]['id_order'];
    $id_cliente = $info_pedido[0]['id_cliente'];
    $nombre_cliente = $info_pedido[0]['nombre_cliente'];
    //10/10/2024 Miramos si el grupo de cliente es Sith (id_group 7), en cuyo caso mostraremos un warning
    $customer_id_default_group = $info_pedido[0]['grupo_cliente'];    
    $direccion = $info_pedido[0]['direccion'];
    $codigo_postal = $info_pedido[0]['codigo_postal'];
    $ciudad = $info_pedido[0]['ciudad'];
    $provincia = $info_pedido[0]['provincia'];
    $pais = $info_pedido[0]['pais'];
    $fecha_pedido = date('d-m-Y', strtotime($info_pedido[0]['fecha_pedido']));
    $transporte = $info_pedido[0]['transporte'];
    $regalo = $info_pedido[0]['regalo'];
    $id_cart = $info_pedido[0]['id_cart'];
    $amazon = $info_pedido[0]['module'];
    $pedido_dropshipping = $info_pedido[0]['pedido_dropshipping'];
    $dropshipping_envio_almacen = $info_pedido[0]['dropshipping_envio_almacen'];
    //si el módulo de pago es amazon $amazon = 1
    if ($amazon == 'amazon'){
        $amazon = 1;
    } else {
        $amazon = 0;
    }
    $estado_pickpack = $info_pedido[0]['estado_pickpack'];
    $finalizado = $info_pedido[0]['finalizado'];
    $fecha_fin_packing = $info_pedido[0]['fecha_fin_packing'];
    $gaveta_incidencias = $info_pedido[0]['gaveta_incidencias'];

    // Número de pedidos del cliente
    $sql_numero_pedidos = "SELECT COUNT(id_order) AS num_pedidos FROM lafrips_orders WHERE id_customer = ".$id_cliente." AND valid = 1;";
    $numero_pedidos = Db::getInstance()->ExecuteS($sql_numero_pedidos);
    $numero_pedidos = $numero_pedidos[0]['num_pedidos'];  

    //18/03/2024 Vamos a no mostrar ningún mensaje en el pickin g de momento
    // $todos_mensajes_pedido = mensajesOrder($id_order);  
    $todos_mensajes_pedido = "";  

    //info de productos en pedido. Aquí obtenemos los productos del pedido base. Si hay una caja aparece como caja, no su contenido. Enviamos parámetro que indica si el pedido es dropshipping y si sería entrega en almacén
    $productos_pedido = infoProducts($id_order, 0, 0, $dropshipping_envio_almacen, $pedido_dropshipping);
    
    if ($productos_pedido == 'dropshipping') {
        //el pedido era dropshipping y no devuelve productos, probablemente todos los porductos son dropshipping con entrega a cliente
        //pickpack_log            
        pickpackLog($id_order, 0, 'picking_dropshipping', 1);
        //mostramos picking, aunque estos pedidos deberían estar en enviado y no salir en el proceso de varios
        $warning_pedido_dropshipping = 1;
        $productos_pedido = null;
        require_once("../views/templates/muestrapicking.php");
    } elseif (!$productos_pedido) {
        //no encuentra productos y no era dropshipping
        muestraError('picking', 'No pude encontrar los productos del pedido', (int)$id_order);   
        return;      
    } else {
        //devuelve productos
        //tenemos que comprobar si en el pedido hay cajas sorpresa para sacar los productos. Si el campo de producto 'customizable' es distinto de 0 y el id_product es 5344. Enviamos $productos_pedido a otra función en herramientas.php que primero comprueba si hay cajas, y si las hay, dependiendo de si es picking (mostrará todos los productos seguidos) o packing (deben salir cada producto "dentro" de su caja) devolverá $productos_pedido en un formato u otro.

        //23/11/2020 Si el pedido contiene cajas sorpresa, vamos a marcar picking comenzado también en los pedidos que contienen las cajas, para ello llamamos desde aquí a checkCajas con $action = obtener_ids_cajas para recoger únicamente los ids si los hay, y modificar dichas líneas del pickpack (modificamos la función checkCajas en herramientas.php). Lo ejecutamos primero para obtener los id de las cajas y debajo para los productos
        //aquí solo obtenemos los id de pedido que contiene productos de caja sorpresa si lo hubiera, si no 0
        $ids_orders_caja_sorpresa = checkCajas($productos_pedido , 'obtener_ids_cajas', $id_order, $id_cart);

        //ahora si, miramos si tiene cajas para recoger los productos
        //si el pedido base no tiene cajas sopresa devuelve $productos_pedido como se le ha enviado, pero si tiene, devuelve los productos del pedido original más los de los pedidos que contienen los productos de caja sorpresa, ordenados para picking (por localización todos juntos)
        $productos_pedido = checkCajas($productos_pedido , 'picking', $id_order, $id_cart);
        //si vuelve 0 es que había cajas pero hay errores
        if (!$productos_pedido) {
            muestraError('picking', 'Error con la/s caja/s sorpresa del pedido. No han sido creadas o no se encuentran.', (int)$id_order);     
            return;        
        } else {
            //en este punto debemos tener la info del pedido, del cliente y sus mensajes y de los productos en el pedido, comprobamos si es el primer picking del pedido y llamamos a la vista para mostrar el picking
            // para almacenar la fecha de inicio de picking, cuando se muestra el pedido hacemos una búsqueda en lafrips_pick_pack. Si comenzado_picking es 0 es que es el primer picking, lo ponemos a 1 y guardamos date now() en date_inicio_picking. Si hay incidencias y se repiten los picking será un dato inválido, la duración del picking solo sirve si es un pedido sin incidencias. También cambiamos el estado de pickpack a Picking Abierto      
            
            //23/11/2020 Si en el pedido hay cajas sorpresa, en el array $ids_orders_caja_sorpresa están los ids de lo/s pedido/s que las contienen. Les marcamos también comenzado_picking etc si necesario.
            if (count($ids_orders_caja_sorpresa) > 0) {
                comenzadoPickingCajas($id_order, $ids_orders_caja_sorpresa);                
            }

            //para el pedido base
            $sql_comenzado_picking = 'SELECT comenzado_picking FROM lafrips_pick_pack WHERE id_pickpack_order = '.$id_order;
            $comenzado_picking = Db::getInstance()->ExecuteS($sql_comenzado_picking);
            //si comenzado_picking es 0 marcamos inicio picking
            if (!$comenzado_picking[0]['comenzado_picking']){
                //el log se hace en la función comenzadoPicking()
                comenzadoPicking($id_order);                
            } else {
                //solo log, abrir pero no primera vez
                //pickpack_log            
                pickpackLog($id_order, 0, 'picking', 1);
            }
            
            require_once("../views/templates/muestrapicking.php");
        }
        
    }

}

function mensajesOrder($id_order) {
    //queremos mostrar en el picking los mensajes sobre el pedido, SOLO PRIVADOS
    $sql_mensajes_sobre_pedido = "SELECT cum.id_employee AS id_empleado, CONCAT(emp.firstname,' ',emp.lastname) AS nombre_empleado, cum.message AS mensaje, cum.date_add AS fecha
    FROM lafrips_customer_message cum
    JOIN lafrips_customer_thread cut ON cut.id_customer_thread = cum.id_customer_thread
    LEFT JOIN lafrips_employee emp ON emp.id_employee = cum.id_employee
    WHERE cum.private = 1
    AND cut.id_order = ".$id_order."
    ORDER BY cum.date_add DESC;";

    $mensajes_sobre_pedido = Db::getInstance()->ExecuteS($sql_mensajes_sobre_pedido);

    //vamos a crear un array key=>value que contenga todos los mensajes que haya que mostrar sobre el pedido (no sobre cliente o mensaje de envoltorio regalo). Key será la parte que contiene la fecha y persona que creó el mensaje y value el mensaje en si.
    $todos_mensajes_pedido = array();
    if($mensajes_sobre_pedido){
        foreach ($mensajes_sobre_pedido AS $mensaje){
            //primero montamos la cabecera del mensaje, fecha con creador de mensaje
            $fecha = date_create($mensaje['fecha']); 
            $fecha = date_format($fecha, 'd-m-Y H:i:s');
            $mensajeador = $mensaje['nombre_empleado'];
            $mensaje = $mensaje['mensaje'];
            
            $cabecera = $fecha.'<b> '.$mensajeador.'</b>'; 

            //introducimos todo al array de mensajes
            $todos_mensajes_pedido[$cabecera] = $mensaje;
        }           

    }

    return $todos_mensajes_pedido;

}

//función que recibe id_order y marca el pedido como comenzado picking (utilizado para estadísticas). El pedido puede ser base, o de caja sorpresa, en cuyo caso enviaremos el comentario a añadir como parámetro. Cada pedido solo puede pasar por aquí una vez, la primera que se abre picking
function comenzadoPicking($id_order, $comentario_picking_caja = '') {   
    $sql_update_comenzado_picking = 'UPDATE lafrips_pick_pack
    SET
    id_estado_order = 2,
    comenzado_picking = 1, 
    comenzado = 1,
    id_employee_picking = '.$_SESSION["id_empleado"].',
    nombre_employee_picking = "'.$_SESSION["nombre_empleado"].'",
    '.$comentario_picking_caja.' 
    date_inicio_picking = NOW(),
    date_upd = NOW()
    WHERE id_pickpack_order = '.$id_order.';';
    Db::getInstance()->execute($sql_update_comenzado_picking);

    //pickpack_log  
    //si es caja lo indicamos en log
    $caja = 0;
    if ($comentario_picking_caja != '') {
        $caja = 1;
    }          
    pickpackLog($id_order, $caja, 'picking', 1, 1);
}

//función que recibe el/los id de pedido que contienen los productos de las cajas sorpresa, crea comentario y llama a comenzadoPicking para marcarlos como comenzados
function comenzadoPickingCajas($id_order, $ids_orders_caja_sorpresa) { 
    foreach ($ids_orders_caja_sorpresa AS $id_caja) {

        $comentario_caja = '- Picking comenzado para productos de Caja correspondiente a pedido padre '.$id_order.'<span style=\"font-size:80%;\"><i>  '.$_SESSION["nombre_empleado"].' </i>';  
        $comentario_picking_caja = 'comentario_picking = CONCAT(comentario_picking,"'.$comentario_caja.'",DATE_FORMAT(NOW(),"%d-%m-%Y %H:%i:%s"),"</span><br>"),';

        //comprobamos si ya está marcado como comenzado picking
        $sql_comenzado_picking = 'SELECT comenzado_picking FROM lafrips_pick_pack WHERE id_pickpack_order = '.$id_caja;
        $comenzado_picking = Db::getInstance()->ExecuteS($sql_comenzado_picking);
        //si comenzado_picking es 0 marcamos inicio picking
        if (!$comenzado_picking[0]['comenzado_picking']) {
            //pickpack_log lo hacemos en comenzadoPicking()  

            comenzadoPicking($id_caja, $comentario_picking_caja);            
        } else {
            //solo log, abrir pero no primera vez
            //pickpack_log            
            pickpackLog($id_caja, 1, 'picking', 1);
        }
    }
}

//función que recibe el POST después de cerrar picking con submit_pedido y lo procesa para marcar cada producto como finalizado o incidencia. Devuelve $incidencia, que será 0 si no hubo (todos los productos marcados ok) y 1 si hubo (algún producto marcado no ok)
function procesaProductos() {
    $numero_productos = 0;
    if ($_POST['numero_productos']) {
        $numero_productos = $_POST['numero_productos'];
    }
    //ponemos un marcador $incidencia que coja valor 1 si alguno de los productos ha sido marcado con la opción de no ok del radio button 
    $incidencia = 0;
    //sacamos los id_product y id_product_atribute e id_order de cada producto. Como $_POST contiene los valores del formulario en el orden de este, saco el contenido desde el primero hasta $numero_productos y los almaceno en tres variables, $id_product y $id_product_atribute e $id_order después de separar la cadena que llega (tipo 12323_2345_123456) $id_order indica a qué pedido corresponde el producto. Si hay cajas sorpresa y el id no corresponde al pedido "base", pertenecerá al pedido virtual que contiene los productos de una caja sorpresa
    $contador = 1;
    foreach ($_POST as $key => $value) {
        if ($contador <= $numero_productos) {
            //$key contiene el id unido al id atributo con barra baja
            $ids = explode('_', $key);
            $id_product = $ids[0];
            $id_product_attribute = $ids[1];
            $id_pedido_producto = $ids[2];
            //marcaremos con uno el producto como incidencia_picking para saber cual estuvo mal en el picking. $incidencia nos señala que el pedido en general ha tenido incidencia. Si el producto pasa una vez como incidencia se marca, pero nunca se vuelve a poner a 0 si se corrige     
            $incidencia_producto = '';   
            //$value contiene 1 o 0 si el radio button estaba en ok o no
            $correcto = $value;
            if (!$correcto) {
                $incidencia = 1;
                $incidencia_producto = ' ,incidencia_picking = 1 ';
            } else {            
                $incidencia_producto = ' ';
            }
            //Creamos la sql para hacer update de los productos en picking comprobando producto a producto si está en lafrips_pick_pack_productos (si el update da resultado) y si no lo está lo metemos con un insert, ya que puede darse el caso de que se cambie o añada algún producto si hay incidencia. 
            //como Db::getInstance()->Execute() devuelve tru o false si la ejecución es correcta, pero eso quiere decir simplemente que no ha habido error, es decir, si no encuentra la línea para hacer update pero la sql es correcta, devuelve true también, para saber si el producto no está y hay que hacer insert, primero tenemos que buscar el producto. Si está hacemos update y si no insert
            $sql_busca_pickpack_product = 'SELECT id_pickpack_productos FROM lafrips_pick_pack_productos            
                WHERE id_product = '.$id_product.'
                AND id_product_attribute = '.$id_product_attribute.'
                AND id_pickpack_order = '.$id_pedido_producto;

            $id_pickpack_productos = Db::getInstance()->getValue($sql_busca_pickpack_product);

            if ($id_pickpack_productos) {
                $sql_update_producto = 'UPDATE lafrips_pick_pack_productos 
                    SET ok_picking = '.$correcto.$incidencia_producto.'
                    WHERE id_pickpack_productos = '.$id_pickpack_productos;

                Db::getInstance()->Execute($sql_update_producto);
                
            } else {
                //si no se hace el update, hacemos insert del producto
                if (!$correcto) {
                    $incidencia_prod = ', incidencia_picking';
                    $incidencia_producto = ' ,1 ';
                } else {
                    $incidencia_prod = '';
                    $incidencia_producto = '';
                }
                $sql_insert_pickpack_producto = "INSERT INTO lafrips_pick_pack_productos (id_pickpack_order, id_product, id_product_attribute, ok_picking ".$incidencia_prod.") VALUES (".$id_pedido_producto." ,".$id_product." ,".$id_product_attribute." ,".$correcto.$incidencia_producto.")";
                
                Db::getInstance()->Execute($sql_insert_pickpack_producto);
                
            }

            //Si algún producto que estaba en un picking anterior ya no se recibe en este picking, pondremos a 1 "eliminado". SIN HACERRRRR!!        

            $contador++;                
        }           
             
    }

    return $incidencia;
}

// //función que marca los resultados del picking en el pedido en lafrips_pickpack
// function finalizaOrder($id_order, $incidencia, $comentario_picking = '', $obsequio = 0) {
//     //obtenemos el id de la tabla pickpack
//     $id_pickpack = PickPackOrder::getIdPickPackByIdOrder($id_order);
    
//     if ($incidencia) {
//         //si hay incidencia ponemos o dejamos el estado Incidencia Picking y marcamos incidencia_picking = 1
//         $id_estado_order = 3;
//         $date_fin_picking = '"0000-00-00 00:00:00"';
//         $incidencia_picking = ' incidencia_picking = 1 ,';
//     } else {
//         //si no hay incidencia ponemos el estado Picking Finalizado y metemos la fecha
//         $id_estado_order = 4;
//         //$date_fin_picking = date("Y-m-d H:i:s");
//         $date_fin_picking = 'NOW()';
//         $incidencia_picking = '';
//     }

//     $sql_update_pickpack_pedido = 'UPDATE lafrips_pick_pack
//     SET
//     comenzado = 1,
//     id_estado_order = '.$id_estado_order.',
//     id_employee_picking = '.$_SESSION["id_empleado"].',
//     nombre_employee_picking = "'.$_SESSION["nombre_empleado"].'",
//     '.$comentario_picking.'
//     obsequio = '.$obsequio.',
//     '.$incidencia_picking.'
//     date_fin_picking = '.$date_fin_picking.',
//     date_upd = NOW()
//     WHERE id_pickpack = '.$id_pickpack;   

//     if (Db::getInstance()->execute($sql_update_pickpack_pedido)){
//         return true;
//     }

//     return false;
// }



?>