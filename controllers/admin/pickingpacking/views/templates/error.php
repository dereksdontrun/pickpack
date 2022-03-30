<?php

include('header.php');

?>

<div class="jumbotron jumbotron_error">
    <h1 class="display-4">Hola <?= $_SESSION["nombre_empleado"] ?> 
        
        <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown"><?= $_SESSION["nombre_empleado"] ?>
        <span class="caret"></span></button>
        <ul class="dropdown-menu">
        <li><a href="<?= _MODULE_DIR_.'pickpack/controllers/admin/pickingpacking/pickpackindex.php'; ?>">  Cerrar Sesión</a></li>              
        </ul>

    </h1>
    <!-- <p class="lead">El pedido que buscas no está disponible para <?= $action ?></p> -->
    <?php
    if ($error_tracking){
    ?>
    <p class="lead"><?= $error_tracking ?></p><br>
    <?php
    }
    ?>

    <div class="container" style="margin-bottom:10px;">  
        <form action="<?= $action ?>.php" method="post">      
            <button type="submit" name="submit_volver" class="btn btn-success">Volver</button>
        </form>
    </div>  
</div>
