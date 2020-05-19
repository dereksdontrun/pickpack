<?php

/**
* Utilidad para hacer picking y packing prescindiendo de hojas de pedido
*
* @author    Sergio™ <sergio@lafrikileria.com>
*/

//En AdminPicking.php apuntamos a este archivo fuera de Prestashop, se carga la cabecera html y despendiendo de como llega la llamada, GET o POST y si contiene alguna variable lo orientamos a una función u otra, haciendo todo el proceso de picking en este script


?>
<!DOCTYPE html>
<html>
<title>PICKING - La Frikilería</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
  <link href='../../../views/css/back.css' rel="stylesheet">  
  <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
    <script type="text/javascript" src="../../../views/js/back.js"></script>
</head>
<body>


<?php

require_once(dirname(__FILE__).'/../../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../../init.php');
//hacemos include de la clase para poder usar la función de buscar id_pickpack por id_order
include dirname(__FILE__).'/../../../classes/PickPackOrder.php';

//si llegamos aquí desde el controlador de AdminPicking trae el id de empleado por GET
if (isset($_GET['id_empleado'])) {
  $id_empleado = $_GET['id_empleado']; 
  $sql_nombre_empleado = 'SELECT firstname FROM lafrips_employee WHERE id_employee = '.$id_empleado;
  $nombre_empleado = Db::getInstance()->ExecuteS($sql_nombre_empleado);
  $nombre_empleado = $nombre_empleado[0]['firstname'];
  
  //almaceno en una sesión el id y nombre del empleado para usarlo después 
  session_start();
  $_SESSION["id_empleado"] = $id_empleado;
  $_SESSION["nombre_empleado"] = $nombre_empleado;
  buscarPedido();
//si llegamos a picking.php desde el formulario de buscarPedido viene por POST y mostraremos el picking
} elseif(isset($_POST['submit_pedido'])){
  mostrarPicking();
} elseif(isset($_POST['submit_finpicking'])){
  procesaPicking();
} elseif(isset($_POST['submit_volver'])){
  buscarPedido();
} else {
  echo '<div class="jumbotron jumbotron_sesion">
    <h1 class="display-4">Hola</h1>
    <p class="lead">Tu sesión ha expirado o nunca se inició</p>
    <p class="lead">Tienes que acceder desde Prestashop, iniciando sesión en La Frikilería</p>
  </div>  ';
}

function buscarPedido()
{
  $nombre_empleado = $_SESSION["nombre_empleado"];  
  //si no hay nada en la sesión pedimos que inicie sesión en Prestashop
  if (!$nombre_empleado){
    echo '
    <div class="jumbotron jumbotron_sesion">
      <h1 class="display-4">Hola</h1>
      <p class="lead">Tu sesión ha expirado o nunca se inició</p>
      <p class="lead">Tienes que acceder desde Prestashop, iniciando sesión en La Frikilería</p>
    </div>    
    ';
  }else{
    echo '
    <div class="jumbotron jumbotron_picking">
      <h1 class="display-4">Hola '.$nombre_empleado.'</h1>
      <p class="lead">Bienvenido a los pickings de La Frikilería</p>
    </div>
    <div class="container" style="margin-bottom:60px;">  
      <form action="picking.php" method="post"> 
        <div class="form-group"> 
          <label for="text">Introduce el identificador del pedido para realizar el PICKING:</label> 
          <input type="text" name="id_pedido" size="30"  class="form-control">
        </div>
        <button type="submit" name="submit_pedido" class="btn btn-primary">Buscar</button>
      </form>
    </div>  
    ';
  }
}

