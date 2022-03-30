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
        <li><a href="<?= _MODULE_DIR_.'pickpack/controllers/admin/pickingpacking/pickpackindex.php'; ?>">  Cerrar Sesión</a></li>              
    </ul>

    </h1>
    <p class="lead">Bienvenido a los <?= $action ?>s de La Frikilería</p>
    <div class="container" style="margin-bottom:20px;">  
    <form action="<?= $action ?>.php" method="post"> 
        <div class="form-group"> 
        <label for="text">Introduce el identificador del pedido para realizar el <span class="mayusculas"><?= $action ?></span>:</label> 
        <input type="text" name="id_pedido" size="30"  class="form-control" autofocus>
        </div>
        <button type="submit" name="submit_pedido" class="btn btn-primary">Buscar</button>
    </form>
    </div>  
</div>