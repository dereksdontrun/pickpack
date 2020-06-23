<?php

/**
* Utilidad para hacer picking y packing prescindiendo de hojas de pedido
*
* @author    Sergio™ <sergio@lafrikileria.com>
*/
//Este controlador solo tiene la función de redirigirnos mediante un header hacia la página de hacer picking, fuera del marco de Prestashop

if (!defined('_PS_VERSION_'))
    exit;

class AdminPickingController extends ModuleAdminController {

  public function __construct() {    

    parent::__construct();

    return true;
  }

  /**
     * AdminController::init() override
     * @see AdminController::init()
     */
  public function init() {
    //sacamos los datos para generar la url a la que queremos dirigir al usuario
    $id_empleado = Context::getContext()->employee->id;
    $token = Tools::getAdminTokenLite('AdminModules');
    $url_modulos = _MODULE_DIR_;

    $url = $url_modulos.'pickpack/controllers/admin/procesos/picking.php?token='.$token.'&id_empleado='.$id_empleado;  

    header("Location: $url");
  }

}

?>

