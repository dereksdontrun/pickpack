<!-- /**
* Utilidad para hacer picking y packing prescindiendo de hojas de pedido
*
* @author    Sergio™ <sergio@lafrikileria.com>
*/ -->

<?php

//Aquí colocamos las funciones que se van a repetir para obtener los datos que se necesitan para picking y para packing

require_once(dirname(__FILE__).'/../../../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../../../init.php');
//hacemos include de la clase para poder usar la función de buscar id_pickpack por id_order
include dirname(__FILE__).'/../../../../classes/PickPackOrder.php';

//Función para obtener el nombre de empleado
function obtenerEmpleado($id_empleado){    
    
    $sql_nombre_empleado = 'SELECT firstname FROM lafrips_employee WHERE id_employee = '.$id_empleado;
    $nombre_empleado = Db::getInstance()->ExecuteS($sql_nombre_empleado);
    $nombre_empleado = $nombre_empleado[0]['firstname'];

    return $nombre_empleado;
}

//Función para obtener el id de pedido en función de lo introducido en el formulario
function obtenerIdOrder($pedido){    
    
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

      //23/06/2021 añadimos mrw de nuevo. El código escaneado son 22 cifras, 6 iniciales, el tracking, y 3 finales. Pero introduce un 0 en el tracking. El tracking comienza con nuestro número de cliente, 02403, pone un 0 y sigue con el resto del tracking. 

      //18/10/2021 Hemos añadido DHL. Al escanear la etiqueta sale el tracking sin más, de 7 cifras, con lo que se detecta sin tener que buscar en el resultado del escaneo.

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

              //23/06/2021 añadimos mrw
              if (!$tracking){
                //Si no ha coincidido, probamos quitando los últimos 3 caracteres y los 6 primeros, y al resultado le eliminamos el 0 que está en la posición 5, para MRW
                $pedido_mrw = substr_replace(substr($pedido, 6, -3),"",5,1);
                $sql_busca_pedido_desde_tracking = 'SELECT id_order FROM lafrips_order_carrier WHERE tracking_number = "'.$pedido_mrw.'";';
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

    return array($pedido , $error_tracking);
}

//Función para obtener la información del pedido y del cliente que mostraremos en pantalla de picking y de packing
function infoOrder($pedido){    
  //sacamos info del cliente y pedido. LEFT JOIN en tabla state porque amazon introduce las address sin id_state.
  //JOIN a tabla pick_pack para que solo muestre pedidos que estén en la tabla
  //sacamos si es envuelto para regalo para no mostrar mensaje de obsequio en ese caso
  //sacamos id_cart para buscar más tarde el id_customization si hubiera caja sorpresa
  $sql_info_pedido = "SELECT ord.id_customer AS id_cliente, CONCAT(cus.firstname,' ', cus.lastname) AS nombre_cliente, CONCAT(adr.address1,' ', adr.address2) AS direccion, adr.postcode AS codigo_postal, 
  adr.city AS ciudad, sta.name AS provincia, col.name AS pais, ord.date_add AS fecha_pedido, car.name AS transporte, 
  ord.module AS module, ord.payment AS metodo_pago, osl.name AS estado_prestashop, ppe.nombre_estado AS 'estado_pickpack', ord.gift AS regalo, ord.id_cart AS id_cart, ord.gift_message AS mensaje_regalo, cus.note AS nota_sobre_cliente, adr.phone_mobile AS tlfno1, adr.phone AS tlfno2  
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

  return Db::getInstance()->ExecuteS($sql_info_pedido);

}


//Función para obtener la información del pedido y del cliente que mostraremos en pantalla de picking y de packing
function infoProducts($pedido, $ids_pedidos, $action){ 
  //info de productos en pedido
  //$ids_pedidos contiene 0 si se llama desde el proceso normal de picking o packing para sacar los productos. Si después se chequea con la función checkCajas la lista de productos y contiene alguna caja, se llamará de nuevo a esta función con el/los id_order de los pedidos virtuales que corresponden a cada caja en el parámetro $ids_pedidos
  if ($ids_pedidos) {
    $ids_pedidos = implode(',', $ids_pedidos);
    $pedidos = $pedido.','.$ids_pedidos;
  } else {
    $pedidos = $pedido;
  }
  //si $action es picking, la llamada viene de checkcajas, y ha encontrado cajas. Devolvemos esta consulta ordenando por localización. Si $action es packing, debemos ordenar agrupando por pedidos, y si es 0, lo dejamos como para picking. Las cajas sorpresa, de momento las ponemos al final
  if ($action == 'picking') {
    $ordenar = ' ORDER BY FIELD(ode.product_id, 5344) ASC, wpl.location';
  } elseif ($action == 'packing') {
    $ordenar = ' ORDER BY FIELD(ode.product_id, 5344) ASC, ode.id_order';
  } else {
    $ordenar = ' ORDER BY FIELD(ode.product_id, 5344) ASC, wpl.location';
  }
  
  //sacamos el campo de lafrips_product 'customizable'. Tiene valor 0 si no lo es, 1 si es pero no es required el campo, 2 si es required.
  //23/12/2020 aparte de customizable, sacamos customizable_data, que es un concat de los campos value de customización, para cuando el producto es carta hogwarts, sacando una cadena con el o los nombres para la carta, ya que al ser el mismo id_producto e id_cart aunque haya varias cartas van a salir como una con varias unidades. Hacemos JOIN a lafrips_orders para obtener el id_cart del pedido
  $sql_productos_pedido = "SELECT ode.id_order AS id_order, ode.product_id AS id_producto, ode.product_attribute_id AS id_atributo, ode.product_name AS nombre_completo,    
  ode.product_reference AS referencia_producto, ode.product_ean13 AS ean, ode.product_quantity AS cantidad, ode.unit_price_tax_incl AS precio_producto,  
  CONCAT( 'http://lafrikileria.com', '/', img.id_image, '-home_default/', 
  pla.link_rewrite, '.', 'jpg') AS 'imagen', img.id_image AS 'existe_imagen', pro.customizable AS customizable,
  (SELECT GROUP_CONCAT(cud.value SEPARATOR ', ') FROM lafrips_customized_data cud JOIN lafrips_customization cust ON cud.id_customization = cust.id_customization
  WHERE cust.id_product = ode.product_id AND cust.id_product_attribute = ode.product_attribute_id AND cust.id_cart = ord.id_cart) 
  AS customizable_data,
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
  JOIN lafrips_orders ord ON ode.id_order = ord.id_order
  WHERE ode.id_order IN (".$pedidos.") 
  AND wpl.id_warehouse = 1 
  GROUP BY ode.id_order, ode.product_id, ode.product_attribute_id
  ".$ordenar.";";
  //ORDER BY FIELD(ode.product_id, 5344) ASC, wpl.location;"; 
  //ORDER BY wpl.location;";

  return Db::getInstance()->ExecuteS($sql_productos_pedido); 

}

//Función para comprobar si entre los productos de un pedido hay cajas sorpresa. Si las hay deberá retornar también los productos correspondientes a los pedidos que forman esas cajas sorpresa.
//23/11/2020 Modificamos para que si el argumento action de la función es obtener_ids_cajas, lo que devuelva sea solo los ids de los pedidos que contienen los productos de las cajas, si los hay. Si no los hay, devuelve 0
function checkCajas($productos_pedido , $action, $pedido, $id_cart){ 
  //debemos comprobar si en la lista de productos hay alguna caja (customizable != 0)  
  $array_customizables = array();
  foreach ($productos_pedido AS $producto) {
    if (($producto['customizable'] != 0) && ($producto['id_producto'] == 5344)) {      
      //se ha encontrado al menos una caja, buscamos su id_customization
      $sql_customization = 'SELECT id_customization FROM lafrips_customization
        WHERE id_cart = '.$id_cart.'
        AND id_product = 5344
        AND id_product_attribute = '.$producto['id_atributo'];
      $customization = Db::getInstance()->ExecuteS($sql_customization);  
      //si sale más de una caja, es decir, más de un id_customization (dos cajas del mismo precio en mismo pedido darán aquí más de un resultado ya que tienen mismo carro, id_product e id_product_attribute) buscamos los id_customization en $array_customizables hasta no encontrarlo, entonces lo usamos y lo metemos al array
      if (count($customization) > 1){
        foreach($customization AS $custom){
          if (in_array($custom['id_customization'], $array_customizables)){
            //si el id ya está en el array pasamos al siguiente
            continue;
          } else {  
            //si el id no está en el array lo metemos 
            $id_customization = $custom['id_customization'];         
            array_push($array_customizables, $id_customization); 
          }
        }
      } else { //si solo da resultado de un id_customization lo metemos al array  
        $id_customization = $customization[0]['id_customization'];      
        array_push($array_customizables, $id_customization);
      }
      

      if (!$id_customization) {
        //si a pesar de haber cajas no se encuentra su id_customization, se ha producido algún error
        return 0;
      } 
      
    } //end if caja+customizable    
  } //end foreach

  //si no se encuentran cajas devolvemos lo que recibimos, salvo que se haya llamado a checkCajas para obtenr los ids de losp pedidos que contienen lo productos de las cajas
  if (empty($array_customizables)) {
    if ($action == 'obtener_ids_cajas') {
      return 0;
    } else {
      return $productos_pedido;
    }

  } else {
    //buscamos el id_order correspondiente a cada id_customization que se haya encontrado en la tabla frik_cajas_sorpresa. Cada uno debe corresponder a una caja sorpresa, que si el pedido está para hacer picking quiere decir que sus cajas ya deben estar hechas
    $array_id_order_cajas = array();
    foreach ($array_customizables AS $id_customization){
      $sql_id_order_caja_sorpresa = 'SELECT id_order_caja FROM frik_cajas_sorpresa
      WHERE id_order = '.$pedido.'
      AND id_customization = '.$id_customization;
      $id_order_caja_sorpresa = Db::getInstance()->ExecuteS($sql_id_order_caja_sorpresa)[0]['id_order_caja'];  

      if (!$id_order_caja_sorpresa) {
        return 0;
      } else {
        array_push($array_id_order_cajas, $id_order_caja_sorpresa);
      }
      
    }
    
    //si el proceso es picking, devolvemos una nueva búsqueda de los productos, donde habremos añadido los contenidos de las cajas sorpresa como productos ordenados por localización, añadiendo el where id_order in (ids de cajas sorpresa)
    if ($action == 'picking') {
      return infoProducts($pedido, $array_id_order_cajas, $action);
    }


    //si el proceso es packing,  tenemos que devolver los productos agrupados por el pedido que los contiene, por si hubiera cajas sorpresa
    if ($action == 'packing') {
      return infoProducts($pedido, $array_id_order_cajas, $action);
    }

    //si el proceso es obtener_ids_cajas devolvemos solo $array_id_order_cajas
    if ($action == 'obtener_ids_cajas') {
      return $array_id_order_cajas;
    }
  }

}

//Función para obtener los mensajes del cliente para la creación de su caja sorpresa, recibimos el id_order del pedido que la contiene y buscamos el id_customization y después el mensaje, devolviendo este último
function mensajeCaja($id_pedido_caja_sorpresa){
  //buscamos el id_customization que le corresponde si la caja ya ha sido creada 
  $sql_id_customization_caja_sorpresa = 'SELECT id_customization, id_order, id_product, id_product_attribute FROM frik_cajas_sorpresa
  WHERE id_order_caja = '.$id_pedido_caja_sorpresa;
  $id_customization_caja_sorpresa = Db::getInstance()->ExecuteS($sql_id_customization_caja_sorpresa);  

  if (!$id_customization_caja_sorpresa || count($id_customization_caja_sorpresa) > 1) {
    //si no hay id_customization es que la caja aún no se ha hecho, si hay varios es un error
    return 'error';
  } else {
    //obtenemos el nombre completo de la caja vendida en la tabla order_detail
    $sql_nombre_caja = 'SELECT product_name FROM lafrips_order_detail 
    WHERE id_order = '.$id_customization_caja_sorpresa[0]['id_order'].'
    AND product_id = '.$id_customization_caja_sorpresa[0]['id_product'].'
    AND product_attribute_id = '.$id_customization_caja_sorpresa[0]['id_product_attribute'];
    
    if (Db::getInstance()->ExecuteS($sql_nombre_caja)) {
      $nombre_caja = Db::getInstance()->ExecuteS($sql_nombre_caja)[0]['product_name'].'<br><br>'; 
    } else {
      $nombre_caja = ''; 
    }    

    $id_customization_caja_sorpresa = $id_customization_caja_sorpresa[0]['id_customization'];

    //buscamos el mensaje de cliente
    $sql_mensaje_cliente_caja = 'SELECT value FROM lafrips_customized_data
    WHERE id_customization = '.$id_customization_caja_sorpresa;
    if ($mensaje_cliente_caja = Db::getInstance()->ExecuteS($sql_mensaje_cliente_caja)) {
      return $nombre_caja.$mensaje_cliente_caja[0]['value'];
    } else {
      return 'error';
    }
    
  }

}

?>