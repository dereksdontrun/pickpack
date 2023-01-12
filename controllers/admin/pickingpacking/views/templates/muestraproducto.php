<?php

include('header.php');

?>
<!-- Cabecera -->
<nav class="navbar bg-body-tertiary jumbotron_ubicaciones">
  <div class="container-fluid">
    <span class="navbar-brand mb-0 h1">Ubicaciones</span>
    <div class="dropdown-center">
      <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown"><?= $_SESSION["nombre_empleado"] ?>
      <span class="caret"></span></button>
      <ul class="dropdown-menu">
        <!-- quito variable get ?cerrar_sesion=1 de momento -->
        <li><a class="dropdown-item" href="<?= _MODULE_DIR_.'pickpack/controllers/admin/pickingpacking/pickpackindex.php'; ?>">  Cerrar Sesión</a></li>              
      </ul>
    </div>
  </div>
</nav>

<div class="card">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-4 text-center">
        <img class="card-img-top" src="<?= $producto['image_link'] ?>" alt="Imagen <?= $producto['id'] ?>">
        <div class="card-header">
          <?= $producto['product_name'] ?>
        </div>
      </div>
    
      <div class="col-sm-3">
        <div class="card-body text-center"> 
          <p class="card-text"><?= $producto['reference'] ?>       
          <?php if ($producto['product_attributes']) { ?>
           <br><span style="font-size: 12px;"><b><?= $producto['product_attributes'] ?></b></span>          
          <?php } ?>
          </p>
          <p class="card-text"> 
            <div class="row">
              <div class="col">
                Stock: <span style="font-size: 17px;"><b><?= $producto['stock_fisico'] ?></b></span>
              </div>
              <div class="col">
                Consumo: <span style="font-size: 17px;" class="badge badge-pill badge-<?= $producto['badge'] ?>"><?= $producto['abc'] ?></span>                
              </div>
            </div> 
          </p>     
        </div>
      </div>
    
      <div class="col-sm-5">
        <form id="formulario_ubicaciones" action="ubicaciones.php" method="post">
          <div class="card-body text-center"> 
            <div class="input-group mb-3">
              <span class="input-group-text">Localización</span>
              <input type="text" class="form-control" name="input_localizacion" value="<?= $producto['localizacion'] ?>" autofocus  onfocus="this.select()"> <!-- autofocus centra el cursor en el input y onfocus.. hace que se seleccione el contenido  -->
            </div>  
            <div class="input-group mb-3">
              <span class="input-group-text">Reposición</span>
              <input type="text" class="form-control" name="input_reposicion" value="<?= $producto['reposicion'] ?>">
            </div>
            <!-- coloco el id d producto en un input hidden para el proceso de actualización. Va compuesto de id_product e id_product_attribute -->
            <input type="hidden" name="id_producto" value="<?= $producto['id'] ?>">
          </div>
          <div class="card-body text-center">
            <div class="btn-group btn-group-lg" role="group">
              <button type="submit" class="btn btn-success btn-outline-light" name="submit_producto_ok">OK</button>
              <button type="submit" class="btn btn-warning btn-outline-light" name="submit_volver">Volver</button>
              <button type="submit" class="btn btn-danger btn-outline-light" name="submit_producto_incidencia">Incidencia</button>
            </div>
          </div>
        </form>
      </div>
    </div>
    
  </div>
  
  

</div>


</body>
</html>