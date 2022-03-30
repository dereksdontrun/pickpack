<?php

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

            //con los datos del empleado en sesión, mostramos el formulario de búsqueda de pedido. Como vamos a usar el mismo formulario para picking y packing, enviamos una variable que indica el proceso, para modificar el destino del formulario y los estilos, color etc
            $action = 'packing';
            require_once("../views/templates/buscapedido.php");

        } else {
            //no se ha encontrado el nombre de usuario, queremos que vuelva de la pantalla de error al login
            $error_tracking = 'No encuentro el usuario en el sistema';
            $action = 'login';
            require_once("../views/templates/error.php");
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

        $busqueda = obtenerIdOrder($pedido);
    } else {
        //no se ha introducido nada en el formulario
        $error_tracking = 'Debes introducir algo para buscar en el formulario, aquí no somos adivinos';
        $action = 'packing';
        require_once("../views/templates/error.php");
    }

    //obtenerIdOrder devuelve un array con el id_order en primer lugar y el error en segundo. Si devuelve id_order como 0, es que hay error, llamamos a error.php
    if (!$busqueda[0]) {
        //enviamos la variable que contiene la descripción del error y la acción que estabamos haciendo
        $error_tracking = $busqueda[1];
        $action = 'packing';
        require_once("../views/templates/error.php");

    } else {
        //devuelve un id_order correcto, pasamos a procesar el packing
        $pedido = $busqueda[0];

        //llamamos a función que busca los detalles del pedido
        $info_pedido = infoOrder($pedido);
        if (!$info_pedido) {
            $action = 'packing';
            $error_tracking = 'No pude encontrar el pedido';
            require_once("../views/templates/error.php");
        } else {
            //sacamos la info para el packing y para buscar los mensajes de cliente
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
            $nota_sobre_cliente = $info_pedido[0]['nota_sobre_cliente'];
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

            // Número de pedidos del cliente
            $sql_numero_pedidos = "SELECT COUNT(id_order) AS num_pedidos FROM lafrips_orders WHERE id_customer = ".$id_cliente." AND valid = 1;";
            $numero_pedidos = Db::getInstance()->ExecuteS($sql_numero_pedidos);
            $numero_pedidos = $numero_pedidos[0]['num_pedidos'];  

            // Sacamos los mensajes del pedido.         
            // Mensajes de "Pedido manual" si lo hay por ser pedido creado a mano por empleado, este solo se encuentra en lafrips_message
            $sql_mensajes_pedido_manual = "SELECT date_add, message FROM lafrips_message WHERE id_order = ".$pedido." AND message LIKE '%Pedido manual --%';";
            $mensajes_pedido_manual = Db::getInstance()->ExecuteS($sql_mensajes_pedido_manual);        

            // Mensajes sobre pedido de clientes y empleados. Son tanto el mensaje que puede dejar el cliente en el carro de compra como los posteriores que puede dejar sobre el pedido en el área de cliente/pedidos y además los mensajes tanto privados como públicos que dejan los empleados sobre el pedido dentro de la ficha de pedido.
            $sql_mensajes_sobre_pedido = "SELECT cum.id_employee AS id_empleado, CONCAT(emp.firstname,' ',emp.lastname) AS nombre_empleado, cum.message AS mensaje, cum.private AS privado, cum.date_add AS fecha
            FROM lafrips_customer_message cum
            JOIN lafrips_customer_thread cut ON cut.id_customer_thread = cum.id_customer_thread
            LEFT JOIN lafrips_employee emp ON emp.id_employee = cum.id_employee
            WHERE cut.id_order = ".$pedido."
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
                        $mensajeador = 'CLIENTE: '.$nombre_cliente;
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

            //sacamos la persona que hizo el picking
            $sql_empleado_picking = 'SELECT nombre_employee_picking FROM lafrips_pick_pack WHERE id_pickpack_order = '.$pedido;  
            $empleado_picking = Db::getInstance()->getValue($sql_empleado_picking);

            //sacamos los comentarios de picking
            $sql_comentario_picking = 'SELECT comentario_picking FROM lafrips_pick_pack WHERE id_pickpack_order = '.$pedido;  
            $comentario_picking = Db::getInstance()->getValue($sql_comentario_picking); 

            //info de productos en pedido
            $productos_pedido = infoProducts($pedido, 0, 0);
            if (!$productos_pedido) {
                $action = 'packing';
                $error_tracking = 'No pude encontrar los productos del pedido';
                require_once("../views/templates/error.php");
            } else {
                //tenemos que comprobar si en el pedido hay cajas sorpresa para sacar los productos. Si el campo de producto 'customizable' es distinto de 0 y el id_product es 5344. Enviamos $productos_pedido a otra función en herramientas.php que primero comprueba si hay cajas, y si las hay, dependiendo de si es picking (mostrará todos los productos seguidos) o packing (deben salir cada producto "dentro" de su caja) devolverá $productos_pedido en un formato u otro.
                $productos_pedido = checkCajas($productos_pedido , 'packing', $pedido, $id_cart);
                //var_dump($productos_pedido);   
                $error_cajas = 0;             
                //si vuelve 0 es que había cajas pero hay errores
                if (!$productos_pedido) {
                    $error_cajas = 1;                    
                } else {
                    //queremos comprobar si el pedido "padre" contenía cajas sorpresa, esto sucederá si en el array de productos que hemos obtenido en $productos_pedido se contienen diferentes id_order para los productos. Si hay cajas queremos mostrar al principio del packing los comentarios del cliente para cada caja, señalando el id_order para asociarloa los productos mientras se hace el packing. array_column permite buscar en un multiarray los campos con el nombre id_order, haremos array_unique y si queda un solo dato, es que no hay caja, si quedan más hay diferentes id_order y los que no coincidan con $pedido serán los de los pedidos de las cajas. Buscaremos en frik_cajas_sorpresa los id_customization para esas cajas y con ello en lafrips_customized_data sacaremos los comentarios de cliente 

                    //23/11/2020 En picking esto de los id de los pedidos caja lo he hecho medainte modificación de la función chekCajas, es lo que pasa por modificar cada cosa un día...
                    $ids_diferentes = array_unique(array_column($productos_pedido, 'id_order'));
                    //print_r($idsorders);
                    if (count($ids_diferentes) > 1) {
                        $mensaje_caja = '';
                        $mensajes_cliente_cajas = array();
                        //hay más de un id_order, buscamos los mensajes de cada caja y montamos $mensajes_cajas
                        foreach ($ids_diferentes AS $id_pedido_caja_sorpresa) {
                            if ($id_pedido_caja_sorpresa != $pedido){
                                $mensaje_caja = mensajeCaja($id_pedido_caja_sorpresa);
                                if ($mensaje_caja == 'error') {
                                    $error_cajas = 1;                                    
                                } else {
                                    //montamos el mensaje
                                    $mensajes_cliente_cajas[$id_pedido_caja_sorpresa] = $mensaje_caja;
                                }
                            }
                        }
                    }
                                        
                }

                if ($error_cajas) {
                    $action = 'packing';
                    $error_tracking = 'Error con la/s caja/s sorpresa del pedido. No han sido creadas o no se encuentran.';
                    require_once("../views/templates/error.php");
                } else {
                    //en este punto debemos tener la info del pedido, del cliente y sus mensajes y de los productos en el pedido, comprobamos si es el primer packing del pedido y llamamos a la vista para mostrar el packing
                    //para almacenar la fecha de inicio de packing, cuando se muestra el pedido hacemos una búsqueda en lafrips_pick_pack. Si comenzado_packing es 0 es que es el primer packing, lo ponemos a 1 y guardamos date now() en date_inicio_packing. Si hay incidencias y se repiten los packing será un dato inválido, la duración del packing solo sirve si es un pedido sin incidencias. También cambiamos el estado de pickpack a Packing Abierto

                    //23/11/2020 Si en el pedido hay cajas sorpresa, en el array $ids_diferentes están los ids de lo/s pedido/s que las contienen. Les marcamos también comenzado_packing etc si necesario.
                    if (count($ids_diferentes) > 1) {
                        foreach ($ids_diferentes AS $id_caja) {
                            if ($id_caja != $pedido){
                                //el id NO es el del pedido padre, es de caja
                                $comentario_caja = '- Packing comenzado para productos de Caja correspondiente a pedido padre '.$pedido.'<span style=\"font-size:80%;\"><i>  '.$_SESSION["nombre_empleado"].' </i>';  
                                $comentario_packing_caja = 'comentario_packing = CONCAT(comentario_packing,"'.$comentario_caja.'",DATE_FORMAT(NOW(),"%d-%m-%Y %H:%i:%s"),"</span><br>"),';


                                $sql_comenzado_packing = 'SELECT comenzado_packing FROM lafrips_pick_pack WHERE id_pickpack_order = '.$id_caja;
                                $comenzado_packing = Db::getInstance()->ExecuteS($sql_comenzado_packing);
                                //si comenzado_packing es 0 marcamos inicio packing
                                if (!$comenzado_packing[0]['comenzado_packing']){
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
                                    WHERE id_pickpack_order = '.$id_caja.';';
                                    Db::getInstance()->execute($sql_update_comenzado_packing);
                                }
                            }
                            
                        }
                    }

                    $sql_comenzado_packing = 'SELECT comenzado_packing FROM lafrips_pick_pack WHERE id_pickpack_order = '.$pedido;
                    $comenzado_packing = Db::getInstance()->ExecuteS($sql_comenzado_packing);
                    //si comenzado_packing es 0 marcamos inicio packing
                    if (!$comenzado_packing[0]['comenzado_packing']){
                        $sql_update_comenzado_packing = 'UPDATE lafrips_pick_pack
                        SET
                        id_estado_order = 8,
                        comenzado_packing = 1,      
                        comenzado = 1,
                        id_employee_packing = '.$_SESSION["id_empleado"].',
                        nombre_employee_packing = "'.$_SESSION["nombre_empleado"].'",
                        date_inicio_packing = NOW(),
                        date_upd = NOW()
                        WHERE id_pickpack_order = '.$pedido.';';

                        Db::getInstance()->execute($sql_update_comenzado_packing);
                    }
                    
                    require_once("../views/templates/muestrapacking.php");
                }                    
                
            }

        }
        
    }    

} elseif (isset($_POST['submit_finpacking'])) {
    //procesamos el formulario del packing. 
    //sacamos id y nombre de empleado de la sesión
    $id_empleado = $_SESSION["id_empleado"];
    $nombre_empleado = $_SESSION["nombre_empleado"];
    if ($_POST['id_pedido']){
        $id_pedido = $_POST['id_pedido'];
    }
    //sacamos los ids de pedidos cajas si los hay, es un array que vendría serializado por POST
    if ($_POST['ids_cajas']){
        $ids_cajas = unserialize($_POST['ids_cajas']);
    }
    //para no sobreescribir el comentario, lo añadiremos al anterior si fuera la segunda vez o más que pasamos por aquí o no insertaremos nada si no llega ninguno. Añadimos perosna que lo escribe y fecha
    if ($_POST['comentario'] && $_POST['comentario'] !==''){
        $comentario = $_POST['comentario'];
        $comentario = '- '.$comentario.'<span style=\"font-size:80%;\"><i>  '.$nombre_empleado.' </i>';      
        $comentario_packing = 'comentario_packing = CONCAT(comentario_packing,"'.$comentario.'",DATE_FORMAT(NOW(),"%d-%m-%Y %H:%i:%s"),"</span><br>"),';
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
            //marcaremos con uno el producto como incidencia_picking para saber cual estuvo mal en el picking. $incidencia nos señala que el pedido en general ha tenido incidencia. Si el producto pasa una vez como incidencia se marca, pero nunca se vuelve a poner a 0 si se corrige     
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

    $error_tracking = '';

    $id_pickpack = PickPackOrder::getIdPickPackByIdOrder($id_pedido);
    if ($incidencia){
        //si hay incidencia ponemos o dejamos el estado Incidencia Packing, y añadimos incidencia_packing=1
        $id_estado_order = 5;
        $date_fin_packing = '"0000-00-00 00:00:00"';
        $finalizado = 0;
        $incidencia_packing = ' incidencia_packing = 1 ,';
    } else {
        //si no hay incidencia ponemos el estado Packing Finalizado y metemos la fecha
        $id_estado_order = 6;
        // $date_fin_packing = date("Y-m-d H:i:s");
        $date_fin_packing = 'NOW()';
        $finalizado = 1;
        $incidencia_packing = '';

        // 09/02/2021 Vamos a comprobar si el pedido está en Etiquetado GLS o en Etiquetado Correos. Si es así y el packing es finalizado correctamente, lo vamos a pasar a Enviado, y si es GLS enviar al cliente el email con el seguimiento.
        //sacamos id_status de Etiquetado GLS
        $sql_id_etiquetado_gls = "SELECT ost.id_order_state as id_etiquetado_gls
        FROM lafrips_order_state ost
        JOIN lafrips_order_state_lang osl ON osl.id_order_state = ost.id_order_state AND osl.id_lang = 1
        WHERE osl.name = 'Etiquetado GLS'
        AND ost.deleted = 0";
        $id_etiquetado_gls = Db::getInstance()->executeS($sql_id_etiquetado_gls)[0]['id_etiquetado_gls']; 

        //sacamos id_status de Etiquetado Correos
        $sql_id_etiquetado_correos = "SELECT ost.id_order_state as id_etiquetado_correos
        FROM lafrips_order_state ost
        JOIN lafrips_order_state_lang osl ON osl.id_order_state = ost.id_order_state AND osl.id_lang = 1
        WHERE osl.name = 'Etiquetado Correos'
        AND ost.deleted = 0";
        $id_etiquetado_correos = Db::getInstance()->executeS($sql_id_etiquetado_correos)[0]['id_etiquetado_correos'];

        //sacamos estado actual del pedido
        $order = new Order($id_pedido);
        $current_state = $order->current_state;

        //si el estado actual es Etiquetado GLS o Correos seguimos
        if (($id_etiquetado_gls == $current_state) || ($id_etiquetado_correos == $current_state)) {
            //son iguales, ponemos un mensaje en el pedido indicando el cambio de estado por fin de packing y envio y cambiamos estado a Enviado
            if ($id_etiquetado_gls == $current_state) {
                $transporte = 'GLS';
            } else {
                $transporte = 'Correos';
            }
            //generamos mensaje para pedido
            $fecha = date("d-m-Y H:i:s");
            $mensaje_pedido_cambio_enviado = 'Estado de pedido cambiado a Enviado después de Packing Finalizado desde Etiquetado '.$transporte.'
            por '.$nombre_empleado.' el '.$fecha;

            //luego generamos un nuevo customer_thread, puede existir uno para este cliente y pedido pero lo comprobamos 
            $id_customer = $order->id_customer; 
            $customer = new Customer($id_customer);  
            $customer_email = $customer->email;              
            //si existe ya un customer_thread para este pedido lo sacamos
            $id_customer_thread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($customer_email, $id_pedido);            

            if ($id_customer_thread) {
                //si ya existiera lo instanciamos para tener los datos para el mensaje y el envío de email
                $ct = new CustomerThread($id_customer_thread);
            } else {
                //si no existe lo creamos
                $ct = new CustomerThread();
                $ct->id_shop = 1; // (int)$this->context->shop->id;
                $ct->id_lang = 1; // (int)$this->context->language->id;
                $ct->id_contact = 0; 
                $ct->id_customer = $id_customer;
                $ct->id_order = $id_pedido;
                //$ct->id_product = 0;
                $ct->status = 'open';
                $ct->email = $customer_email;
                $ct->token = Tools::passwdGen(12);  // hay que generar un token para el hilo
                $ct->add();
            }           
            
            //si hay id de customer_thread continuamos
            if ($ct->id){
                //un mensaje interno para que aparezca la fecha de cambio de esatdo por fin packing y el empleado 
                $cm_interno = new CustomerMessage();
                $cm_interno->id_customer_thread = $ct->id;
                $cm_interno->id_employee = $id_empleado; 
                $cm_interno->message = $mensaje_pedido_cambio_enviado;
                $cm_interno->private = 1;                
                $cm_interno->add();
            }  

            //con esto podemos generar una instancia del módulo (buscándolo por su nombre) y con ella llamar a una función en la clase raíz del módulo. En este caso buscamos el módulo pickpack, clase pickpack.php, función cambiaEstadoEnviado() y le enviamos el id de pedido. Pero si se puede vamos a generar context aquí (ya que allí tampoco tiene al llamarse desde aquí que no hay) y hacer el cambio de estado
            // $pickpack_module = Module::getInstanceByName('pickpack');
            // // var_dump($pickpack_module);
            // $pickpack_module->cambiaEstadoEnviado($id_pedido);

            // return;

            // VERY IMPORTANTE ////////////////////
            ///////////////////////////////////////
            //como aquí no tenemos context ya que la parte de picking y packing esta "fuera" de Prestashop, sacamos la cookie con la que podemos obtener el id de empleado logado, en cookie->id_employee
            $cookie = new Cookie('psAdmin', '', (int)Configuration::get('PS_COOKIE_LIFETIME_BO'));
            //comprobamos que haya id_employee e instanciamos el empleado
            if ($cookie->id_employee) {
                $employee_desde_cookie = new Employee($cookie->id_employee);
            } else {
                //si no hubiera id_employee, quizás se comienza a trabajar sin entrar en prestashop.. ponemos el de Automatizador
                $employee_desde_cookie = new Employee(44);
            }
            
            //"creamos" unnuevo context y después le asignamos como empleado el empleado instanciado
            $context = Context::getContext();
            $context->employee = $employee_desde_cookie;

            //ahora tenemos un context con employee, esto lo necesitamos porque al pasar el pedido a Enviado, en una parte del proceso dentro de OrderHistory.php,se llama a la función removeProduct dentro de StockManager.php. Esta función, cuando hay packs, entra en un if que para establecer los campos de la tabla stock_movement necesita $context->employee->id, y si aquí no tenemos context, no puede sacarlo y se rompe el proceso. De este modo hemos "creado" un context válido.
            
            // var_dump($context->employee);
            // var_dump(class_exists(‘Context’));

            $order = new Order($id_pedido);

            $id_estado_enviado = Configuration::get(PS_OS_SHIPPING);
            
            //cambiamos estado de orden a Enviado, ponemos id_employee 44 que es Automatizador, para log
            $use_existings_payment = false;
            if (!$order->hasInvoice()) {
                $use_existings_payment = true;
            }

            $history = new OrderHistory();        
            $history->id_order = $id_pedido;        
            $history->id_employee = $id_empleado;         
            $history->changeIdOrderState($id_estado_enviado, $id_pedido, $use_existing_payment); 
            $history->add(true);
            $history->save();

            
            //ahora si es GLS enviamos email con seguimiento al cliente            
            //si hay url_track enviamos email
            if ( ($transporte == 'GLS') && ($url_track = Db::getInstance()->ExecuteS('SELECT url_track FROM lafrips_gls_envios WHERE id_envio_order = '.$id_pedido))) {
                //mensaje , enlace etc parece ser si se usa la plantilla de mensaje de GLS pero usamos in_transit de Prestashop sin más
                $mensaje = '';
                $id_lang = 1;
                $usuario_nombre    = $customer->firstname;
                $usuario_apellidos = $customer->lastname;
                // $usuario_email     = $customer_email;
                //$orden_pedido      = sprintf('%06d', $id_pedido);
                $orden_pedido      = $order->reference;
                $asunto            = 'Código seguimiento del pedido num. '.$orden_pedido;
                $enlace            = '<p><a href="'.$url_track[0]['url_track'].'">Ver seguimiento</a></p>';
                $mensaje .= '<p>'.$enlace.'</p>';
                $followup = $url_track[0]['url_track'];

                if (!Mail::Send(
                    intval($id_lang),
                    'in_transit',
                    $asunto,
                    array(
                        '{meta_products}'=>$mensaje,
                        '{firstname}' => $usuario_nombre,
                        '{lastname}' => $usuario_apellidos,
                        '{order_name}' => $orden_pedido,
                        '{message}' => $mensaje,
                        '{followup}' => $followup,
                        '{email}' => $customer_email
                    ),
                    $customer_email
                )) {
                    //si no funciona el envío de email                    
                    $error_tracking .= 'Hubo un problema al enviar el email de GLS';                   
                    
                }
            }
            
            
        }

        
    }   
    
    //09/09/2020 Además, si no hay incidencia tras el packing y el pedido contenía cajas sorpresa, se pondrá como Paquete Enviado cada pedido con caja sorpresa. Los id de esos pedidos estarían en el array ids_cajas. Si hay incidencia, se marcará como para un pedido normal
    //10/09/2020 También comprobamos el estado del pedido que contiene la caja, y si es Pedido Virtual, lo cambiamos de estado a Entregado
    if (count($ids_cajas) > 0) {
        if ($incidencia){
            //si hay incidencia solo cambiamos el estado a incidencia packing poniendo la hora a 0
            foreach ($ids_cajas AS $id_caja) {
                $id_pickpack_caja = PickPackOrder::getIdPickPackByIdOrder($id_caja);
                 
                $sql_update_pickpack_pedido_caja = 'UPDATE lafrips_pick_pack
                SET
                comenzado = 1,
                id_estado_order = 5,
                id_employee_packing = '.$id_empleado.',
                nombre_employee_packing = "'.$nombre_empleado.'",                
                incidencia_packing = 1 ,           
                date_fin_packing = "0000-00-00 00:00:00",
                date_upd = NOW()
                WHERE id_pickpack = '.$id_pickpack_caja.';';    

                Db::getInstance()->execute($sql_update_pickpack_pedido_caja);
            }

        } else {
            //si no hay incidencia y se cierra el pedido, pasamos a finalizado y cambiamos esatdo de virtual a entregado si se da el caso
            //sacamos id_status de Pedido virtual
            $sql_id_pedido_virtual = "SELECT ost.id_order_state as id_pedido_virtual
            FROM lafrips_order_state ost
            JOIN lafrips_order_state_lang osl ON osl.id_order_state = ost.id_order_state AND osl.id_lang = 1
            WHERE osl.name = 'Pedido Virtual'
            AND ost.deleted = 0";
            $id_pedido_virtual = Db::getInstance()->executeS($sql_id_pedido_virtual)[0]['id_pedido_virtual']; 

            foreach ($ids_cajas AS $id_caja) {
                $id_pickpack_caja = PickPackOrder::getIdPickPackByIdOrder($id_caja);
                $comentario_caja = '- Packing finalizado para productos de Caja correspondiente a pedido padre '.$id_pedido.'<span style=\"font-size:80%;\"><i>  '.$nombre_empleado.' </i>';  
                $comentario_packing_caja = 'comentario_packing = CONCAT(comentario_packing,"'.$comentario_caja.'",DATE_FORMAT(NOW(),"%d-%m-%Y %H:%i:%s"),"</span><br>"),';

                $sql_update_pickpack_pedido_caja = 'UPDATE lafrips_pick_pack
                SET
                comenzado = 1,
                id_estado_order = 6,
                id_employee_packing = '.$id_empleado.',
                nombre_employee_packing = "'.$nombre_empleado.'",
                finalizado = 1,
                '.$comentario_packing_caja.'            
                date_fin_packing = NOW(),
                date_upd = NOW()
                WHERE id_pickpack = '.$id_pickpack_caja.';';    

                Db::getInstance()->execute($sql_update_pickpack_pedido_caja);

                //cambiamos estado de pedido. Primero sacamos su estado actual
                $sql_id_estado_pedido = 'SELECT current_state, id_customer FROM lafrips_orders WHERE id_order = '.$id_caja;
                $id_estado_pedido = Db::getInstance()->executeS($sql_id_estado_pedido)[0]['current_state'];
                $id_customer = Db::getInstance()->executeS($sql_id_estado_pedido)[0]['id_customer'];

                //si el estado del pedido caja no es virtual, no lo cambiamos
                if ($id_estado_pedido == $id_pedido_virtual) {
                    //son iguales, ponemos un mensaje en el pedido indicando el cambio de esatdo por fin de packing y cambiamos estado a Entregado
                    //generamos mensaje para pedido
                    $fecha = date("d-m-Y H:i:s");
                    $mensaje_pedido_caja_entregado = 'Estado de pedido cambiado a Entregado después de Packing Finalizado 
                    por '.$nombre_empleado.' el '.$fecha;

                    //luego generamos un nuevo customer_thread, puede existir uno para este cliente y pedido pero lo comprobamos  
                    $customer = new Customer($id_customer);                
                    //si existe ya un customer_thread para este pedido lo sacamos
                    $id_customer_thread = CustomerThread::getIdCustomerThreadByEmailAndIdOrder($customer->email, $id_caja);            

                    if ($id_customer_thread) {
                        //si ya existiera lo instanciamos para tener los datos para el mensaje y el envío de email
                        $ct = new CustomerThread($id_customer_thread);
                    } else {
                        //si no existe lo creamos
                        $ct = new CustomerThread();
                        $ct->id_shop = 1; // (int)$this->context->shop->id;
                        $ct->id_lang = 1; // (int)$this->context->language->id;
                        $ct->id_contact = 0; 
                        $ct->id_customer = $id_customer;
                        $ct->id_order = $id_caja;
                        //$ct->id_product = 0;
                        $ct->status = 'open';
                        $ct->email = $customer->email;
                        $ct->token = Tools::passwdGen(12);  // hay que generar un token para el hilo
                        $ct->add();
                    }           
                    
                    //si hay id de customer_thread continuamos
                    if ($ct->id){
                        //un mensaje interno para que aparezca la fecha de cambio de esatdo por fin packing y el empleado 
                        $cm_interno = new CustomerMessage();
                        $cm_interno->id_customer_thread = $ct->id;
                        $cm_interno->id_employee = $id_empleado; 
                        $cm_interno->message = $mensaje_pedido_caja_entregado;
                        $cm_interno->private = 1;                
                        $cm_interno->add();
                    }  

                    $id_estado_entregado = Configuration::get(PS_OS_DELIVERED);
                    //cambiamos estado de orden a Entregado, ponemos id_employee 44 que es Automatizador, para log
                    $history = new OrderHistory();
                    $history->id_order = $id_caja;
                    $history->id_employee = 44;
                    $history->changeIdOrderState($id_estado_entregado, $id_caja); 
                    $history->add(true);
                    $history->save();

                }
            }
        }


        
    }
    
    $sql_update_pickpack_pedido = 'UPDATE lafrips_pick_pack
        SET
        comenzado = 1,
        id_estado_order = '.$id_estado_order.',
        id_employee_packing = '.$id_empleado.',
        nombre_employee_packing = "'.$nombre_empleado.'",
        '.$comentario_packing.'
        regalo = '.$regalo.',
        obsequio = '.$obsequio.',
        finalizado = '.$finalizado.',
        '.$incidencia_packing.'
        date_fin_packing = '.$date_fin_packing.',
        date_upd = NOW()
        WHERE id_pickpack = '.$id_pickpack.';';

    if (Db::getInstance()->execute($sql_update_pickpack_pedido)) {
        //una vez guardado el resultado del packing correctamente, mostramos pantalla success 
        //06/10/2020 hacemos que vaya directamente al formulario del siguiente packing para no perder tiempo
        // require_once("../views/templates/packingcorrecto.php");
        // 09/02/2021 comprobamos que no haya generado error el envío de email si era GLS
        if ($error_tracking != '') {
            $action = 'packing';
            //$error_tracking .= '<br>Hubo un problema al actualizar los datos del packing';
            require_once("../views/templates/error.php");
        } else {
            $action = 'packing';
            require_once("../views/templates/buscapedido.php");
        }
        
    } else {
        $action = 'packing';
        $error_tracking .= '<br>Hubo un problema al actualizar los datos del packing';
        require_once("../views/templates/error.php");
    }
    

} elseif (isset($_POST['submit_volver'])) {
    $action = 'packing';
    require_once("../views/templates/buscapedido.php");
}


?>