function mostrarPicking(){
  if (!$_SESSION["id_empleado"]){
    echo '
    <div class="jumbotron jumbotron_sesion">
      <h1 class="display-4">Hola</h1>
      <p class="lead">Tu sesión ha expirado o nunca se inició</p>
      <p class="lead">Tienes que acceder desde Prestashop, iniciando sesión en La Frikilería</p>
    </div>    
    ';
  }else{
    $pedido = $_POST['id_pedido'];

    $error_tracking = 0;
    //$pedido contiene un valor que puede ser un id de pedido, normalmente de 6 cifras, o un código de barras de x longitud, mayor de 6 caracteres. Si lo introducido tiene menos de 7 cifras lo interpretamos como el id de pedido y lo dejamos igual, si no, lo consideramos tracking y en función a este buscamos el id de pedido. Tenemos a 03/03/2020 lo siguiente:
      // - GLS - Tracking de 14 cifras. El lector saca 18, añade 4 al final
      // - Correos "nacional" - Tracking de 23 caracteres, letras y números. Lo lee completo
      // - Correos internacional light y prioritario 13 caracteres. Lo lee completo
      // - Correos Express Baleares - tracking 16 cifras - lee 23, empieza por 6, quitar últimas
      // - Correos Express 24/48 domicilio - tracking 16 cifras - lee 23, empieza por 323, quitar últimas 
      // - Spring - Tiene muchas variantes por paises, en algunas lo que lee el scanner coincide con el tracking, en otras hay que quitar números del final, además depende del repartidor final. A veces...:
      //     Francia - quitar 5 cifras
      //     Mónaco coincide
      //     UK coincide
      //     Alemania coincide
      //     Italia coincide

    if (strlen($pedido) > 6){
      //si lo introducido es más de 6 caracteres primero buscamos la coincidencia completa
      $sql_busca_pedido_desde_tracking = 'SELECT id_order FROM lafrips_order_carrier WHERE tracking_number = "'.$pedido.'";';
      $tracking = Db::getInstance()->ExecuteS($sql_busca_pedido_desde_tracking);
      //si no encuentra nada que corresponda a lo introducido mostraremos luego un error
      if (!$tracking){
        //Si no ha coincidido, probamos quitando los últimos 4 caracteres, para GLS
        $pedido_4 = substr($pedido, 0, -4);
        $sql_busca_pedido_desde_tracking = 'SELECT id_order FROM lafrips_order_carrier WHERE tracking_number = "'.$pedido_4.'";';
        $tracking = Db::getInstance()->ExecuteS($sql_busca_pedido_desde_tracking);
        if (!$tracking){
          //Si no ha coincidido, probamos quitando los últimos 5 caracteres, para algunas de Spring
          $pedido_5 = substr($pedido, 0, -5);
          $sql_busca_pedido_desde_tracking = 'SELECT id_order FROM lafrips_order_carrier WHERE tracking_number = "'.$pedido_5.'";';
          $tracking = Db::getInstance()->ExecuteS($sql_busca_pedido_desde_tracking);
          if (!$tracking){
            //Si no ha coincidido, probamos quitando los últimos 6 caracteres, para Correos Express??
            $pedido_6 = substr($pedido, 0, -6);
            $sql_busca_pedido_desde_tracking = 'SELECT id_order FROM lafrips_order_carrier WHERE tracking_number = "'.$pedido_6.'";';
            $tracking = Db::getInstance()->ExecuteS($sql_busca_pedido_desde_tracking);
            if (!$tracking){
              //Si no ha coincidido, probamos quitando los últimos 8 caracteres, para Correos Express, y además el tracking_number tiene la última cifra diferente a la del código leido, es decir, la cifra en posición 16 es diferente en el tracking y en el código
              // Ejemplo: 
              // -leido con scanner  32300028296086601287016
              // -tracking number    3230002829608665
              // quitamos 8 caracteres y comparamos con los tracking number con LIKE
              $pedido_8 = substr($pedido, 0, -8);
              $sql_busca_pedido_desde_tracking = 'SELECT id_order FROM lafrips_order_carrier WHERE tracking_number LIKE "'.$pedido_8.'%";';
              $tracking = Db::getInstance()->ExecuteS($sql_busca_pedido_desde_tracking);

              if (!$tracking){
                //por ahora 03/03/2020, si no ha encontrado aún correspondencia interpretamos que no es correcto el tracking
                $error_tracking = 'El código introducido no corresponde a ningún tracking de pedido';
                $pedido = 0;
              } else {
                //si devuelve más de un pedido coincidente al tracking mostrará error y pedirá que se introduzca el id manualmente
                if (count($tracking) > 1){
                  $error_tracking = 'El código introducido corresponde a más de un pedido, introduce el número de pedido a mano';
                  $pedido = 0;
                } else {
                  $pedido = $tracking[0]['id_order'];
                }
              }
            } else {
              //si devuelve más de un pedido coincidente al tracking mostrará error y pedirá que se introduzca el id manualmente
              if (count($tracking) > 1){
                $error_tracking = 'El código introducido corresponde a más de un pedido, introduce el número de pedido a mano';
                $pedido = 0;
              } else {
                $pedido = $tracking[0]['id_order'];
              }
            }
            
          } else {
            //si devuelve más de un pedido coincidente al tracking mostrará error y pedirá que se introduzca el id manualmente
            if (count($tracking) > 1){
              $error_tracking = 'El código introducido corresponde a más de un pedido, introduce el número de pedido a mano';
              $pedido = 0;
            } else {
              $pedido = $tracking[0]['id_order'];
            }
          }

        } else {
          //si devuelve más de un pedido coincidente al tracking mostrará error y pedirá que se introduzca el id manualmente
          if (count($tracking) > 1){
            $error_tracking = 'El código introducido corresponde a más de un pedido, introduce el número de pedido a mano';
            $pedido = 0;
          } else {
            $pedido = $tracking[0]['id_order'];
          }
        }
        
      } else {
        //si devuelve más de un pedido coincidente al tracking mostrará error y pedirá que se introduzca el id manualmente
        if (count($tracking) > 1){
          $error_tracking = 'El código introducido corresponde a más de un pedido, introduce el número de pedido a mano';
          $pedido = 0;
        } else {
          $pedido = $tracking[0]['id_order'];
        }
      }
      
    }
    
    //sacamos info del cliente y pedido. LEFT JOIN en tabla state porque amazon introduce las address sin id_state.
    //añadido JOIN a tabla pick_pack para que solo muestre pedidos que estén en la tabla
    //añadido para saber si ha pasado por estados Caja Sin Preparar (id 28) y Cajas Preparadas (id 17). Si ha pasado por ambos da 1, si solo ha pasado por 28, da 2 y mostrará mensaje de que falta hacer la caja.     
    $sql_info_pedido = "SELECT ord.id_customer AS id_cliente, CONCAT(cus.firstname,' ', cus.lastname) AS nombre_cliente, CONCAT(adr.address1,' ', adr.address2) AS direccion, adr.postcode AS codigo_postal, 
    adr.city AS ciudad, sta.name AS provincia, col.name AS pais, ord.date_add AS fecha_pedido, car.name AS transporte, ord.module AS 'amazon', ppe.nombre_estado AS 'estado_pickpack', 
    CASE
    WHEN 28 IN (SELECT id_order_state FROM lafrips_order_history where id_order= ".$pedido.") && 
		17 IN (SELECT id_order_state FROM lafrips_order_history where id_order= ".$pedido.") THEN 1
    WHEN 28 IN (SELECT id_order_state FROM lafrips_order_history where id_order= ".$pedido.") && 
		17 NOT IN (SELECT id_order_state FROM lafrips_order_history where id_order= ".$pedido.") THEN 2    
	  ELSE 0
    END AS 'estados_caja_sorpresa'  
    FROM lafrips_customer cus
    JOIN lafrips_orders ord ON ord.id_customer = cus.id_customer
    JOIN lafrips_address adr ON ord.id_address_delivery = adr.id_address
    JOIN lafrips_country_lang col ON adr.id_country = col.id_country
    LEFT JOIN lafrips_state sta ON sta.id_state = adr.id_state
    JOIN lafrips_carrier car ON car.id_carrier = ord.id_carrier
    JOIN lafrips_pick_pack pip ON pip.id_pickpack_order = ord.id_order
    JOIN lafrips_pick_pack_estados ppe ON ppe.id_pickpack_estados = pip.id_estado_order
    WHERE col.id_lang = 1
    AND ord.id_order = ".$pedido.";";

    $info_pedido = Db::getInstance()->ExecuteS($sql_info_pedido);

    //si no se encuentra el pedido mostramos error
    if (!$info_pedido){   
      $nombre_empleado = $_SESSION["nombre_empleado"];   
      //mostrar una pantalla de error con botón para volver
      echo '
      <div class="jumbotron jumbotron_picking">
        <h1 class="display-4">Hola '.$nombre_empleado.'</h1>
        <p class="lead">El pedido que buscas no está disponible para Picking</p>';
      if ($error_tracking){
        echo '<p class="lead">'.$error_tracking.'</p>';
      }
      echo '</div>
      <div class="container" style="margin-bottom:60px;">  
        <form action="picking.php" method="post">      
          <button type="submit" name="submit_volver" class="btn btn-success">Volver</button>
        </form>
      </div>  
      ';       
    } else {
      $id_cliente = $info_pedido[0]['id_cliente'];
      $nombre_cliente = $info_pedido[0]['nombre_cliente'];
      $direccion = $info_pedido[0]['direccion'];
      $codigo_postal = $info_pedido[0]['codigo_postal'];
      $ciudad = $info_pedido[0]['ciudad'];
      $provincia = $info_pedido[0]['provincia'];
      $pais = $info_pedido[0]['pais'];
      $fecha_pedido = date('d-m-Y', strtotime($info_pedido[0]['fecha_pedido']));
      $transporte = $info_pedido[0]['transporte'];
      $amazon = $info_pedido[0]['amazon'];
      //si el módulo de pago es amazon $amazon = 1
      if ($amazon == 'amazon'){
        $amazon = 1;
      } else {
        $amazon = 0;
      }
      $estado_pickpack = $info_pedido[0]['estado_pickpack'];
      //estados_caja_sorpresa 0 si no hay caja sorpresa, 1 si ha pasado ambos estados (sin preparar y preparada) y 2 si solo ha pasado por sin preparar
      $estados_caja_sorpresa = $info_pedido[0]['estados_caja_sorpresa'];

      // Número de pedidos del cliente
      $sql_numero_pedidos = "SELECT COUNT(id_order) AS num_pedidos FROM lafrips_orders WHERE id_customer = ".$id_cliente." AND valid = 1;";
      $numero_pedidos = Db::getInstance()->ExecuteS($sql_numero_pedidos);
      $numero_pedidos = $numero_pedidos[0]['num_pedidos'];  

      //27/02/2020 - queremos mostrar en el picking los mensajes sobre el pedido, SOLO PRIVADOS
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
      //hacemos que ORDER BY coloque en orden de menor a mayor por localización pero si el valor de product_id es de caja sorpresa lo coloque al final, de modo que las cajs sorpresa vayan al final ORDER BY FIELD(ode.product_id, 5344) ASC, wpl.location
      $sql_productos_pedido = "SELECT ode.product_id AS id_producto, ode.product_attribute_id AS id_atributo, ode.product_name AS nombre_completo,    
      ode.product_reference AS referencia_producto, ode.product_ean13 AS ean, ode.product_quantity AS cantidad, ode.unit_price_tax_incl AS precio_producto,  
      CONCAT( 'http://lafrikileria.com', '/', img.id_image, '-home_default/', 
      pla.link_rewrite, '.', 'jpg') AS 'imagen', img.id_image AS 'existe_imagen', 
      CASE
      WHEN pro.cache_is_pack = 0 THEN wpl.location
      WHEN pro.cache_is_pack = 1 THEN CONCAT(
          (CASE
          WHEN wpl.location IS NULL THEN 'NO'
          WHEN wpl.location = '' THEN 'NO'
          ELSE CONCAT('P',wpl.location)
          END)
          ,' / ',(SELECT GROUP_CONCAT(location SEPARATOR ' / ') FROM lafrips_warehouse_product_location WHERE id_product IN (SELECT id_product_item FROM lafrips_pack WHERE id_product_pack = ode.product_id) AND id_warehouse = 1))
      END
      AS 'localizacion',
      loc.r_location AS 'localizacion_repo',
      (SELECT SUM(physical_quantity) FROM lafrips_stock 
      WHERE id_product = ode.product_id
      AND id_product_attribute = ode.product_attribute_id 
      AND id_warehouse = 1) AS 'stock_online',
      (SELECT SUM(physical_quantity) FROM lafrips_stock 
      WHERE id_product = ode.product_id
      AND id_product_attribute = ode.product_attribute_id  
      AND id_warehouse = 4) AS 'stock_tienda'
      FROM lafrips_product pro      
      JOIN lafrips_order_detail ode ON pro.id_product = ode.product_id   
      JOIN lafrips_product_lang pla ON pla.id_product = ode.product_id AND pla.id_lang = 1  
      LEFT JOIN lafrips_image img ON ode.product_id = img.id_product
          AND img.cover = 1
      LEFT JOIN lafrips_warehouse_product_location wpl ON wpl.id_product = ode.product_id AND wpl.id_product_attribute = ode.product_attribute_id     
      LEFT JOIN lafrips_localizaciones loc ON loc.id_product = ode.product_id AND loc.id_product_attribute = ode.product_attribute_id
      WHERE ode.id_order = ".$pedido." 
      AND wpl.id_warehouse = 1 
      GROUP BY ode.product_name
      ORDER BY FIELD(ode.product_id, 5344) ASC, wpl.location;";

      $productos_pedido = Db::getInstance()->ExecuteS($sql_productos_pedido);  

      //Si es un pedido con Caja Sorpresa, es decir, el pedido ha pasado por Caja sin preparar Y Cajas preparadas, buscaremos el/los mensajes privados que indican en que pedido/s virtual están los productos de la/las cajas. El mensaje debe tener el formato dado en la plantilla dentro de los pedidos: "CAJA SORPRESA*Pedido ". Obtenido el mensaje se cortará para sacar solo el número de pedido. Si son varios se guardan en un array.
      if ($estados_caja_sorpresa == 1){
        $sql_mensaje_pedido_caja_sorpresa = "SELECT cum.message AS mensaje
        FROM lafrips_customer_message cum
        JOIN lafrips_customer_thread cut ON cut.id_customer_thread = cum.id_customer_thread      
        WHERE cum.private = 1
        AND cum.message LIKE 'CAJA SORPRESA*Pedido %'
        AND cut.id_order = ".$pedido.";";
        $mensaje_pedido_caja_sorpresa = Db::getInstance()->ExecuteS($sql_mensaje_pedido_caja_sorpresa);

        //sacamos el/los id_order donde están las cajas
        $id_order_caja_sorpresa = array();
        foreach ($mensaje_pedido_caja_sorpresa AS $mensaje){        
          $mensaje = $mensaje['mensaje'];
          //recortamos a partir de "CAJA SORPRESA*Pedido " 6 caracteres hacia la derecha, lo cual debe recoger el id de pedido
          $id_order_caja_sorpresa[] = substr($mensaje, 21, 6);       
        }        
        
        //con los productos obtenidos en la consulta de productos, comprobar que dentro del pedido hay tantas cajs sorpresa como id_order hemos encontrado en los comentarios, mostrar error si no es así. Si hubiera más de una caja sorpresa, al no poder saber a cual corresponde el id_order, en el picking no mostraremos el precio de la caja sino un genérico y que tengan que fijarse (mensajes con descripción??)        
        $error_numero_cajas_sorpresa = 0;
        $num_cajas = 0;
        foreach ($productos_pedido as $producto) { 
          if ($producto['id_producto'] == 5344){
            $cantidad = $producto['cantidad'];  //con esto evitamos el error al contar las cajas, ya que si son del mismo precio cuentan como una caja y dos unidades de ella, etc
            $num_cajas = $num_cajas + $cantidad;
          }
        }
        if (COUNT($id_order_caja_sorpresa) != $num_cajas){
          $error_numero_cajas_sorpresa = 1;
        }

        //si todo es correcto comprobamos que los id de pedido corresponden a pedidos virtuales y sacamos los productos para luego
        if (!$error_numero_cajas_sorpresa){
          //creamos un array donde se introducirán los id de pedido de caja como key y los productos del pedido como value
          $productos_pedidos_virtuales = array();
          foreach ($id_order_caja_sorpresa as $id_order){
            $pedido_virtual = $id_order;
            //revisamos que el pedido esté o haya estado en estado Pedido Virtual id 24 y que el id_customer corresponda al mismo cliente, si no guardamos error
            $sql_info_pedido_virtual = "SELECT id_customer AS id_cliente,     
            CASE
            WHEN 24 IN (SELECT id_order_state FROM lafrips_order_history where id_order= ".$pedido_virtual.") THEN 1
            ELSE 0
            END AS 'estado_pedido_virtual'  
            FROM lafrips_orders 
            WHERE id_order = ".$pedido_virtual.";";

            $info_pedido_virtual = Db::getInstance()->ExecuteS($sql_info_pedido_virtual);
            //si no se da cualquiera de los requisitos sacamos marcador de error
            if ($id_cliente != $info_pedido_virtual[0]['id_cliente']){
              $error_pedido_virtual_cliente = 1;
            } 
            if (!$info_pedido_virtual[0]['estado_pedido_virtual']){
              $error_pedido_virtual_estado = 1;
            }

            //si no hay error de cliente ni del estado pedido virtual, sacamos los productos de cada pedido/caja
            $sql_productos_caja_sorpresa = "SELECT ode.product_id AS id_producto, ode.product_attribute_id AS id_atributo, ode.product_name AS nombre_completo,    
            ode.product_reference AS referencia_producto, ode.product_ean13 AS ean, ode.product_quantity AS cantidad, ode.unit_price_tax_incl AS precio_producto,  
            CONCAT( 'http://lafrikileria.com', '/', img.id_image, '-home_default/', 
            pla.link_rewrite, '.', 'jpg') AS 'imagen', img.id_image AS 'existe_imagen', 
            CASE
            WHEN pro.cache_is_pack = 0 THEN wpl.location
            WHEN pro.cache_is_pack = 1 THEN CONCAT(
                (CASE
                WHEN wpl.location IS NULL THEN 'NO'
                WHEN wpl.location = '' THEN 'NO'
                ELSE CONCAT('P',wpl.location)
                END)
                ,' / ',(SELECT GROUP_CONCAT(location SEPARATOR ' / ') FROM lafrips_warehouse_product_location WHERE id_product IN (SELECT id_product_item FROM lafrips_pack WHERE id_product_pack = ode.product_id) AND id_warehouse = 1))
            END
            AS 'localizacion',
            loc.r_location AS 'localizacion_repo',
            (SELECT SUM(physical_quantity) FROM lafrips_stock 
            WHERE id_product = ode.product_id
            AND id_product_attribute = ode.product_attribute_id 
            AND id_warehouse = 1) AS 'stock_online',
            (SELECT SUM(physical_quantity) FROM lafrips_stock 
            WHERE id_product = ode.product_id
            AND id_product_attribute = ode.product_attribute_id  
            AND id_warehouse = 4) AS 'stock_tienda'
            FROM lafrips_product pro      
            JOIN lafrips_order_detail ode ON pro.id_product = ode.product_id   
            JOIN lafrips_product_lang pla ON pla.id_product = ode.product_id AND pla.id_lang = 1  
            LEFT JOIN lafrips_image img ON ode.product_id = img.id_product
                AND img.cover = 1
            LEFT JOIN lafrips_warehouse_product_location wpl ON wpl.id_product = ode.product_id AND wpl.id_product_attribute = ode.product_attribute_id     
            LEFT JOIN lafrips_localizaciones loc ON loc.id_product = ode.product_id AND loc.id_product_attribute = ode.product_attribute_id
            WHERE ode.id_order = ".$pedido_virtual." 
            AND wpl.id_warehouse = 1 
            GROUP BY ode.product_name
            ORDER BY FIELD(ode.product_id, 5344) ASC, wpl.location;";

            $productos_caja_sorpresa = Db::getInstance()->ExecuteS($sql_productos_caja_sorpresa);

            //el resultado lo guardaremos en un array cuyo key será el id del pedido virtual y value el resultado del sql de productos
            $productos_pedidos_virtuales[$pedido_virtual] = $productos_caja_sorpresa;
          }
        }

        //Si el pedido ha pasado por estado Cajas sin preparar pero no por cajas preparadas, contamos las cajas en pedido y mostraremos error
      } elseif ($estados_caja_sorpresa == 2){
        $num_cajas = 0;
        foreach ($productos_pedido as $producto) { 
          if ($producto['id_producto'] == 5344){
            $cantidad = $producto['cantidad'];
            $num_cajas = $num_cajas + $cantidad;
          }
        }
      }   

      //mostramos los datos
      //cabecera con datos de cliente
      echo '
      <div class="jumbotron jumbotron_picking">
        <h1 class="display-4"><span style="font-size: 50%;">PICKING</span> <strong>'.$pedido.'</strong> <span style="font-size: 50%;">'.$fecha_pedido.'</span></h1> num cajas='.$num_cajas.' $error_numero_cajas_sorpresa='.$error_numero_cajas_sorpresa.' cajas= '; 
        print_r($id_order_caja_sorpresa);echo '<br>';
        print_r($info_pedido_virtual);echo '<br>';
        print_r($productos_pedidos_virtuales);echo '<br>';
        echo '<h5><span style="font-size: 80%;">Estado Pick Pack actual</span> <strong>'.$estado_pickpack.'</strong></h5>';
        //Si el pedido pasó por Caja sin preparar pero no por Cajas preparadas mostramos error
        if ($estados_caja_sorpresa == 2){
          echo '<span class="badge badge-pill badge-danger" style="font-size: 150%;"><p>Solo pasado por Caja sin preparar</p><p>Contiene '.$num_cajas.' cajas</p></span><br><br>';
        }   
        //Si el pedido pasó por Caja sin preparar y por Cajas preparadas pero no coincide el número de cajas en pedido con los pedidos virtuales de cajas (los mensajes privados) mostramos error
        if ($estados_caja_sorpresa == 1 && $error_numero_cajas_sorpresa){
          echo '<span class="badge badge-pill badge-danger" style="font-size: 150%;"><p>Error en cantidad</p><p>de cajas preparadas</p></span><br><br>';
        }   
        //Si el pedido que debería contener la caja no ha estado en pedido virtual mostramos error
        if ($error_pedido_virtual_estado){
          echo '<span class="badge badge-pill badge-danger" style="font-size: 150%;"><p>Error en Caja Sorpresa</p><p>No hay pedido virtual</p></span><br><br>';
        } 
        //Si el cliente del pedido que debería contener la caja no coincide con el del pedido principal mostramos error
        if ($error_pedido_virtual_cliente){
          echo '<span class="badge badge-pill badge-danger" style="font-size: 150%;"><p>Error en Caja Sorpresa</p><p>No coincide cliente</p></span><br><br>';
        } 

        echo '<div class="datos_cliente">
          <address>          
            <strong>'.$nombre_cliente.'</strong><br>
            '.$direccion.'<br>
            '.$codigo_postal.' '.$ciudad.' - '.$provincia.' - '.$pais.'<br>';
            //si es pedido amazon lo indico
            if ($amazon){
              echo '<span class="badge badge-pill badge-info" style="font-size: 150%;">AMAZON</span><br>';
            }        
            echo '<span class="badge badge-pill badge-warning" style="font-size: 150%;">'.$transporte.'</span>
          </address>         
        </div>    
      </div>
      ';

      //mostramos los mensajes sobre el pedido si los hay.
      if ($todos_mensajes_pedido){
        echo '
        <div class="container container_mensajes">  
          <div class="panel">
            <h3>Mensajes privados del pedido:</h3>
            <div class="list-group">';
              foreach ($todos_mensajes_pedido AS $cabecera => $mensaje){
                echo '<div class="list-group-item">        
                  <footer class="blockquote-footer">'.$cabecera.'</footer>
                  <blockquote class="blockquote padquote">
                    <p class="mb-0">'.$mensaje.'</p>
                  </blockquote>
                </div>';
              }
              echo '</div>
              </div>
        </div>';
        }

      //necesito saber el número de productos del pedido cuando envíe el formulario, de modo que usamos una variable $numero_productos que se insertará en un input hidden y se recogerá después en la función procesaPicking para sacar bien los id de producto y su estado al hacer picking
      $numero_productos = count($productos_pedido);
      
      //líneas de productos
      echo '
      <div class="container container_productos">  
        <h3>Productos - '.$numero_productos.'</h3>
        <form id="formulario_picking" action="picking.php" method="post"> ';   
        //si el pedido no tiene cajas pasamos este loop, si no iremos al de abajo. En caso de que salga una caja pero no hubiera pasado por Caja sin preparar etc, mostraremos un error
        if (!$estados_caja_sorpresa) {
          foreach ($productos_pedido as $producto) { 
            $id_producto = $producto['id_producto'];
            $id_atributo = $producto['id_atributo'];
            $nombre_producto = $producto['nombre_completo']; 
            $referencia_producto = $producto['referencia_producto']; 
            $ean = $producto['ean']; 
            $cantidad = $producto['cantidad']; 
            $precio_producto = $producto['precio_producto']; 
            $localizacion = $producto['localizacion']; 
            $localizacion_reposicion = $producto['localizacion_repo']; 
            //stocks
            $stock_online = $producto['stock_online'];
            if (!$stock_online){
              $stock_online = 0;
            }
            $stock_tienda = $producto['stock_tienda'];
            if (!$stock_tienda){
              $stock_tienda = 0;
            }
            //imagen, revisamos campo 'existe_imagen', si no contiene id ponemos logo de tienda. 
            if (empty($producto['existe_imagen'])) {
              $imagen = 'https://lafrikileria.com/img/logo_producto_medium_default.jpg';
            } else {
              $imagen = $producto['imagen'];
            }

            //mostramos imagen, tamaño de imagen fijado, con un 25% menos del tamaño de home_default
            echo '
            <div class="row">
              <div class="col-sm-5 col-xs-4">            
                <img src="'.$imagen.'"  width="218" height="289"/>    
              </div>
              ';

            //datos del producto, si fuera id 5344 caja sorpresa es que hay algún error, habiendo caja no debería pasar por aquí,mostramos mensaje
            echo '
              <div class="col-sm-5 col-xs-6">';
                if ($id_producto == 5344) {
                  echo '<span class="badge badge-pill badge-danger" style="font-size: 150%;"><p>Error en pedido</p><p>o caja sin preparar</p></span><br><br>';
                } else {
                  echo '<p class="h6">'.$nombre_producto.'</p><br>';
                }

                echo 'REF: '.$referencia_producto.'<br/>';
                //Si no tiene ean no lo ponemos
                if ($ean && $ean !== ''){ 
                  echo '<span>EAN: '.$ean.'</span><br/>';
                }
                //Si no tiene localización no la ponemos
                if ($localizacion && $localizacion !== ''){ 
                  echo '<span style="font-size:17px; font-weight:bold;">'.$localizacion.'</span><br/>';
                }
                //Si no tiene localización de repo no la  ponemos
                if ($localizacion_reposicion && $localizacion_reposicion !== ''){ 
                  echo '<span style="font-size:14px;">Repo: '.$localizacion_reposicion.'</span><br/>';
                }
                //Stock en ambos almacenes
                echo '<span style="font-size:14px;">Stock: Online ( '.$stock_online.' ) - Tienda ( '.$stock_tienda.' )</span>          
                </div>';
              
            //Unidades de producto en pedido, si son más de una se señala
            echo '<div class="col-sm-1 col-xs-1">
                    <h4 class="alert-heading">Ud:</h4>';
                      if ($cantidad != 1) {         
                        echo '<div class="alert alert-danger">          
                          <p class="h2">'.$cantidad.'</p>
                        </div>'; 
                      }else{ 
                        echo '<div class="alert alert-light">          
                          '.$cantidad.'
                        </div>';
                      }    
              echo '</div>';
              
              //botones de ok para cada producto
              echo '<div class="col-sm-1 col-xs-1" style="padding-top:5%;">
                      <div class="btn-group" data-toggle="buttons">            
                        <label class="btn btn-success active">
                          <input type="radio" name="'.$id_producto.'_'.$id_atributo.'" id="'.$id_producto.'_'.$id_atributo.'" value="1" autocomplete="off" required>
                          <span class="fa fa-check"></span>
                        </label>		

                        <label class="btn btn-danger">
                        <input type="radio" name="'.$id_producto.'_'.$id_atributo.'" id="'.$id_producto.'_'.$id_atributo.'" value="0" autocomplete="off" >
                          <span class="fa fa-times"></span>
                        </label>          
                      </div>     
                    </div>
                  </div> <!-- Fin row de producto-->
                  <hr>';


          } //fin foreach producto
          //fin if No hay cajas sorpresa
        } elseif ($estados_caja_sorpresa == 2){ //hay cajas pero el pedido no ha pasado por Cajas Preparadas
          echo '<span class="badge badge-pill badge-danger" style="font-size: 150%;"><p></p><p>Este pedido solo ha pasado por</p><p>Estado Caja sin preparar</p><p>Contiene '.$num_cajas.' cajas</p></span><br><br>';
        } elseif ($estados_caja_sorpresa == 1 && $error_numero_cajas_sorpresa){ //No coincide número de cajas en pedido con id_order en mensajes
          echo '<span class="badge badge-pill badge-danger" style="font-size: 150%;"><p>Error en cantidad</p><p>de cajas preparadas</p></span><br><br>';
        } elseif ($estados_caja_sorpresa == 1 && !$error_numero_cajas_sorpresa) { //Correctos estados y número de cajas preparadas

          foreach ($productos_pedido as $producto) { 
            $id_producto = $producto['id_producto'];
            $id_atributo = $producto['id_atributo'];
            $nombre_producto = $producto['nombre_completo']; 
            $referencia_producto = $producto['referencia_producto']; 
            $ean = $producto['ean']; 
            $cantidad = $producto['cantidad']; 
            $precio_producto = $producto['precio_producto']; 
            $localizacion = $producto['localizacion']; 
            $localizacion_reposicion = $producto['localizacion_repo']; 
            //stocks
            $stock_online = $producto['stock_online'];
            if (!$stock_online){
              $stock_online = 0;
            }
            $stock_tienda = $producto['stock_tienda'];
            if (!$stock_tienda){
              $stock_tienda = 0;
            }
            //imagen, revisamos campo 'existe_imagen', si no contiene id ponemos logo de tienda. 
            if (empty($producto['existe_imagen'])) {
              $imagen = 'https://lafrikileria.com/img/logo_producto_medium_default.jpg';
            } else {
              $imagen = $producto['imagen'];
            }

            //comprobamos si el producto es caja sorpresa, si no lo es sacamos el producto normalmente
            if ($id_producto != 5344){
              //mostramos imagen, tamaño de imagen fijado, con un 25% menos del tamaño de home_default
              echo '
              <div class="row">
                <div class="col-sm-5 col-xs-4">            
                  <img src="'.$imagen.'"  width="218" height="289"/>    
                </div>
                ';

              //datos del producto, si fuera id 5344 caja sorpresa es que hay algún error, habiendo caja no debería pasar por aquí,mostramos mensaje
              echo '
                <div class="col-sm-5 col-xs-6">';
                  
                  echo '<p class="h6">'.$nombre_producto.'</p><br>';     

                  echo 'REF: '.$referencia_producto.'<br/>';
                  //Si no tiene ean no lo ponemos
                  if ($ean && $ean !== ''){ 
                    echo '<span>EAN: '.$ean.'</span><br/>';
                  }
                  //Si no tiene localización no la ponemos
                  if ($localizacion && $localizacion !== ''){ 
                    echo '<span style="font-size:17px; font-weight:bold;">'.$localizacion.'</span><br/>';
                  }
                  //Si no tiene localización de repo no la  ponemos
                  if ($localizacion_reposicion && $localizacion_reposicion !== ''){ 
                    echo '<span style="font-size:14px;">Repo: '.$localizacion_reposicion.'</span><br/>';
                  }
                  //Stock en ambos almacenes
                  echo '<span style="font-size:14px;">Stock: Online ( '.$stock_online.' ) - Tienda ( '.$stock_tienda.' )</span>          
                  </div>';
                
              //Unidades de producto en pedido, si son más de una se señala
              echo '<div class="col-sm-1 col-xs-1">
                      <h4 class="alert-heading">Ud:</h4>';
                        if ($cantidad != 1) {         
                          echo '<div class="alert alert-danger">          
                            <p class="h2">'.$cantidad.'</p>
                          </div>'; 
                        }else{ 
                          echo '<div class="alert alert-light">          
                            '.$cantidad.'
                          </div>';
                        }    
                echo '</div>';
                
                //botones de ok para cada producto
                echo '<div class="col-sm-1 col-xs-1" style="padding-top:5%;">
                        <div class="btn-group" data-toggle="buttons">            
                          <label class="btn btn-success active">
                            <input type="radio" name="'.$id_producto.'_'.$id_atributo.'" id="'.$id_producto.'_'.$id_atributo.'" value="1" autocomplete="off" required>
                            <span class="fa fa-check"></span>
                          </label>		

                          <label class="btn btn-danger">
                          <input type="radio" name="'.$id_producto.'_'.$id_atributo.'" id="'.$id_producto.'_'.$id_atributo.'" value="0" autocomplete="off" >
                            <span class="fa fa-times"></span>
                          </label>          
                        </div>     
                      </div>
                    </div> <!-- Fin row de producto-->
                    <hr>';

            } else { //si es caja sorpresa...
              //tenemos que obtener los productos de la caja y el id_order del pedido virtual. Sabemos que hay $num_cajas en el pedido, y la info está en el array $productos_pedidos_virtuales. Sacaremos la info de una caja (key y value) y la eliminaremos, de modo que la segunda vez que pasemos por aquí y sucesivas, simepre saldrá info de una caja que no había salido antes, así no se repetirá.

              //reset — Establece el puntero interno de un array a su primer elemento
              //key() devuelve el elemento índice de la posición actual del array. 
              reset($productos_pedidos_virtuales);
              $id_pedido_virtual = key($productos_pedidos_virtuales);
              //con la key sacamos el array de productos
              $productos_caja = $productos_pedidos_virtuales[$id_pedido_virtual];
              //ahora eliminamos los datos del pedido en que nos encontramos dentro del array
              //unset() destroys the specified variables. 
              unset($productos_pedidos_virtuales[$id_pedido_virtual]);
              
              //mostramos imagen, tamaño de imagen fijado, con un 25% menos del tamaño de home_default
              echo '
              <div class="row">
                <div class="col-sm-5 col-xs-3">            
                  <img src="'.$imagen.'"  width="218" height="289"/>    
                </div>
                ';
              
              //datos de la caja y número del pedido virtual del que sale
              echo '
                <div class="col-sm-4 col-xs-5">';
// echo '<br>id_pedido_virtual='.$id_pedido_virtual;
// print_r($productos_caja);
// echo '<br>';
                  //comprobamos si hay una caja o más, si son varias ponemos un nombre genérico, que incluya el nº de pedido
                  if ($num_cajas == 1){
                    echo '<p class="h6">'.$nombre_producto.'</p><br>';     

                    echo 'REF: '.$referencia_producto.'<br/>';
                  } else {
                    echo '<p class="h6">Caja Sorpresa</p><br>';     

                    echo '<p class="h6">Este pedido incluye más de una caja sorpresa,<br>si es necesario, deberás comprobar manualmente<br>cual es cual.</p><br>';
                  }      

                  echo 'Pedido Virtual: '.$id_pedido_virtual.'<br/>';

                  echo 'Nº Productos: '.count($productos_caja).'<br/>';
                           
                  echo '</div>';
                
              //un botón que al ser pulsado mostrará el div con los productos del pedido virtual
              echo '<div class="col-sm-2 col-xs-2">
                <div class="form-group" style="padding-top:5%;">
                  <label class="btn btn-lg btn-warning"> 
                    <span name="muestra_caja" id="muestra_caja">Mostrar Productos</span>
                  </label>
                </div>                
              </div>';
                
                //botones de ok para caja en general, como producto individual
                echo '<div class="col-sm-1 col-xs-1" style="padding-top:5%;">
                        <div class="btn-group" data-toggle="buttons">            
                          <label class="btn btn-success active">
                            <input type="radio" name="'.$id_producto.'_'.$id_atributo.'" id="'.$id_producto.'_'.$id_atributo.'" value="1" autocomplete="off" required>
                            <span class="fa fa-check"></span>
                          </label>		

                          <label class="btn btn-danger">
                          <input type="radio" name="'.$id_producto.'_'.$id_atributo.'" id="'.$id_producto.'_'.$id_atributo.'" value="0" autocomplete="off" >
                            <span class="fa fa-times"></span>
                          </label>          
                        </div>     
                      </div>
                    </div> <!-- Fin row de producto-->
                    <hr>';
            }

            
          } //fin foreach producto


        } //fin pedido con cajas


        //Si el cliente lleva 5 o más pedidos ponemos un check para marcar si se coge el regalo
        if ($numero_pedidos > 4){
          echo '
          <div class="row">
            <div class="form-group form-check" style="padding-left:30%;">
              <label class="btn btn-lg btn-warning">Obsequio: 
                <input type="checkbox" name="obsequio" id="obsequio" required>
              </label>
            </div>
            </div>
            <hr>';      
        }

        //ponemos un textarea para comentarios sobre el picking
        echo '<div class="row"> 
                <div class="form-group col-sm-12  col-xs-12">
                  <label for="comentario">Comentarios:</label>
                  <textarea class="form-control" rows="5" id="comentario" name="comentario"></textarea>
                </div>
              </div>
            </div>'; //div final de container

        //coloco el número de productos en un input hidden para procesaPicking
        echo '<input type="hidden" name="numero_productos" value="'.$numero_productos.'">';
        //coloco el número de pedido en un input hidden para procesaPicking
        echo '<input type="hidden" name="id_pedido" value="'.$pedido.'">';

        //Botón de submit para el formulario. formnovalidate en el botón de cancelar permite hacer submit sin marcar los required etc. Les ponemos a ambos clase submit_boton para en js comprobar cual es el pulsado y usarlo a la hora de mostrar mensaje de confirmación de envío si hay productos no ok
        echo '<div class="row" style="padding-left:30%; margin-bottom:30px;"> 
                <input class="btn btn-lg btn-success submit_boton_picking" type="submit" id="submit_finpicking" name="submit_finpicking" value="Finalizar" />
                <button type="submit" name="submit_volver" class="btn btn-danger submit_boton_picking"  formnovalidate  style="margin-left:10px;">Cancelar</button>
              </div>
          </form>';

        //para almacenar la fecha de inicio de picking, cuando se muestra el pedido hacemos una búsqueda en lafrips_pick_pack. Si comenzado_picking es 0 es que es el primer picking, lo ponemos a 1 y guardamos date now() en date_inicio_picking. Si hay incidencias y se repiten los picking será un dato inválido, la duración del picking solo sirve si es un pedido sin incidencias. También cambiamos el estado de pickpack a Picking Abierto
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
      }
    }//fin if si se encuentra pedido

}


