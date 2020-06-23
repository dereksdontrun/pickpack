<?php

/**
* Utilidad para hacer picking y packing prescindiendo de hojas de pedido
*
* @author    Sergio™ <sergio@lafrikileria.com>
*/

//En AdminPacking.php apuntamos a este archivo fuera de Prestashop, se carga la cabecera html y despendiendo de como llega la llamada, GET o POST y si contiene alguna variable lo orientamos a una función u otra, haciendo todo el proceso de packing en este script


?>
<!DOCTYPE html>
<html>
<title>PACKING - La Frikilería</title>
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

//si llegamos aquí desde pickpacklogin.php trae el id de empleado por GET
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
//si llegamos a packing.php desde el formulario de buscarPedido viene por POST y mostraremos el packing
} elseif(isset($_POST['submit_pedido'])){
  mostrarPacking();
} elseif(isset($_POST['submit_finpacking'])){
  procesaPacking();
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
  
  if (!$nombre_empleado){
    //si no hay nombre guardado en sesión enviamos a login
    $token = Tools::getAdminTokenLite('AdminModules');
    $url_modulos = _MODULE_DIR_;

    $url = $url_modulos.'pickpack/controllers/admin/procesos/pickpacklogin.php?token='.$token;  
    //echo '<br>'.$url;
    header("Location: $url");

    // echo '
    // <div class="jumbotron jumbotron_sesion">
    //   <h1 class="display-4">Hola</h1>
    //   <p class="lead">Tu sesión ha expirado o nunca se inició</p>
    //   <p class="lead">Tienes que acceder desde Prestashop, iniciando sesión en La Frikilería</p>
    // </div>    
    // ';
  }else{
    echo '
    <div class="jumbotron jumbotron_packing">
      <h1 class="display-4">Hola '.$nombre_empleado.'</h1>
      <p class="lead">Bienvenido a los packings de La Frikilería</p>
    </div>
    <div class="container" style="margin-bottom:60px;">  
      <form action="packing.php" method="post"> 
        <div class="form-group"> 
          <label for="text">Introduce el identificador del pedido para realizar el PACKING:</label> 
          <input type="text" name="id_pedido" size="30"  class="form-control">
        </div>
        <button type="submit" name="submit_pedido" class="btn btn-primary">Buscar</button>
      </form>
    </div>  
    ';
  } 

}

