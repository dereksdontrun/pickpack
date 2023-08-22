<?php

include('header.php');

include('cabeceramovil.php');

$recepciones = 0; 
if ($_SESSION["funcionalidad"] == 'recepciones') { 
  //si estamos en recepciones reduciremos tamaño foto, meteremos datos de pedido de materiales e input para unidades a recibir
  $recepciones = 1;         
}

?>

<div class="card">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-3 col-xs-3 text-center col_ubicaciones">
        <img class="card-img-top <?php if ($recepciones) { ?> img-recepciones<?php } ?>" src="<?= $producto['image_link'] ?>" alt="Imagen <?= $producto['id'] ?>">        
      </div>
    
      <div class="col-sm-4 col-xs-4 col_ubicaciones">
        <div class="card-body text-center"> 
          <div class="card-header">
            <?= $producto['product_name'] ?>
          </div>
          <p class="card-text"><?= $producto['reference'] ?>       
          <?php if ($producto['product_attributes']) { ?>
           <br><span style="font-size: 12px;"><b><?= $producto['product_attributes'] ?></b></span>          
          <?php } ?>
          </p>
          <p class="card-text"> 
            <div class="row">
              <div class="col">
                Stock: <span style="font-size: 22px;"><b><?= $producto['stock_fisico'] ?></b></span>
              </div>
              <div class="col">
                Consumo: <span style="font-size: 22px;" class="badge badge-pill badge-<?= $producto['badge'] ?>"><?= $producto['abc'] ?></span>                
              </div>
            </div> 
          </p>     
        </div>
      </div>
    
      <div class="col-sm-5 col-xs-5 col_ubicaciones col_ubicaciones_right">
        <form id="formulario_ubicaciones" action="ubicaciones.php" method="post">
        <?php if ($recepciones) { ?> 
          <div class="card-body text-center card-recepciones"> 
            <div class="input-group mb-3 input-group-recepciones">
              <div class="input-group-prepend">
                <label class="input-group-text" for="select_pedido_materiales">Pedido</label>
              </div>
              <select class="custom-select" id="select_pedido_materiales" name="select_pedido_materiales">
                <?php for ($x = 0; $x < count($producto['pedidos_materiales']); $x++) { ?>
                  <!-- como value ponemos, id pedido materiales con cantidad esperada de producto y cantidad ya recibida, y cantidad real esperada, que sería la diferencia entre esas dos, o 0 si es negativo, para meter la cantidad esperada al input de debajo con javascript si se cambia el select -->
                  <option 
                    value="<?= $producto['pedidos_materiales'][$x]['id_supply_order'].'_'.$producto['pedidos_materiales'][$x]['quantity_expected'].'_'.$producto['pedidos_materiales'][$x]['unidades_ya_recibidas'].'_'.$producto['pedidos_materiales'][$x]['unidades_esperadas_reales'] ?>" 
                    <?php if ($x == 0) { ?> selected<?php } ?>>
                    <?= $producto['pedidos_materiales'][$x]['supply_order'] ?> - 
                    <?= $producto['pedidos_materiales'][$x]['supplier'] ?> -
                    <?= $producto['pedidos_materiales'][$x]['state'] ?> -
                    <?= $producto['pedidos_materiales'][$x]['date_add'] ?>                     
                  </option>
                <?php  }?>
              </select>
            </div>  
            <div class="input-group mb-3 input-group-recepciones">
              <span class="input-group-text input_ubicaciones">Esperadas</span>
              <!-- ponemos a / b, siendo b el total esperado del pedido y a si ya se supone que hemos marcado recibida alguna, ya sea aquí o de forma permanente en pedido materiales -->
              <span class="input-group-text input_ubicaciones" id="span_esperadas_recibidas">
               <?= $producto['pedidos_materiales'][0]['unidades_ya_recibidas'].' / '. $producto['pedidos_materiales'][0]['quantity_expected'] ?>
              </span>
              <!-- cargamos con las unidades esperadas del primer pedido que caerá por defecto como selected en el select. Se actualiza dinámicamente desde back,js al cambiar el select -->
              <input type="number" class="form-control input_ubicaciones" id="input_unidades_esperadas"  name="input_unidades_esperadas" value="<?= $producto['pedidos_materiales'][0]['unidades_esperadas_reales'] ?>"  onfocus="this.select()"> 
            </div>            
          </div>          
        <?php } ?>
          <div class="card-body text-center <?php if ($recepciones) { ?> card-recepciones<?php } ?>"> 
            <div class="input-group mb-3 <?php if ($recepciones) { ?> input-group-recepciones<?php } ?>">
              <span class="input-group-text input_ubicacion">Localización</span>
              <input type="text" class="form-control input_ubicacion" name="input_localizacion" value="<?= $producto['localizacion'] ?>" autofocus  onfocus="this.select()"> <!-- autofocus centra el cursor en el input y onfocus.. hace que se seleccione el contenido  -->
            </div>  
            <div class="input-group mb-3 <?php if ($recepciones) { ?> input-group-recepciones<?php } ?>">
              <span class="input-group-text input_ubicacion">Reposición</span>
              <input type="text" class="form-control input_ubicacion" name="input_reposicion" value="<?= $producto['reposicion'] ?>">
            </div>
            <!-- coloco el id d producto en un input hidden para el proceso de actualización. Va compuesto de id_product e id_product_attribute -->
            <input type="hidden" name="id_producto" value="<?= $producto['id'] ?>">
          </div>
          <div class="card-body text-center <?php if ($recepciones) { ?> card-recepciones<?php } ?>">
            <div class="btn-group btn-group-lg" role="group">
              <button type="submit" class="btn btn-success btn-outline-light btn_ubicacion" name="submit_producto_ok">OK</button>
              <button type="submit" class="btn btn-warning btn-outline-light btn_ubicacion" name="submit_volver">Volver</button>
              <button type="submit" class="btn btn-danger btn-outline-light btn_ubicacion" name="submit_producto_incidencia">Incidencia</button>
            </div>
              <!-- Prueba para sacar las dimensiones de la pantalla del móvil y poder poner el css acorde para que salga todo a la vez -->
              <!-- <div id="medidas"></div>
              <script>
              let text = "Total width/height: " + screen.width + "*" + screen.height + "<br>" +
              "Available width/height: " + screen.availWidth + "*" + screen.availHeight + "<br>";

              document.getElementById("medidas").innerHTML = text;
              </script> -->

          </div>
        </form>
      </div>
    </div>
    
  </div>
  
  

</div>


</body>
</html>
<?php 
// echo '<pre>';
// var_dump($_SESSION);

// var_dump($producto);
// echo '</pre>';

?>