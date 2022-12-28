<?php

/**
* Utilidad para hacer picking y packing prescindiendo de hojas de pedido
*
* @author    Sergio™ <sergio@lafrikileria.com>
*/

// https://lafrikileria.com/test/modules/pickpack/controllers/admin/pickingpacking/pickpackindex.php


require_once(dirname(__FILE__).'/../../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../../init.php');
//hacemos include de la clase para poder usar la función de buscar id_pickpack por id_order
//aquí no es necesario
// include dirname(__FILE__).'/../../../classes/PickPackOrder.php';
//01/12/2022 metemos herramientas para poder llamar a pickpackLog()
require_once(dirname(__FILE__).'/controllers/herramientas.php');

//si entramos después de pulsar un submit direccionamos al proceso asignado al submit, picking o packing, si no, mostramos login
if (isset($_POST['submit_hacer_picking'])){

  if ($id_usuario = $_POST['usuario']){
    $token = Tools::getAdminTokenLite('AdminModules');
    $url_modulos = _MODULE_DIR_;

    $url = $url_modulos.'pickpack/controllers/admin/pickingpacking/controllers/picking.php?token='.$token.'&id_empleado='.$id_usuario;  
    //echo '<br>'.$url;
    header("Location: $url");
  } else {
    $token = Tools::getAdminTokenLite('AdminModules');
    $url_modulos = _MODULE_DIR_;
    
    $url = $url_modulos.'pickpack/controllers/admin/pickingpacking/pickpackindex.php?token='.$token;  
    
    header("Location: $url");
  } 


} elseif(isset($_POST['submit_hacer_packing'])){
  $id_usuario = $_POST['usuario'];


  $token = Tools::getAdminTokenLite('AdminModules');
  $url_modulos = _MODULE_DIR_;

  $url = $url_modulos.'pickpack/controllers/admin/pickingpacking/controllers/packing.php?token='.$token.'&id_empleado='.$id_usuario;  
  //echo '<br>'.$url;
  header("Location: $url");
} elseif (isset($_POST['submit_picking_varios'])){ 
  if ($id_usuario = $_POST['usuario']){
    $token = Tools::getAdminTokenLite('AdminModules');
    $url_modulos = _MODULE_DIR_;

    //añadimos a la url el parámetro varios con valor 1
    $url = $url_modulos.'pickpack/controllers/admin/pickingpacking/controllers/picking.php?token='.$token.'&varios=1&id_empleado='.$id_usuario;  
    //echo '<br>'.$url;
    header("Location: $url");
  } else {
    $token = Tools::getAdminTokenLite('AdminModules');
    $url_modulos = _MODULE_DIR_;
    
    $url = $url_modulos.'pickpack/controllers/admin/pickingpacking/pickpackindex.php?token='.$token;  
    
    header("Location: $url");
  }

} elseif (isset($_POST['submit_packing_varios'])){ 
  if ($id_usuario = $_POST['usuario']){
    $token = Tools::getAdminTokenLite('AdminModules');
    $url_modulos = _MODULE_DIR_;

    //añadimos a la url el parámetro varios con valor 1
    $url = $url_modulos.'pickpack/controllers/admin/pickingpacking/controllers/packing.php?token='.$token.'&varios=1&id_empleado='.$id_usuario;  
    //echo '<br>'.$url;
    header("Location: $url");
  } else {
    $token = Tools::getAdminTokenLite('AdminModules');
    $url_modulos = _MODULE_DIR_;
    
    $url = $url_modulos.'pickpack/controllers/admin/pickingpacking/pickpackindex.php?token='.$token;  
    
    header("Location: $url");
  }

} else {    
  //comprobamos si venimos del botón de Cerrar Sesión por el GET
  if (isset($_GET['cerrar_sesion']) && $_GET['cerrar_sesion'] == 1) {    
    //hacemos log
    pickpackLog(0, 0, 'cerrar_sesion');
  }
  //enviamos a login
  $token = Tools::getAdminTokenLite('AdminModules');
  $url_modulos = _MODULE_DIR_;

  $url = $url_modulos.'pickpack/controllers/admin/pickingpacking/controllers/login.php?token='.$token;  
  //echo '<br>'.$url;
  header("Location: $url");

  
}


    

?>

</body>
</html>
  