function mostrarPacking(){
  if (!$_SESSION["id_empleado"]){
    //si no hay nombre guardado en sesión enviamos a login
    $token = Tools::getAdminTokenLite('AdminModules');
    $url_modulos = _MODULE_DIR_;

    $url = $url_modulos.'pickpack/controllers/admin/procesos/pickpacklogin.php?token='.$token;  
    //echo '<br>'.$url;
    header("Location: $url");

    // echo '
    // <div class="jumbotron jumbotron_sesion">
    //   <h1 class="display-4">Hola</h1>
    //   <p class="lead">Tu sesión ha expirado o nunca se inició</p>
    //   <p class="lead">Tienes que acceder desde Prestashop, iniciando sesión en La Frikilería</p>
    // </div>    
    // ';
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
    
    //sacamos info del cliente y pedido. LEFT JOIN en tabla state porque amazon introduce las address sin id_state
    //añadido JOIN a tabla pick_pack para que solo muestre pedidos que estén en la tabla
    $sql_info_pedido = "SELECT ord.id_customer AS id_cliente, CONCAT(cus.firstname,' ', cus.lastname) AS nombre_cliente, CONCAT(adr.address1,' ', adr.address2) AS direccion, adr.postcode AS codigo_postal, ord.payment AS metodo_pago, ord.module AS module, osl.name AS estado_prestashop,
    adr.city AS ciudad, sta.name AS provincia, col.name AS pais, ord.date_add AS fecha_pedido, car.name AS transporte, ord.gift AS regalo, 
    ord.gift_message AS mensaje_regalo, cus.note AS nota_sobre_cliente, adr.phone_mobile AS tlfno1, adr.phone AS tlfno2, ppe.nombre_estado AS 'estado_pickpack'  
    FROM lafrips_customer cus
    JOIN lafrips_orders ord ON ord.id_customer = cus.id_customer
    JOIN lafrips_address adr ON ord.id_address_delivery = adr.id_address
    JOIN lafrips_country_lang col ON adr.id_country = col.id_country
    LEFT JOIN lafrips_state sta ON sta.id_state = adr.id_state
    JOIN lafrips_carrier car ON car.id_carrier = ord.id_carrier
    JOIN lafrips_order_state ors ON ors.id_order_state = ord.current_state
    JOIN lafrips_order_state_lang osl ON ors.id_order_state = osl.id_order_state AND osl.id_lang = 1
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
      <div class="jumbotron jumbotron_packing">
        <h1 class="display-4">Hola '.$nombre_empleado.'</h1>
        <p class="lead">El pedido que buscas no existe o no está disponible para Packing</p>';
        if ($error_tracking){
          echo '<p class="lead">'.$error_tracking.'</p>';
        }
        echo '</div>
      <div class="container" style="margin-bottom:60px;">  
        <form action="packing.php" method="post">      
          <button type="submit" name="submit_volver" class="btn btn-success">Volver</button>
        </form>
      </div>  
      ';       
    } else {
      //asignamos los datos a variables
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

      //info de productos en pedido
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
      ORDER BY wpl.location;";

      $productos_pedido = Db::getInstance()->ExecuteS($sql_productos_pedido);  

      //mostramos los datos
      //cabecera con datos de cliente
      echo '
      <div class="jumbotron jumbotron_packing">
        <h1 class="display-4"><span style="font-size: 50%;">PACKING</span> <strong>'.$pedido.'</strong> <span style="font-size: 50%;">'.$fecha_pedido.'</span></h1>
        <h5><span style="font-size: 80%;">Estado Pick Pack actual</span> <strong>'.$estado_pickpack.'</strong></h5>';
        if ($empleado_picking && $empleado_picking !== ''){
          echo '<p class="empleado">Picking realizado por '.$empleado_picking.'</p>';
        }else{
          echo '<p class="empleado">Picking no realizado o empleado desconocido</p>';
        }         
        echo '<div class="row"> 
        <div class="panel datos_cliente col-md-4">
          <address>          
            <strong>'.$nombre_cliente.'</strong><br>
            '.$direccion.'<br>
            '.$codigo_postal.' '.$ciudad.' - '.$provincia.' - '.$pais.'<br>
          </address>
          <b>Nº Pedidos:</b> ';
          if ($numero_pedidos > 4){
            echo '<span class="badge badge-pill badge-warning">'.$numero_pedidos.'</span><br>';
          }else{
            echo $numero_pedidos.'<br>';
          } 
          if ($nota_sobre_cliente){
            echo '<b>Notas sobre cliente:</b>              
                  <blockquote class="blockquote padquote nota_cliente">  
                    <p>'.$nota_sobre_cliente.'</p>
                  </blockquote>';
          }           
        echo '</div> </br>
        <div class="panel datos_cliente col-md-4">
          <b>Pagado:</b> '.$metodo_pago.'<br>';
          if ($amazon){
            echo '<h3><span class="badge badge-pill badge-info">AMAZON</span></h3>';
          }        
          echo '<b>Transporte:</b><br> <h3><span class="badge badge-pill badge-warning">'.$transporte.'</span></h3>';
          // echo '<b>Estado:</b> '.$estado_prestashop.'<br>';
          echo '<b>Envuelto para Regalo: </b>'; if ($regalo){ echo '<h3><span class="badge badge-pill badge-danger"> SI</span></h3>'; }else{ echo ' No<br>'; } 
          if ($regalo){ 
            //ponemos nl2br() para respetar saltos de línea etc del mensaje
            echo '<b>Mensaje regalo:</b> 
              <blockquote class="blockquote padquote nota_regalo">  
                <p>'.nl2br($mensaje_regalo).'</p>
              </blockquote>';
          }  
        echo '</div>
        </div>
      </div>   
      ';
      //mostramos los mensajes sobre el pedido si los hay.
      if ($todos_mensajes_pedido){
      echo '
      <div class="container container_mensajes">  
        <div class="panel">
          <h3>Mensajes de pedido:</h3>
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

      //mostramos los comentarios de picking si los hay. Los sacamos primero
      $sql_comentario_picking = 'SELECT comentario_picking FROM lafrips_pick_pack WHERE id_pickpack_order = '.$pedido;  
      $comentario_picking = Db::getInstance()->getValue($sql_comentario_picking); 
      if ($comentario_picking){
        echo '<br>
        <div class="container container_comentario">  
          <div class="panel">
            <h3>Comentario de picking:</h3>
            <blockquote class="blockquote padquote">
            <p>'.$comentario_picking.'</p>        
          </blockquote>
          </div>
        </div>';
      }


      //necesito saber el número de productos del pedido cuando envíe el formulario, de modo que usamos una variable $numero_productos que se insertará en un input hidden y se recogerá después en la función procesaPacking para sacr bien los id de producto y su estado al hacer packing
      $numero_productos = count($productos_pedido);
      
      //líneas de productos
      echo '
      <div class="container container_productos">  
        <h3>Productos - '.$numero_productos.'</h3>
        <form id="formulario_packing" action="packing.php" method="post"> ';   

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

          //datos del producto
          echo '
            <div class="col-sm-5 col-xs-6">
              <p class="h5">'.$nombre_producto.'</p>
              REF: '.$referencia_producto.'<br/>';
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

        //Si el cliente ha pedido envuelto para regalo
        if ($regalo){
            echo '
            <div class="row">
              <div class="form-group form-check" style="padding-left:30%;">
                <label class="btn btn-lg btn-warning">Envuelto regalo / nota: 
                  <input type="checkbox" name="checkbox_regalo" id="checkbox_regalo" required>
                </label>
              </div>
              </div>
              <hr>';      
          }    

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

        //ponemos un textarea para comentarios sobre el packing
        echo '<div class="row"> 
                <div class="form-group col-sm-12  col-xs-12">
                  <label for="comentario">Comentarios:</label>
                  <textarea class="form-control" rows="5" id="comentario" name="comentario"></textarea>
                </div>
              </div>
            </div>'; //div final de container

        //coloco el número de productos en un input hidden para procesaPacking
        echo '<input type="hidden" name="numero_productos" value="'.$numero_productos.'">';
        //coloco el número de pedido en un input hidden para procesaPacking
        echo '<input type="hidden" name="id_pedido" value="'.$pedido.'">';

        //Botón de submit para el formulario. formnovalidate en el botón de cancelar permite hacer submit sin marcar los required etc
        echo '<div class="row" style="padding-left:30%; margin-bottom:30px;"> 
                <input class="btn btn-lg btn-success submit_boton_packing" type="submit" id="submit_finpacking" name="submit_finpacking" value="Finalizar" />
                <button type="submit" name="submit_volver" class="btn btn-danger submit_boton_packing"  formnovalidate  style="margin-left:10px;">Cancelar</button>
              </div>
          </form>';

        //para almacenar la fecha de inicio de packing, cuando se muestra el pedido hacemos una búsqueda en lafrips_pick_pack. Si comenzado_packing es 0 es que es el primer packing, lo ponemos a 1 y guardamos date now() en date_inicio_packing. Si hay incidencias y se repiten los packing será un dato inválido, la duración del packing solo sirve si es un pedido sin incidencias. También cambiamos el estado de pickpack a Packing Abierto
        $sql_comenzado_packing = 'SELECT comenzado_packing FROM lafrips_pick_pack WHERE id_pickpack_order = '.$pedido;
        $comenzado_packing = Db::getInstance()->ExecuteS($sql_comenzado_packing);
        //si comenzado_packing es 0 marcamos inicio picking
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
      }
  }//fin if si no encuentra pedido

}


