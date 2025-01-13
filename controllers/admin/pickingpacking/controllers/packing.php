<?php

include('check_login.php');
include('herramientas.php');

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

            //con los datos del empleado en sesión, mostramos el formulario de búsqueda de pedido. Como vamos a usar el mismo formulario para picking y packing, enviamos una variable que indica el proceso, para modificar el destino del formulario y los estilos, color etc
            $action = 'packing';

            //comprobamos si la url contiene parámetro varios
            if(isset($_GET['varios']) && $_GET['varios'] == 1){
                $_SESSION["varios"] = 1;                
            }

            //pickpack_log login
            pickpackLog(0, 0, 'packing', 0, 0, 0, 0, 0, 1);

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

} elseif (isset($_POST['submit_pedido'])){
    //llegamos del formulario de buscar pedido para packing
    if ($_POST['id_pedido'] && $_POST['id_pedido'] != '') {
        $pedido = $_POST['id_pedido'];
        $_SESSION["numero_pedidos"] = 0; 
        $_SESSION["ids_pedidos"] = array(); //en este array en la sesión meteremos los ids de pedidos a mostrar e iremos eliminando a medida que los terminamos, hasta que no quede ninguno
        $_SESSION["incidencia"] = 0;  //si se encuentra una incidencia en el packing en algún pedido, esta variable de sesión actuará como global para que no se procese el cambio de estado de prestashop a los pedidos
        $_SESSION["pedidos_cambio_estado"] = array(); //en este array guardaremos la correspondencia id_order - tipo pedido, siendo tipo pedido 'pedido' o 'caja', del o los pedidos a los que cambiar el estado de prestashop al cerrar el packing. Pasaremos los caja a entregado y los pedido a enviado o entregado según cada uno. Cuando se termine el packing, en inicioPacking() si no ha habido incidencias se enviarán los pedidos dentro de este array a cambioEstado() uno a uno

        $busqueda = obtenerIdOrder($pedido);
    } else {
        //no se ha introducido nada en el formulario
        muestraError('packing', 'Debes introducir algo para buscar en el formulario, aquí no somos adivinos');         
        return;
    }

    //obtenerIdOrder devuelve un array con el id_order en primer lugar y el error en segundo. Si devuelve id_order como 0, es que hay error, llamamos a error.php
    if (!$busqueda[0]) {
        //enviamos la variable que contiene la descripción del error y la acción que estabamos haciendo
        muestraError('packing', $busqueda[1]);
        return;

    } else {
        //devuelve un id_order correcto, pasamos a procesar el packing
        $id_order = $busqueda[0];

        //lo metemos en un array para los casos de sacar varios pedidos
        $ids_pedidos = array();
        $ids_pedidos[] = $id_order;

        //si hemos utilizado la función de sacar varios pedidos $varios será 1
        if ($_SESSION["varios"]) {
            //tenemos el id_order de un pedido del cliente, tenemos que buscar el resto de id_order de pedidos del cliente, válidos y no enviados que a 11/11/2022, tengan asociado transporte Guárdamelo. 
            //cambio la función variosPedidos() para que devuelva los pedidos así pero en lugar del transporte deben estar en la tabla lafrips_pedidos_guardados. Tendré que evitar el de $id_order repetido ya que devuelve todos
            //Usamos función variosPedidos() en Herramientas. También devuelve el estado en que se halla cada pedido, y un marcador de error si el estado no es Pago Aceptado. Salvo el pedido "padre" que ya tiene etiqueta, el resto deberían estar en Pago aceptado indicando que están completos y preparados para enviar.
            //OJO, si introducimos otro pedido del cliente y no el de la etiqueta nos mostrará error al estar un pedido en Etiquetado GLS o lo que sea
            //primero comprobamos que el pedido entró como guardado
            if (!esGuardado($id_order)) {
                //no está en lafrips_pedidos_guardados
                muestraError('packing', 'El pedido introducido no corresponde a un pedido guardado');  
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
                
                muestraError('packing', $mensaje_estado_erroneo);   
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
        
        //Llamamos a inicioPacking() teniendo en $_SESSION["ids_pedidos"] los id_order de todos los pedidos a hacer packing, que puede contener 1 o varios
        inicioPacking();  
        
    }    

} elseif (isset($_POST['submit_finpacking'])) {
    //procesamos el formulario del packing. 
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
    //para no sobreescribir el comentario, lo añadiremos al anterior si fuera la segunda vez o más que pasamos por aquí o no insertaremos nada si no llega ninguno. Añadimos usuario que lo escribe y fecha. Llamamos a función generaComentario() en herramientas
    if ($_POST['comentario'] && $_POST['comentario'] !==''){
        $comentario_packing = generaComentario('pedido', 'packing');  
    } else {
        $comentario_packing = '';
    }
    
    if ($_POST['checkbox_regalo'] && $_POST['checkbox_regalo']=='on'){
        $regalo = 1;
    } else {
        $regalo = 0;
    }
    if ($_POST['obsequio'] && $_POST['obsequio']=='on'){
        $obsequio = 1;
    } else {
        $obsequio = 0;
    }

    //procesaProductos procesa $_POST y si encuentra algún producto marcado no ok devolverá incidencia = 1. $_POST es global y no hay que enviarlo como parámetro
    $incidencia = procesaProductos();

    //si es necesario actualizamos $_SESSION["incidencia"]. Lo hacemos así para asegurar que, si hay varios pedidos y alguno ha tenido una incidencia, la variable de sesión tenga incidencia=1 aunque el siguiente pedido no tenga incidencia
    if ($incidencia) {
        $_SESSION["incidencia"] = 1;
    }
    
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
    
    //22/11/2022 finalizado el packing, si hay incidencia se marcará el pedido como estado incidencia packing, y si tiene cajas sorpresa se hará lo mismo para cada pedido de caja. Si no hay incidencia, se marcará el pedido como packing finalizado y estado Paquete enviado y se procederá al proceso de pasar a enviado, o lo que toque según el tipo de pedido. Si tuviera cajas sorpresa, sus pedidos, que serían virtuales se deberán pasar a Paquete Enviado y el pedido a entregado. Como en el caso de verios pedidos a la vez vamos pedido a pedido, no queremos cambiar los estados de prestashop hasta que no sepamos que no hay incidencia en ninguno, de modo que a medida que procesamos el packing de un pedido sin incidencia lo vamos metiendo en una variable de sesión $_SESSION["pedidos_cambio_estado"] con la correspondencia id_order y tipo (caja o pedido) y en inicioPacking(), una vez vacio el array de $_SESSION['ids_pedidos'], si no ha habido incidencias, haremos el cambio de estado de cada pedido.

    //primero las cajas si hay
    if (count($ids_cajas) > 0) {
        //creamos el mensaje
        if (!$incidencia){
            $comentario_packing_caja = generaComentario('caja', 'packing', $id_order);            
        } else {
            $comentario_packing_caja = '';
        }

        foreach ($ids_cajas AS $id_caja) {
            //marcamos como finalizado packing o no según haya incidencia, etc
            finalizaOrder($id_caja, 'packing', $incidencia, $comentario_packing_caja, 0, 0, 1); 
            //metemos el id de pedido y su tipo caja en $_SESSION["pedidos_cambio_estado"] para luego desde inicioPacking(), si no hay incidencia, hacer cambio de estado de pedido de caja de virtual a entregado            
            $_SESSION["pedidos_cambio_estado"][$id_caja] = 'caja';  

        }
    }

    //ahora pedido principal
    if (finalizaOrder($id_order, 'packing', $incidencia, $comentario_packing, $obsequio, $regalo)){
        //una vez guardado el resultado del packing, metemos el id de pedido y su tipo caja en $_SESSION["pedidos_cambio_estado"] para luego desde inicioPacking(), si no hay incidencia, procesar el pedido para su cambio de estado
        $_SESSION["pedidos_cambio_estado"][$id_order] = 'pedido';  

        //vamos a inicioPacking() para continuar
        inicioPacking();        
        
    } else {
        muestraError('packing', 'Hubo un problema al actualizar los datos del packing');      
        return;   
    }

} elseif (isset($_POST['submit_volver'])) {
    //pickpack_log  
    $id_order = $_POST['id_pedido'];          
    //si $id_order = 0 es que venimos de la pantalla de error, por ejemplo por dar a buscar con el input vacío. No hacemos log
    if ($id_order) {
        pickpackLog($id_order, 0, 'packing', 0, 0, 0, 0, 1);
    } 

    $action = 'packing';
    require_once("../views/templates/buscapedido.php");
}


//función que estudia el array de ids de pedido en $_SESSION['ids_pedidos'] y va lanzando el packing para cada pedido. Si el proceso no es 'varios' la variable de sesión solo contendrá un id_order.
//tenemos un parámetro $_SESSION["incidencia"]. Si vale 1 no se procederá al cambio de estado de pedidos que tengamos en $_SESSION["pedidos_cambio_estado"]
function inicioPacking() {
    //si el array de $_SESSION no está vacío, sacamos el primer elemento. De modo que si llegamos con varios id_order saca el primero, si solo hay uno saca ese, y se va vaciando, hasta que no quedan más. Cuando se llegue aquí con $_SESSION['ids_pedidos'] vacío se considera finalizado el packing    
    // echo '<br>';
    if (!empty($_SESSION['ids_pedidos'])) {
        $id_order = array_shift($_SESSION['ids_pedidos']);
        // print_r($id_order);
        $info_pedido = infoOrder($id_order);
        if (!$info_pedido) {
            muestraError('packing', 'No pude encontrar el pedido');          
            return;  
        } else {
            //enviamos los datos a la función procesaOrder() para que procese
            procesaOrder($info_pedido);
        }   
    } else {
        // var_dump($_SESSION);
        //packing finalizado, ya no quedan pedidos. Si no ha habido incidencia procedemos a cambiar los estados de pedido de prestashop
        if (!$_SESSION["incidencia"]) {
            //tenemos el/los pedidos en $_SESSION["pedidos_cambio_estado"], con formato key (id_order) => value (tipo pedido)
            $error_cambio_estado = 0;
            $resultado_cambio = '';
            foreach ($_SESSION["pedidos_cambio_estado"] AS $id_order => $tipo) {
                //cambioEstado devuelve un array con su primer elemento true o false si lo ha conseguido insertar o no, y segundo elemento un mensaje de error si el priemro es false
                $resultado_cambio = cambioEstado($tipo, $id_order);
                if ($resultado_cambio[0] !== true) {
                    $mensaje_error_cambio .= $resultado_cambio[1];
                    $error_cambio_estado = 1;
                }                 
            }        

            if ($error_cambio_estado) {
                //ponemos log, enviando el mensaje de error que mostramos
                pickpackLog(0, 0, 'cambio_estado', 0, 0, 0, 0, 0, 0, $mensaje_error_cambio);

                muestraError('packing', 'Hubo un problema al procesar el cambio de estado de Prestashop'.$mensaje_error_cambio);  
                return;       
            } else {
                //volvemos al formulario de buscar pedido
                $action = 'packing';
                require_once("../views/templates/buscapedido.php");
            }       
        } else {
            //hubo alguna incidencia, vovlemos a buscar pedido pero mostramos un mensaje de aviso
            $incidencia_packing = 1;
            $action = 'packing';
            require_once("../views/templates/buscapedido.php");
        }

    } 
}

function procesaOrder($info_pedido) {
    //sacamos la info para el packing y para buscar los mensajes de cliente
    $id_order = $info_pedido[0]['id_order'];
    $id_cliente = $info_pedido[0]['id_cliente'];
    $nombre_cliente = $info_pedido[0]['nombre_cliente'];
    $direccion = $info_pedido[0]['direccion'];
    $codigo_postal = $info_pedido[0]['codigo_postal'];
    $ciudad = $info_pedido[0]['ciudad'];
    $provincia = $info_pedido[0]['provincia'];
    $pais = $info_pedido[0]['pais'];
    $fecha_pedido = date('d-m-Y', strtotime($info_pedido[0]['fecha_pedido']));
    $transporte = $info_pedido[0]['transporte'];
    $metodo_pago = $info_pedido[0]['metodo_pago'];
    $estado_prestashop = $info_pedido[0]['estado_prestashop'];
    $regalo = $info_pedido[0]['regalo'];
    $id_cart = $info_pedido[0]['id_cart'];
    $mensaje_regalo = $info_pedido[0]['mensaje_regalo'];
    $pedido_dropshipping = $info_pedido[0]['pedido_dropshipping'];
    $dropshipping_envio_almacen = $info_pedido[0]['dropshipping_envio_almacen'];
    $pedido_webservice = $info_pedido[0]['pedido_webservice'];
    $nota_sobre_cliente = $info_pedido[0]['nota_sobre_cliente'];
    //10/10/2024 Miramos si el grupo de cliente es Sith (id_group 7), en cuyo caso mostraremos un warning
    $customer_id_default_group = $info_pedido[0]['grupo_cliente'];    
    if ($info_pedido[0]['tlfno1'] != "") {
        $telefono = $info_pedido[0]['tlfno1'];
    } else {
        $telefono = $info_pedido[0]['tlfno2'];
    }
    $amazon = $info_pedido[0]['module'];
    //si el módulo de pago es amazon $amazon = 1
    if ($amazon == 'amazon'){
        $amazon = 1;
    } else {
        $amazon = 0;
    }  
    $estado_pickpack = $info_pedido[0]['estado_pickpack'];
    $finalizado = $info_pedido[0]['finalizado'];
    $fecha_fin_packing = $info_pedido[0]['fecha_fin_packing'];

    //generamos el token de AdminGestionPickPack ya que lo vamos a usar en el archivo de javascript para hacer una llamada ajax para cambiar el estado del pedido a enviado . Lo almacenaremos en un input hidden para acceder a el desde js
    $token_admin_modulo = Tools::getAdminTokenLite('AdminGestionPickPack');

    // Número de pedidos del cliente
    $sql_numero_pedidos = "SELECT COUNT(id_order) AS num_pedidos FROM lafrips_orders WHERE id_customer = ".$id_cliente." AND valid = 1;";
    $numero_pedidos = Db::getInstance()->ExecuteS($sql_numero_pedidos);
    $numero_pedidos = $numero_pedidos[0]['num_pedidos'];   

    $todos_mensajes_pedido = mensajesOrder($id_order);  

    //sacamos la persona que hizo el picking
    $sql_empleado_picking = 'SELECT nombre_employee_picking FROM lafrips_pick_pack WHERE id_pickpack_order = '.$id_order;  
    $empleado_picking = Db::getInstance()->getValue($sql_empleado_picking);

    //sacamos los comentarios de picking
    $sql_comentario_picking = 'SELECT comentario_picking FROM lafrips_pick_pack WHERE id_pickpack_order = '.$id_order;  
    $comentario_picking = Db::getInstance()->getValue($sql_comentario_picking);    
    

    //info de productos en pedido. Aquí obtenemos los productos del pedido base. Si hay una caja aparece como caja, no su contenido. Enviamos parámetro que indica si el pedido es dropshipping y si sería entrega en almacén
    $productos_pedido = infoProducts($id_order, 0, 0, $dropshipping_envio_almacen, $pedido_dropshipping);
    
    if ($productos_pedido == 'dropshipping') {
        //el pedido era dropshipping y no devuelve productos, probablemente todos los productos son dropshipping con entrega a cliente
        //pickpack_log            
        pickpackLog($id_order, 0, 'packing_dropshipping', 1);
        //mostramos packing, aunque estos pedidos deberían estar en enviado y no salir en el proceso de varios
        $warning_pedido_dropshipping = 1;
        $productos_pedido = null;
        require_once("../views/templates/muestrapacking.php");
    } elseif (!$productos_pedido) {
        //no encuentra productos y no era dropshipping
        muestraError('packing', 'No pude encontrar los productos del pedido');   
        return;      
    } else {
        //tenemos que comprobar si en el pedido hay cajas sorpresa para sacar los productos. Si el campo de producto 'customizable' es distinto de 0 y el id_product es 5344. Enviamos $productos_pedido a otra función en herramientas.php que primero comprueba si hay cajas, y si las hay, dependiendo de si es picking (mostrará todos los productos seguidos) o packing (deben salir cada producto "dentro" de su caja) devolverá $productos_pedido en un formato u otro.

        //aquí solo obtenemos los id de pedido que contiene productos de caja sorpresa si lo hubiera, si no checkCajas devuelve 0
        $ids_orders_caja_sorpresa = checkCajas($productos_pedido , 'obtener_ids_cajas', $id_order, $id_cart);

        //ahora si, miramos si tiene cajas para recoger los productos
        //si el pedido base no tiene cajas sopresa devuelve $productos_pedido como se le ha enviado, pero si tiene, devuelve los productos del pedido original más los de los pedidos que contienen los productos de caja sorpresa, ordenados para packing (por todos juntos los de cada caja y ordenados)
        $productos_pedido = checkCajas($productos_pedido , 'packing', $id_order, $id_cart);
                    
        //si vuelve 0 es que había cajas pero hay errores
        if (!$productos_pedido) {
            muestraError('packing', 'Error con la/s caja/s sorpresa del pedido. No han sido creadas o no se encuentran.');                
            return;
        } else {
            //tenemos en $ids_orders_caja_sorpresa los id_order de cada pedido que contiene los productos de las cajas
            
            //si hay cajas queremos sacar los mensajes de cliente para la caja y marcar packing comenzado a cada pedido de productos de caja
            if (count($ids_orders_caja_sorpresa) > 0) {
                $mensajes_cliente_cajas = mensajesCajas($ids_orders_caja_sorpresa, $id_order);
                comenzadoPackingCajas($id_order, $ids_orders_caja_sorpresa);                  
            }  
            
            //para el pedido base
            $sql_comenzado_packing = 'SELECT comenzado_packing FROM lafrips_pick_pack WHERE id_pickpack_order = '.$id_order;
            $comenzado_packing = Db::getInstance()->ExecuteS($sql_comenzado_packing);
            //si comenzado_packing es 0 marcamos inicio packing
            if (!$comenzado_packing[0]['comenzado_packing']){
                //log en comenzadoPacking()
                comenzadoPacking($id_order);                
            } else {
                //solo log, abrir pero no primera vez
                //pickpack_log            
                pickpackLog($id_order, 0, 'packing', 1);
            }
            
            require_once("../views/templates/muestrapacking.php");
        }                    
        
    }

}

function mensajesOrder($id_order) {
    // Sacamos los mensajes del pedido.         
    // Mensajes de "Pedido manual" si lo hay por ser pedido creado a mano por empleado, este solo se encuentra en lafrips_message
    $sql_mensajes_pedido_manual = "SELECT date_add, message FROM lafrips_message WHERE id_order = ".$id_order." AND message LIKE '%Pedido manual --%';";
    $mensajes_pedido_manual = Db::getInstance()->ExecuteS($sql_mensajes_pedido_manual);        

    // Mensajes sobre pedido de clientes y empleados. Son tanto el mensaje que puede dejar el cliente en el carro de compra como los posteriores que puede dejar sobre el pedido en el área de cliente/pedidos y además los mensajes tanto privados como públicos que dejan los empleados sobre el pedido dentro de la ficha de pedido.
    $sql_mensajes_sobre_pedido = "SELECT cum.id_employee AS id_empleado, CONCAT(emp.firstname,' ',emp.lastname) AS nombre_empleado, cum.message AS mensaje, cum.private AS privado, cum.date_add AS fecha, CONCAT(cus.firstname,' ',cus.lastname) AS nombre_cliente
    FROM lafrips_customer_message cum
    JOIN lafrips_customer_thread cut ON cut.id_customer_thread = cum.id_customer_thread
    LEFT JOIN lafrips_customer cus ON cus.id_customer = cut.id_customer
    LEFT JOIN lafrips_employee emp ON emp.id_employee = cum.id_employee
    WHERE cut.id_order = ".$id_order."
    ORDER BY cum.date_add DESC;";
    $mensajes_sobre_pedido = Db::getInstance()->ExecuteS($sql_mensajes_sobre_pedido);
    
    //vamos a crear un array key=>value que contenga todos los mensajes que haya que mostrar sobre el pedido (no sobre cliente o mensaje de envoltorio regalo). Key será la parte que contiene la fecha y persona que creó el mensaje y value el mensaje en si. Primero sacamos si existe mensaje de pedido manual para que vaya el primero (quedaría más abajo) y después el resto por fecha de creación
    $todos_mensajes_pedido = array();

    //si hay mensaje de pedido manual:
    if($mensajes_pedido_manual){
        $fecha = date_create($mensajes_pedido_manual[0]['date_add']); 
        $fecha = date_format($fecha, 'd-m-Y H:i:s');
        $todos_mensajes_pedido[$fecha] = $mensajes_pedido_manual[0]['message'];
    }

    //si hay otros mensajes los vamos introduciendo al array
    if($mensajes_sobre_pedido){
        foreach ($mensajes_sobre_pedido AS $mensaje){
            //primero montamos la cabecera del mensaje, fecha con creador de mensaje
            $fecha = date_create($mensaje['fecha']); 
            $fecha = date_format($fecha, 'd-m-Y H:i:s');
            //si el id de empleado es 0 es un mensaje del cliente
            if ($mensaje['id_empleado'] != 0){
                $mensajeador = $mensaje['nombre_empleado'];
            } else {
                $mensajeador = 'CLIENTE: '.$mensaje['nombre_cliente'];
            }
            //si el mensaje es privado lo indicamos
            if ($mensaje['privado'] != 0){
                $privado = '<b>PRIVADO</b>';
            } else {
                $privado = '';
            }
            $cabecera = $fecha.'<b> '.$mensajeador.'</b>  '.$privado;

            //ahora sacamos el mensaje
            $mensaje = $mensaje['mensaje'];

            //introducimos todo al array de mensajes
            $todos_mensajes_pedido[$cabecera] = $mensaje;
        }           

    }

    return $todos_mensajes_pedido;

}

//función que recibe los ids de pedidos caja sorpresa y devuelve el/los mensajes del cliente para crear la caja
function mensajesCajas($ids_orders_caja_sorpresa, $id_order) {
    $mensaje_caja = '';
    $mensajes_cliente_cajas = array();
    //buscamos los mensajes de cada caja y montamos $mensajes_cajas
    foreach ($ids_orders_caja_sorpresa AS $id_pedido_caja_sorpresa) {
        if ($id_pedido_caja_sorpresa != $id_order){
            $mensaje_caja = mensajeCaja($id_pedido_caja_sorpresa);
            if ($mensaje_caja == 'error') {
                $error_cajas = 1;                                    
            } else {
                //montamos el mensaje
                $mensajes_cliente_cajas[$id_pedido_caja_sorpresa] = $mensaje_caja;
            }
        }
    }

    return $mensajes_cliente_cajas;
}

//función que recibe id_order y marca el pedido como comenzado packing (utilizado para estadísticas). El pedido puede ser base, o de caja sorpresa, en cuyo caso enviaremos el comentario a añadir como parámetro.  Cada pedido solo puede pasar por aquí una vez, la primera que se abre packing
function comenzadoPacking($id_order, $comentario_packing_caja = '') {   
    $sql_update_comenzado_packing = 'UPDATE lafrips_pick_pack
    SET
    id_estado_order = 8,
    comenzado_packing = 1, 
    comenzado = 1,
    id_employee_packing = '.$_SESSION["id_empleado"].',
    nombre_employee_packing = "'.$_SESSION["nombre_empleado"].'",
    '.$comentario_packing_caja.' 
    date_inicio_packing = NOW(),
    date_upd = NOW()
    WHERE id_pickpack_order = '.$id_order.';';
    Db::getInstance()->execute($sql_update_comenzado_packing);    

    //pickpack_log  
    //si es caja lo indicamos en log
    $caja = 0;
    if ($comentario_packing_caja != '') {
        $caja = 1;
    }          
    pickpackLog($id_order, $caja, 'packing', 1, 1);
}

//función que recibe el/los id de pedido que contienen los productos de las cajas sorpresa, crea comentario y llama a comenzadoPacking para marcarlos como comenzados
function comenzadoPackingCajas($id_order, $ids_orders_caja_sorpresa) { 
    foreach ($ids_orders_caja_sorpresa AS $id_caja) {

        $comentario_caja = '- Packing comenzado para productos de Caja correspondiente a pedido padre '.$id_order.'<span style=\"font-size:80%;\"><i>  '.$_SESSION["nombre_empleado"].' </i>';  
        $comentario_packing_caja = 'comentario_packing = CONCAT(comentario_packing,"'.$comentario_caja.'",DATE_FORMAT(NOW(),"%d-%m-%Y %H:%i:%s"),"</span><br>"),';

        //comprobamos si ya está marcado como comenzado packing
        $sql_comenzado_packing = 'SELECT comenzado_packing FROM lafrips_pick_pack WHERE id_pickpack_order = '.$id_caja;
        $comenzado_packing = Db::getInstance()->ExecuteS($sql_comenzado_packing);
        //si comenzado_packing es 0 marcamos inicio packing
        if (!$comenzado_packing[0]['comenzado_packing']){
            //pickpack_log lo hacemos en comenzadoPacking() 

            comenzadoPacking($id_caja, $comentario_packing_caja); 
        } else {
            //solo log, abrir pero no primera vez
            //pickpack_log            
            pickpackLog($id_caja, 1, 'packing', 1);
        }           
    }
}

//función que recibe el POST después de cerrar packing con submit_pedido y lo procesa para marcar cada producto como finalizado o incidencia. Devuelve $incidencia, que será 0 si no hubo (todos los productos marcados ok) y 1 si hubo (algún producto marcado no ok)
function procesaProductos() {
    $numero_productos = 0;

    if ($_POST['numero_productos']){
        $numero_productos = $_POST['numero_productos'];
    }
    //ponemos un marcador $incidencia que coja valor 1 si alguno de los productos ha sido marcado con la opción de no ok del radio button 
    $incidencia = 0;
    //sacamos los id_product y id_product_atribute e id_order de cada producto. Como $_POST contiene los valores del formulario en el orden de este, saco el contenido desde el primero hasta $numero_productos y los almaceno en tres variables, $id_product y $id_product_atribute e $id_order después de separar la cadena que llega (tipo 12323_2345_123456) $id_order indica a qué pedido corresponde el producto. Si hay cajas sorpresa y el id no corresponde al pedido "base", pertenecerá al pedido virtual que contiene los productos de una caja sorpresa
    $contador = 1;
    foreach($_POST as $key => $value) {
        if ($contador <= $numero_productos){
            //$key contiene el id unido al id atributo con barra baja
            $ids = explode('_', $key);
            $id_product = $ids[0];
            $id_product_attribute = $ids[1];
            $id_pedido_producto = $ids[2];
            //marcaremos con uno el producto como incidencia_packing para saber cual estuvo mal en el packing. $incidencia nos señala que el pedido en general ha tenido incidencia. Si el producto pasa una vez como incidencia se marca, pero nunca se vuelve a poner a 0 si se corrige     
            $incidencia_producto = ''; 
            //$value contiene 1 o 0 si el radio button estaba en ok o no
            $correcto = $value;
            if (!$correcto){
                $incidencia = 1;
                $incidencia_producto = ' ,incidencia_packing = 1 ';
            } else {            
                $incidencia_producto = ' ';
            }
            //Creamos la sql para hacer update de los productos en packing comprobando producto a producto si está en lafrips_pick_pack_productos (si el update da resultado) y si no lo está lo metemos con un insert, ya que puede darse el caso de que se cambie o añada algún producto si hay incidencia.
            //como Db::getInstance()->Execute() devuelve tru o false si la ejecución es correcta, pero eso quiere decir simplemente que no ha habido error, es decir, si no encuentra la línea para hacer update pero la sql es correcta, devuelve true también, para saber si el producto no está y hay que hacer insert, primero tenemos que buscar el producto. Si está hacemos update y si no insert
            $sql_busca_pickpack_product = 'SELECT id_pickpack_productos FROM lafrips_pick_pack_productos            
                WHERE id_product = '.$id_product.'
                AND id_product_attribute = '.$id_product_attribute.'
                AND id_pickpack_order = '.$id_pedido_producto;
            $id_pickpack_productos = Db::getInstance()->getValue($sql_busca_pickpack_product, $use_cache = true);

            if ($id_pickpack_productos){
                $sql_update_producto = 'UPDATE lafrips_pick_pack_productos 
                    SET ok_packing = '.$correcto.$incidencia_producto.'
                    WHERE id_pickpack_productos = '.$id_pickpack_productos;

                Db::getInstance()->Execute($sql_update_producto);
                
            } else {
                //si no se hace el update, hacemos insert del producto
                if (!$correcto){
                    $incidencia_prod = ', incidencia_packing';
                    $incidencia_producto = ' ,1 ';
                }else{
                    $incidencia_prod = '';
                    $incidencia_producto = '';
                }
                $sql_insert_pickpack_producto = "INSERT INTO lafrips_pick_pack_productos (id_pickpack_order, id_product, id_product_attribute, ok_packing ".$incidencia_prod.") VALUES (".$id_pedido_producto." ,".$id_product." ,".$id_product_attribute." ,".$correcto.$incidencia_producto.")";
                
                Db::getInstance()->Execute($sql_insert_pickpack_producto);
                
            }

            //Si algún producto que estaba en un picking anterior ya no se recibe en este picking, pondremos a 1 "eliminado". SIN HACERRRRR!!        

            $contador++;           
        }      
    }

    return $incidencia;
}

//función que finalizado el packing recibe un id de pedido y su tipo, caja o pedido normal. Si el pedido es caja debería marcarse como entregado y si es pedido principal o normal, como Enviado, o entregado, dependiendo de ciertos parámetros. 
//Si el packing se cierra sin incidencia enviamos los datos a la tabla frik_cambio_enviado para que se realice el proceso de cambio de estado a Enviado de forma asincrona, y si es GLS el envío de email de seguimiento.
//primero nos aseguramos de que los pedidos no están en estado Enviado, Cancelado, Entregado, Error pago. Si el estado es virtual se considera un pedido con productos para caja virtual y se pasará a entregado  
//01/03/2021 Añadimos para que ignore los pedidos de ClickCanarias, estados "Pendiente envío fuera península." y "Pagado con ClickCanarias" 
//01/12/2022 Para que los pedidos de Canarias en estado Pendiente envío fuera península no muestren error al finalizar el packing, el return en caso de estar en ese estado será true, pero sin insertar en la tabla cambio_enviado
function cambioEstado($tipo, $id_order) {
    if ($tipo == 'pedido') {
        //primero nos aseguramos de que los pedidos no están en estado Enviado, Cancelado, Entregado, Error pago. Si el estado es virtual se considera un pedido con productos para cja virtual y se pasará a entregado  
        //01/03/2021 Añadimos para que ignore los pedidos de ClickCanarias, estados "Pendiente envío fuera península." y "Pagado con ClickCanarias"    
        $id_estado_enviado = (int)Configuration::get(PS_OS_SHIPPING);
        $id_estado_entregado = (int)Configuration::get(PS_OS_DELIVERED);
        $id_estado_cancelado = (int)Configuration::get(PS_OS_CANCELED);
        $id_estado_errorpago = (int)Configuration::get(PS_OS_ERROR);
        $id_estado_canarias = (int)Configuration::get(CLICKCANARIAS_STATE);

        //sacamos id_status de Pedido virtual
        $sql_id_pedido_virtual = "SELECT ost.id_order_state as id_pedido_virtual
        FROM lafrips_order_state ost
        JOIN lafrips_order_state_lang osl ON osl.id_order_state = ost.id_order_state AND osl.id_lang = 1
        WHERE osl.name = 'Pedido Virtual'
        AND ost.deleted = 0";
        $id_pedido_virtual = (int)Db::getInstance()->executeS($sql_id_pedido_virtual)[0]['id_pedido_virtual']; 

        //sacamos id_status de Pendiente envío fuera península.
        $sql_id_pendiente_fuera = "SELECT ost.id_order_state as id_pendiente_fuera
        FROM lafrips_order_state ost
        JOIN lafrips_order_state_lang osl ON osl.id_order_state = ost.id_order_state AND osl.id_lang = 1
        WHERE osl.name = 'Pendiente envío fuera península.'
        AND ost.deleted = 0";
        $id_pendiente_fuera = (int)Db::getInstance()->executeS($sql_id_pendiente_fuera)[0]['id_pendiente_fuera'];

        //instanciamos pedido
        $order = new Order($id_order);
        $current_state = (int)$order->current_state;

        //si el estado actual no es Enviado, Cancelado, Entregado, Error pago, canarias, o si es virtual enviamos a la tabla. Si es virtual lo pasaremos a entregado
        //01/12/2022 Para que los pedidos de Canarias en estado Pendiente envío fuera península no muestren error al finalizar el packing, el return en caso de estar en ese estado será true, pero sin insertar en la tabla cambio_enviado, con lo que no se mostrará error. Añado elseif  ($current_state == $id_pendiente_fuera).
        if (($current_state !== $id_estado_enviado) && ($current_state !== $id_estado_entregado) && ($current_state !== $id_estado_cancelado) && ($current_state !== $id_estado_errorpago) && ($current_state !== $id_estado_canarias) && ($current_state !== $id_pendiente_fuera) || ($current_state == $id_pedido_virtual)) {
            if ($current_state == $id_pedido_virtual) {
                $cambio = 'entregado';
            } else {
                $cambio = 'enviado';
            }

            if (insertCambioEnviado($id_order, $cambio, $current_state)) {
                return array(true);
            } else {
                return array(false, '<br>No pudo hacerse la inserción en tabla de cambios de estado para pedido '.$id_order);
            }
        } elseif ($current_state == $id_pendiente_fuera) {
            //cuando van a Canarias el cambio de estado  aEnviado es manual,  de modo que no queremosinsertar en cambio de estado pero tampoco queremos mostrar error en packing
            return array(true);
        } else {
            return array(false, '<br>Pedido '.$id_order.' en estado '.$order->getCurrentStateFull(1)['name']);
        }


    } elseif ($tipo == 'caja') {
        //comprobamos que la caja está en estado Pedido Virtual y si es así lo marcamos para cambiar Estado de prestashop a Entregado
        //sacamos id_status de Pedido virtual
        $sql_id_pedido_virtual = "SELECT ost.id_order_state as id_pedido_virtual
        FROM lafrips_order_state ost
        JOIN lafrips_order_state_lang osl ON osl.id_order_state = ost.id_order_state AND osl.id_lang = 1
        WHERE osl.name = 'Pedido Virtual'
        AND ost.deleted = 0";
        $id_pedido_virtual = (int)Db::getInstance()->executeS($sql_id_pedido_virtual)[0]['id_pedido_virtual']; 

        //instanciamos pedido de caja
        $order_caja = new Order($id_order);
        $current_state_caja = (int)$order_caja->current_state; 
        //si el estado del pedido caja no es virtual, no entramos al if y no lo cambiamos
        if ($current_state_caja == $id_pedido_virtual) {            
            if (insertCambioEnviado($id_order, 'entregado', $current_state_caja)) {
                return array(true);
            } else {
                return array(false, '<br>No pudo hacerse la inserción en tabla de cambios de estado para pedido de caja '.$id_order);
            }    
        } else {
            return array(false, '<br>Pedido de caja '.$id_order.' en estado '.$order_caja->getCurrentStateFull(1)['name']);
        }
    }
}

//función que hace el insert a la tabla frik_cambio_enviado del pedido para su posterior cambio de estado con tarea cron. $cambio contiene el estado al que se debe cambiar el pedido, Enviado o entregado
function insertCambioEnviado($id_order, $cambio, $current_state) {
    //usamos esta sql para asegurarnos de no insertar duplicados de un order si por lo que sea se cierra un packing varias veces
    $insert_frik_cambio_estado = "INSERT INTO frik_cambio_enviado 
    (cambio, id_order, id_order_status, id_empleado, date_add) 
    SELECT '".$cambio."',
    ".$id_order." ,
    ".$current_state." ,				
    ".$_SESSION['id_empleado']." ,             
    NOW()
    FROM dual
    WHERE NOT EXISTS (SELECT id_order FROM frik_cambio_enviado WHERE id_order = ".$id_order.")LIMIT 1";

    Db::getInstance()->Execute($insert_frik_cambio_estado);

    return true;

    // if (Db::getInstance()->Execute($insert_frik_cambio_estado)) {
    //     return true;
    // }

    // return false;

}


?>