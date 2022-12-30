<?php

include('header.php');

?>

<div class="jumbotron jumbotron_packing">
    <h1 class="display-4">Gracias <?= $_SESSION["nombre_empleado"] ?> 
        
        <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown"><?= $_SESSION["nombre_empleado"] ?>
        <span class="caret"></span></button>
        <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?= _MODULE_DIR_.'pickpack/controllers/admin/pickingpacking/pickpackindex.php'; ?>">  Cerrar Sesión</a></li>              
        </ul>

    </h1>
    <p class="lead">El packing ha sido procesado</p>    
</div>
<div class="container" style="margin-bottom:60px;">  
    <form action="packing.php" method="post">      
        <button type="submit" name="submit_volver" class="btn btn-success">Volver</button>
    </form>
</div>  