function procesaPacking(){
  //sacamos id y nombre de empleado de la sesión
  $id_empleado = $_SESSION["id_empleado"];
  $nombre_empleado = $_SESSION["nombre_empleado"];
  if (!$id_empleado){
    //si no hay nombre guardado en sesión enviamos a login
    $token = Tools::getAdminTokenLite('AdminModules');
    $url_modulos = _MODULE_DIR_;

    $url = $url_modulos.'pickpack/controllers/admin/procesos/pickpacklogin.php?token='.$token;  
    //echo '<br>'.$url;
    header("Location: $url");

    // echo '
    // <div class="jumbotron jumbotron_sesion">
    //   <h1 class="display-4">Hola</h1>
    //   <p class="lead">Tu sesión ha expirado o nunca se inició</p>
    //   <p class="lead">Tienes que acceder desde Prestashop, iniciando sesión en La Frikilería</p>
    // </div>    
    // ';
  }else{
    //sacamos los valores de radio button, textarea y regalo si los hay
    if(isset($_POST['submit_finpacking'])){
      if ($_POST['id_pedido']){
        $id_pedido = $_POST['id_pedido'];
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
      //sacamos los id_product y id_product_atribute de cada producto. Como $_POST contiene los valores del formulario en el orden de este, saco el contenido desde el primero hasta $numero_productos y los almaceno en dos variables, $id_product y $id_product_atribute después de separar la cadena que llega (tipo 12323_2345)
      $contador = 1;
      foreach($_POST as $key => $value) {
        if ($contador <= $numero_productos){
          //$key contiene el id unido al id atributo con barra baja
          $ids = explode('_', $key);
          $id_product = $ids[0];
          $id_product_attribute = $ids[1];
          //$value contiene 1 o 0 si el radio button estaba en ok o no
          $correcto = $value;
          if (!$correcto){
            $incidencia = 1;
            $incidencia_producto = ' ,incidencia_packing = 1 ';
          }
          //Creamos la sql para hacer update de los productos en packing comprobando producto a producto si está en lafrips_pick_pack_productos (si el update da resultado) y si no lo está lo metemos con un insert, ya que puede darse el caso de que se cambie o añada algún producto si hay incidencia. 
          $sql_update_producto = 'UPDATE lafrips_pick_pack_productos 
          SET ok_packing = '.$correcto.$incidencia_producto.'
          WHERE id_product = '.$id_product.'
          AND id_product_attribute = '.$id_product_attribute.'
          AND id_pickpack_order = '.$id_pedido.';';
          //si no funciona el update, hacemos insert del producto
          if (!$correcto){
            $incidencia_prod = ', incidencia_packing';
            $incidencia_producto = ' ,1 ';
          }else{
            $incidencia_prod = '';
            $incidencia_producto = '';
          }
          if (!Db::getInstance()->Execute($sql_update_producto)) {
            Db::getInstance()->Execute("INSERT INTO lafrips_pick_pack_productos (id_pickpack_order, id_product, id_product_attribute, ok_packing ".$incidencia_prod.") VALUES (".$id_pedido." ,".$id_product." ,".$id_product_attribute." ,".$correcto.$incidencia_producto.")");
          }
          //Si algún producto que estaba en un packing anterior ya no se recibe en este packing, pondremos a 1 "eliminado". SIN HACERRRRR!!        

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
        //si hay incidencia ponemos o dejamos el estado Incidencia Packing, y añadimos incidencia_packing=1
        $id_estado_order = 5;
        $date_fin_packing = '"0000-00-00 00:00:00"';
        $finalizado = 0;
        $incidencia_packing = ' incidencia_packing =1 ,';
      } else {
        //si no hay incidencia ponemos el estado Packing Finalizado y metemos la fecha
        $id_estado_order = 6;
        // $date_fin_packing = date("Y-m-d H:i:s");
        $date_fin_packing = 'NOW()';
        $finalizado = 1;
        $incidencia_packing = '';
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

      Db::getInstance()->execute($sql_update_pickpack_pedido);
      

      //mostrar una pantalla de confirmación de envío de packing con botón para volver
      echo '
      <div class="jumbotron jumbotron_packing">
        <h1 class="display-4">Gracias '.$nombre_empleado.'</h1>
        <p class="lead">El packing ha sido procesado</p>
      </div>
      <div class="container" style="margin-bottom:60px;">  
        <form action="packing.php" method="post">      
          <button type="submit" name="submit_volver" class="btn btn-success">Volver</button>
        </form>
      </div>  
      ';

    } else {
      //mostrar una pantalla de error con botón para volver
      echo '
      <div class="jumbotron jumbotron_packing">
        <h1 class="display-4">Hola '.$nombre_empleado.'</h1>
        <p class="lead">Se ha producido un error</p>
      </div>
      <div class="container" style="margin-bottom:60px;">  
        <form action="packing.php" method="post">      
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