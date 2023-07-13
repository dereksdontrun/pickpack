<!-- /**
* Utilidad para hacer picking y packing prescindiendo de hojas de pedido
*
* @author    Sergio™ <sergio@lafrikileria.com>
*/ -->
<?php

include('header.php');
// var_dump($_SESSION);
?>

<div class="jumbotron jumbotron_<?= $_SESSION["funcionalidad"] ?>" style="padding-top:10px;">
    <h1 class="display-5">Hola <?= $_SESSION["nombre_empleado"] ?>  
        
        <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown"><?= $_SESSION["nombre_empleado"] ?>
        <span class="caret"></span></button>
        <ul class="dropdown-menu">        
            <li><a class="dropdown-item" href="<?= _MODULE_DIR_.'pickpack/controllers/admin/pickingpacking/pickpackindex.php?cerrar_sesion=1'; ?>">  Cerrar Sesión</a></li>              
        </ul>

    </h1>
    <!-- <p class="lead"><span class="capitalizar"><?= $_SESSION["funcionalidad"] ?></span> de La Frikilería</p> -->
    <small><span class="capitalizar"><?= $_SESSION["funcionalidad"] ?></span> de La Frikilería</small>

    <?php
    if ($mensaje_ok_ubicacion || $mensaje_ok_recepcion || $mensaje_warning_recepcion || $incidencia){
    ?>
    <p class="lead">
        <?php
        if ($mensaje_ok_ubicacion){
        ?>
        <span style="font-size: 17px;" class="badge badge-pill badge-success">¡Localizaciones actualizadas!</span>
        <?php
        }
        ?>

        <?php
        if ($mensaje_ok_recepcion){
        ?>
        <span style="font-size: 17px;" class="badge badge-pill badge-success">¡Cantidad de recepción almacenada!</span>
        <?php
        }
        ?>

        <?php
        if ($mensaje_warning_recepcion){
        ?>
        <span style="font-size: 17px;" class="badge badge-pill badge-warning">¡Cantidad superior a la esperada!</span>
        <?php
        }
        ?>

        <?php
        if ($incidencia){
        ?>
        <span style="font-size: 17px;" class="badge badge-pill badge-warning"><?= $mensaje_incidencia ?></span>
        <?php
        }
        ?>
    </p>
    <?php
    }
    ?>    
    
    <div class="container" style="margin-bottom:20px;">  
    <form action="ubicaciones.php" method="post"> 
        <div class="form-group">         
            <label for="text">Introduce el Ean del producto:</label> 
        
            <input type="text" name="ean" size="30"  class="form-control" autofocus>
        </div>
        <button type="submit" name="submit_ean" class="btn btn-primary">Buscar</button>
    </form>
    </div>  
</div>