function procesaPicking(){
  //sacamos id y nombre de empleado de la sesión
  $id_empleado = $_SESSION["id_empleado"];
  $nombre_empleado = $_SESSION["nombre_empleado"];
  if (!$id_empleado){
    echo '
    <div class="jumbotron jumbotron_sesion">
      <h1 class="display-4">Hola</h1>
      <p class="lead">Tu sesión ha expirado o nunca se inició</p>
      <p class="lead">Tienes que acceder desde Prestashop, iniciando sesión en La Frikilería</p>
    </div>    
    ';
  }else{
    //sacamos los valores de radio button, textarea y regalo si los hay
    if(isset($_POST['submit_finpicking'])){
      if ($_POST['id_pedido']){
        $id_pedido = $_POST['id_pedido'];
      }
      //para no sobreescribir el comentario, lo añadiremos al anterior si fuera la segunda vez o más que pasamos por aquí o no insertaremos nada si no llega ninguno. Añadimos perosna que lo escribe y fecha
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
      //sacamos los id_product y id_product_atribute de cada producto. Como $_POST contiene los valores del formulario en el orden de este, saco el contenido desde el primero hasta $numero_productos y los almaceno en dos variables, $id_product y $id_product_atribute después de separar la cadena que llega (tipo 12323_2345)
      $contador = 1;
      foreach($_POST as $key => $value) {
        if ($contador <= $numero_productos){
          //$key contiene el id unido al id atributo con barra baja
          $ids = explode('_', $key);
          $id_product = $ids[0];
          $id_product_attribute = $ids[1];
          //marcaremos con uno el producto como incidencia_picking para saber cual estuvo mal en el picking. $incidencia nos señala que el pedido en general ha tenido incidencia. Si el producto pasa una vez como incidencia se marca, pero nunca se vuelve a poner a 0 si se corrige     
          $incidencia_producto = '';   
          //$value contiene 1 o 0 si el radio button estaba en ok o no
          $correcto = $value;
          if (!$correcto){
            $incidencia = 1;
            $incidencia_producto = ' ,incidencia_picking = 1 ';
          }
          //Creamos la sql para hacer update de los productos en picking comprobando producto a producto si está en lafrips_pick_pack_productos (si el update da resultado) y si no lo está lo metemos con un insert, ya que puede darse el caso de que se cambie o añada algún producto si hay incidencia. 
          $sql_update_producto = 'UPDATE lafrips_pick_pack_productos 
          SET ok_picking = '.$correcto.$incidencia_producto.'
          WHERE id_product = '.$id_product.'
          AND id_product_attribute = '.$id_product_attribute.'
          AND id_pickpack_order = '.$id_pedido.';';
          //si no funciona el update, hacemos insert del producto
          if (!$correcto){
            $incidencia_prod = ', incidencia_picking';
            $incidencia_producto = ' ,1 ';
          }else{
            $incidencia_prod = '';
            $incidencia_producto = '';
          }
          if (!Db::getInstance()->Execute($sql_update_producto)) {
            Db::getInstance()->Execute("INSERT INTO lafrips_pick_pack_productos (id_pickpack_order, id_product, id_product_attribute, ok_picking ".$incidencia_prod.") VALUES (".$id_pedido." ,".$id_product." ,".$id_product_attribute." ,".$correcto.$incidencia_producto.")");
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
        $incidencia_picking = ' incidencia_picking =1 ,';
      } else {
        //si no hay incidencia ponemos el estado Picking Finalizado y metemos la fecha
        $id_estado_order = 4;
        //$date_fin_picking = date("Y-m-d H:i:s");
        $date_fin_picking = 'NOW()';
        $incidencia_picking = '';
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

      Db::getInstance()->execute($sql_update_pickpack_pedido);
      

      //mostrar una pantalla de confirmación de envío de picking con botón para volver
      echo '
      <div class="jumbotron jumbotron_picking">
        <h1 class="display-4">Gracias '.$nombre_empleado.'</h1>
        <p class="lead">El picking ha sido procesado</p>
      </div>
      <div class="container" style="margin-bottom:60px;">  
        <form action="picking.php" method="post">      
          <button type="submit" name="submit_volver" class="btn btn-success">Volver</button>
        </form>
      </div>  
      ';

    } else {
      //mostrar una pantalla de error con botón para volver
      echo '
      <div class="jumbotron jumbotron_picking">
        <h1 class="display-4">Hola '.$nombre_empleado.'</h1>
        <p class="lead">Se ha producido un error</p>
      </div>
      <div class="container" style="margin-bottom:60px;">  
        <form action="picking.php" method="post">      
          <button type="submit" name="submit_volver" class="btn btn-success">Volver</button>
        </form>
      </div>  
      ';
    }  
  }
}


?>

</body>
</html>
  