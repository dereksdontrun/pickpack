<!-- /**
* Utilidad para hacer picking y packing prescindiendo de hojas de pedido
*
* @author    Sergio™ <sergio@lafrikileria.com>
*/ -->
<?php

include('header.php');

?>

<div class="jumbotron jumbotron_<?= $action ?>" style="padding-top:10px;">
    <h1 class="display-4">Hola <?= $_SESSION["nombre_empleado"] ?>  
        
    <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown"><?= $_SESSION["nombre_empleado"] ?>
    <span class="caret"></span></button>
    <ul class="dropdown-menu">
        <li><a href="<?= _MODULE_DIR_.'pickpack/controllers/admin/pickingpacking/pickpackindex.php?cerrar_sesion=1'; ?>">  Cerrar Sesión</a></li>              
    </ul>

    </h1>
    <p class="lead">Bienvenido a los <?= $action ?>s de La Frikilería</p>
    <?php if ($incidencia_packing) { ?>
        <p class="lead" >ATENCIÓN: Debido a una incidencia no se procedió al cambio de estado de Prestashop del/los pedidos recién procesados</p>
    <?php } ?>
    <?php if ($_SESSION["varios"]) { ?>
        <p class="lead">ATENCIÓN: Vas a proceder a realizar el <?= $action ?> de varios pedidos</p>
    <?php } ?>
    <div class="container" style="margin-bottom:20px;">  
    <form action="<?= $action ?>.php" method="post"> 
        <div class="form-group"> 
        <?php if ($_SESSION["varios"]) { ?>
            <input type="hidden" name="varios" value="1">
            <label for="text">Introduce el identificador del pedido. <br>Buscaremos también todos los pedidos no enviados, con el transporte ¡Guárdamelo! y asociados al cliente de este pedido. Asegúrate de que los pedidos están preparados para su envío.</label>
        <?php } else { ?>
            <label for="text">Introduce el identificador del pedido para realizar el <span class="mayusculas"><?= $action ?></span>:</label> 
        <?php } ?>
        <input type="text" name="id_pedido" size="30"  class="form-control" autofocus>
        </div>
        <button type="submit" name="submit_pedido" class="btn btn-primary">Buscar</button>
    </form>
    </div>  
</div>