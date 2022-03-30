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
            $action = 'picking';
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
    //llegamos del formulario de buscar pedido para picking
    if ($_POST['id_pedido'] && $_POST['id_pedido'] != '') {
        $pedido = $_POST['id_pedido'];

        $busqueda = obtenerIdOrder($pedido);
    } else {
        //no se ha introducido nada en el formulario
        $error_tracking = 'Debes introducir algo para buscar en el formulario, aquí no somos adivinos';
        $action = 'picking';
        require_once("../views/templates/error.php");
    }    

    //obtenerIdOrder devuelve un array con el id_order en primer lugar y el error en segundo. Si devuelve id_order como 0, es que hay error, llamamos a error.php
    if (!$busqueda[0]) {
        //enviamos la variable que contiene la descripción del error y la acción que estabamos haciendo
        $error_tracking = $busqueda[1];
        $action = 'picking';
        require_once("../views/templates/error.php");

    } else {
        //devuelve un id_order correcto, pasamos a procesar el picking
        $pedido = $busqueda[0];

        //llamamos a función que busca los detalles del pedido
        $info_pedido = infoOrder($pedido);
        if (!$info_pedido) {
            $action = 'picking';
            $error_tracking = 'No pude encontrar el pedido';
            require_once("../views/templates/error.php");
        } else {
            //sacamos la info para el picking y para buscar los mensajes de cliente
            $id_cliente = $info_pedido[0]['id_cliente'];
            $nombre_cliente = $info_pedido[0]['nombre_cliente'];
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
            // $pedido_dropshipping = $info_pedido[0]['pedido_dropshipping'];
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

            //queremos mostrar en el picking los mensajes sobre el pedido, SOLO PRIVADOS
            $sql_mensajes_sobre_pedido = "SELECT cum.id_employee AS id_empleado, CONCAT(emp.firstname,' ',emp.lastname) AS nombre_empleado, cum.message AS mensaje, cum.date_add AS fecha
            FROM lafrips_customer_message cum
            JOIN lafrips_customer_thread cut ON cut.id_customer_thread = cum.id_customer_thread
            LEFT JOIN lafrips_employee emp ON emp.id_employee = cum.id_employee
            WHERE cum.private = 1
            AND cut.id_order = ".$pedido."
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

            //info de productos en pedido
            $productos_pedido = infoProducts($pedido, 0, 0);
            if (!$productos_pedido) {
                $action = 'picking';
                $error_tracking = 'No pude encontrar los productos del pedido';
                require_once("../views/templates/error.php");
            } else {
                //tenemos que comprobar si en el pedido hay cajas sorpresa para sacar los productos. Si el campo de producto 'customizable' es distinto de 0 y el id_product es 5344. Enviamos $productos_pedido a otra función en herramientas.php que primero comprueba si hay cajas, y si las hay, dependiendo de si es picking (mostrará todos los productos seguidos) o packing (deben salir cada producto "dentro" de su caja) devolverá $productos_pedido en un formato u otro.

                //23/11/2020 Si el pedido contiene cajas sorpresa, vamos a marcar picking comenzado también en los pedidos que contienen las cajas, para ello llamamos desde aquí a checkCajas con $action = obtener_ids_cajas para recoger únicamente los ids si los hay, y modificar dichas líneas del pickpack (modificamos la función checkCajas en herramientas.php). Lo ejecutamos primero para obtener los id de las cajas y debajo para los productos
                $ids_orders_caja_sorpresa = checkCajas($productos_pedido , 'obtener_ids_cajas', $pedido, $id_cart);

                //ahora si, miramos si tiene cajas para recoger los productos
                $productos_pedido = checkCajas($productos_pedido , 'picking', $pedido, $id_cart);
                //si vuelve 0 es que había cajas pero hay errores
                if (!$productos_pedido) {
                    $action = 'picking';
                    $error_tracking = 'Error con la/s caja/s sorpresa del pedido. No han sido creadas o no se encuentran.';
                    require_once("../views/templates/error.php");
                } else {
                    //en este punto debemos tener la info del pedido, del cliente y sus mensajes y de los productos en el pedido, comprobamos si es el primer picking del pedido y llamamos a la vista para mostrar el picking
                    // para almacenar la fecha de inicio de picking, cuando se muestra el pedido hacemos una búsqueda en lafrips_pick_pack. Si comenzado_picking es 0 es que es el primer picking, lo ponemos a 1 y guardamos date now() en date_inicio_picking. Si hay incidencias y se repiten los picking será un dato inválido, la duración del picking solo sirve si es un pedido sin incidencias. También cambiamos el estado de pickpack a Picking Abierto      
                    
                    //23/11/2020 Si en el pedido hay cajas sorpresa, en el array $ids_orders_caja_sorpresa están los ids de lo/s pedido/s que las contienen. Les marcamos también comenzado_picking etc si necesario.
                    if (count($ids_orders_caja_sorpresa) > 0) {
                        foreach ($ids_orders_caja_sorpresa AS $id_caja) {

                            $comentario_caja = '- Picking comenzado para productos de Caja correspondiente a pedido padre '.$pedido.'<span style=\"font-size:80%;\"><i>  '.$_SESSION["nombre_empleado"].' </i>';  
                            $comentario_picking_caja = 'comentario_picking = CONCAT(comentario_picking,"'.$comentario_caja.'",DATE_FORMAT(NOW(),"%d-%m-%Y %H:%i:%s"),"</span><br>"),';


                            $sql_comenzado_picking = 'SELECT comenzado_picking FROM lafrips_pick_pack WHERE id_pickpack_order = '.$id_caja;
                            $comenzado_picking = Db::getInstance()->ExecuteS($sql_comenzado_picking);
                            //si comenzado_picking es 0 marcamos inicio picking
                            if (!$comenzado_picking[0]['comenzado_picking']){
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
                                WHERE id_pickpack_order = '.$id_caja.';';
                                Db::getInstance()->execute($sql_update_comenzado_picking);
                            }
                            
                        }
                    }

                    $sql_comenzado_picking = 'SELECT comenzado_picking FROM lafrips_pick_pack WHERE id_pickpack_order = '.$pedido;
                    $comenzado_picking = Db::getInstance()->ExecuteS($sql_comenzado_picking);
                    //si comenzado_picking es 0 marcamos inicio picking
                    if (!$comenzado_picking[0]['comenzado_picking']){
                        $sql_update_comenzado_picking = 'UPDATE lafrips_pick_pack
                        SET
                        id_estado_order = 2,
                        comenzado_picking = 1, 
                        comenzado = 1,
                        id_employee_picking = '.$_SESSION["id_empleado"].',
                        nombre_employee_picking = "'.$_SESSION["nombre_empleado"].'",
                        date_inicio_picking = NOW(),
                        date_upd = NOW()
                        WHERE id_pickpack_order = '.$pedido.';';
                        Db::getInstance()->execute($sql_update_comenzado_picking);
                    }
                    
                    require_once("../views/templates/muestrapicking.php");
                }
                
            }

        }
        
    }    

} elseif (isset($_POST['submit_finpicking'])) {
    //procesamos el formulario del picking. Antes mostrabamos un mensaje de éxito pero para agilizar el picking lo hemos quitado
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
    //para no sobreescribir el comentario, lo añadiremos al anterior si fuera la segunda vez o más que pasamos por aquí o no insertaremos nada si no llega ninguno. Añadimos persona que lo escribe y fecha
    if ($_POST['comentario'] && $_POST['comentario'] !==''){
        $comentario = $_POST['comentario'];
        $comentario = '- '.$comentario.'<span style=\"font-size:80%;\"><i>  '.$nombre_empleado.' </i>';      
        $comentario_picking = 'comentario_picking = CONCAT(comentario_picking,"'.$comentario.'",DATE_FORMAT(NOW(),"%d-%m-%Y %H:%i:%s"),"</span><br>"),';
    } else {
        $comentario_picking = '';
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
            $id_pickpack_productos = Db::getInstance()->getValue($sql_busca_pickpack_product, $use_cache = true);

            if ($id_pickpack_productos){
                $sql_update_producto = 'UPDATE lafrips_pick_pack_productos 
                    SET ok_picking = '.$correcto.$incidencia_producto.'
                    WHERE id_pickpack_productos = '.$id_pickpack_productos;

                Db::getInstance()->Execute($sql_update_producto);
                
            } else {
                //si no se hace el update, hacemos insert del producto
                if (!$correcto){
                    $incidencia_prod = ', incidencia_picking';
                    $incidencia_producto = ' ,1 ';
                }else{
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

    $id_pickpack = PickPackOrder::getIdPickPackByIdOrder($id_pedido);
    if ($incidencia){
        //si hay incidencia ponemos o dejamos el estado Incidencia Picking y marcamos incidencia_picking = 1
        $id_estado_order = 3;
        $date_fin_picking = '"0000-00-00 00:00:00"';
        $incidencia_picking = ' incidencia_picking = 1 ,';
    } else {
        //si no hay incidencia ponemos el estado Picking Finalizado y metemos la fecha
        $id_estado_order = 4;
        //$date_fin_picking = date("Y-m-d H:i:s");
        $date_fin_picking = 'NOW()';
        $incidencia_picking = '';

    }

    //08/09/2020 Además, si no hay incidencia tras el picking y el pedido contenía cajas sorpresa, se pondrá como picking finalizado cada pedido con caja sorpresa. Los id de esos pedidos estarían en el array ids_cajas. Si hay incidencia, pasamos los pedidos a incidencia, pero no ponemos mensaje
    if (count($ids_cajas) > 0) {
        //creamos el mensaje
        if (!$incidencia){
            $comentario_caja = '- Picking finalizado para productos de Caja correspondiente a pedido padre '.$id_pedido.'<span style=\"font-size:80%;\"><i>  '.$nombre_empleado.' </i>';  
            $comentario_picking_caja = 'comentario_picking = CONCAT(comentario_picking,"'.$comentario_caja.'",DATE_FORMAT(NOW(),"%d-%m-%Y %H:%i:%s"),"</span><br>"),';
        } else {
            $comentario_picking_caja = '';
        }

        foreach ($ids_cajas AS $id_caja) {
            $id_pickpack_caja = PickPackOrder::getIdPickPackByIdOrder($id_caja);

            $sql_update_pickpack_pedido_caja = 'UPDATE lafrips_pick_pack
            SET
            comenzado = 1,
            id_estado_order = '.$id_estado_order.',
            id_employee_picking = '.$id_empleado.',
            nombre_employee_picking = "'.$nombre_empleado.'",
            '.$comentario_picking_caja.'            
            '.$incidencia_picking.'
            date_fin_picking = '.$date_fin_picking.',
            date_upd = NOW()
            WHERE id_pickpack = '.$id_pickpack_caja.';';    

            Db::getInstance()->execute($sql_update_pickpack_pedido_caja);
        }
    }
    
    
    $sql_update_pickpack_pedido = 'UPDATE lafrips_pick_pack
        SET
        comenzado = 1,
        id_estado_order = '.$id_estado_order.',
        id_employee_picking = '.$id_empleado.',
        nombre_employee_picking = "'.$nombre_empleado.'",
        '.$comentario_picking.'
        obsequio = '.$obsequio.',
        '.$incidencia_picking.'
        date_fin_picking = '.$date_fin_picking.',
        date_upd = NOW()
        WHERE id_pickpack = '.$id_pickpack.';';    

    if (Db::getInstance()->execute($sql_update_pickpack_pedido)){
        //una vez guardado el resultado del picking, volvemos a buscar un pedido directamente
        $action = 'picking';
        require_once("../views/templates/buscapedido.php");
    } else {
        $action = 'picking';
        $error_tracking = 'Hubo un problema al actualizar los datos del picking';
        require_once("../views/templates/error.php");
    }

} elseif (isset($_POST['submit_volver'])) {
    $action = 'picking';
    require_once("../views/templates/buscapedido.php");
}


?>