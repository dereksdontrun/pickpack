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
              <!-- 03/01/2024 ponemos un input con la cantidad de stock físico para poder sumar/restar stock. El mismo value en un input hidden para comparar tras enviar el formulario -->
              <form id="formulario_ubicaciones" action="ubicaciones.php" method="post">
              <div class="col">            
                <div class="input-group mb-3">
                  <span class="input-group-text input_ubicacion">Stock</span>
                  <input type="number" class="form-control input_ubicacion" id="input_stock" name="input_stock" value="<?= $producto['stock_fisico'] ?>"  onfocus="this.select()">
                  <input type="hidden" id="input_stock_hidden" name="input_stock_hidden" value="<?= $producto['stock_fisico'] ?>">
                </div>
              </div>
              <div class="col">
                <div class="row">
                  <div class="col-6">
                    Consumo: <br><span style="font-size: 22px;" class="badge badge-pill badge-<?= $producto['badge'] ?>"><?= $producto['abc'] ?></span>
                  </div>                
                  <!-- 06/02/2024 Añadimos stock disponible online (disponible menos físico tienda) para saber si el producto está en pedidos de cliente -->
                  <div class="col-6">
                    Disponible: <br><span style="font-size: 22px;" class="badge badge-pill badge-info"><?= $producto['stock_disponible_online'] ?></span>
                  </div>   
                </div>             
              </div>
            </div> 
          </p>     
        </div>
      </div>
    
      <div class="col-sm-5 col-xs-5 col_ubicaciones col_ubicaciones_right">
        <!-- <form id="formulario_ubicaciones" action="ubicaciones.php" method="post"> -->
        <?php if ($recepciones) { ?> 
          <div class="card-body text-center card-recepciones"> 
            <div class="input-group mb-3 input-group-recepciones">
              <div class="input-group-prepend">
                <label class="input-group-text" for="select_pedido_materiales">Pedido</label>
              </div>
              <select class="custom-select" id="select_pedido_materiales" name="select_pedido_materiales">
                <!-- YA NO - 22/08/2023 Si el producto está en más de un pedido obligamos a seleccionarlo poniéndo selected en mensaje SeleccionaPedido, si no, será un select con una sola opción -->
                <!-- 12/09/2023 Comprobamos la variable de sesión $_SESSION["id_pedido_materiales"], se compara con el id_sipply_order de cada pedido (o del pedido si solo hay uno) y se marca selected al que coincida, o a ninguno si no hay coincidencia, en cuyo caso, al final se le pone a "SELECCIONA..."
                Es decir: Si el producto solo está en un pedido se marca seleccionado ese, si está en varios y $_SESSION["id_pedido_materiales"] vale 0 o no coincide con ninguno de esos pedidos, se marca seleccionado "SELECCIONA...", y si hay varios y $_SESSION["id_pedido_materiales"] coincide con alguno, se marca seleccionado ese pedido. PD, siempre ha de estar en al menos un pedido, si no no llegamos aquí -->
                <!-- Variable $selected que pasa a 1 si se marca selected algún option, variable $posicion_array para almacenar la posicion en array de pedidos para luego mostrar en el input las unidades esperadas/recibidas del producto (hasta ahora ponía 0, que era el primer pedido por defecto) -->
                <?php $selected = 0; $posicion_array = 0; $id_supply_order = 0;?>  
                <!-- si solo hay un pedido -->
                <?php if (count($producto['pedidos_materiales']) == 1) { ?>
                  <!-- Ponemos el pedido único como selected, independientemente de $_SESSION["id_pedido_materiales"]. Está en la posición 0 del array pedidos_materiales, $posicion_array se queda en 0 -->
                  <?php 
                    $selected = 1; 
                    $id_supply_order = $producto['pedidos_materiales'][0]['id_supply_order'];
                  ?>
                  <option 
                    value="<?= $producto['pedidos_materiales'][0]['id_supply_order'].'_'.$producto['pedidos_materiales'][0]['quantity_expected'].'_'.$producto['pedidos_materiales'][0]['unidades_ya_recibidas'].'_'.$producto['pedidos_materiales'][$x]['unidades_esperadas_reales'] ?>" selected>
                    <?= $producto['pedidos_materiales'][0]['supply_order'] ?> - 
                    <?= $producto['pedidos_materiales'][0]['supplier'] ?> -
                    <?= $producto['pedidos_materiales'][0]['state'] ?> -
                    <?= $producto['pedidos_materiales'][0]['date_add'] ?>                     
                  </option>
                <?php } else { ?> 
                <!-- varios pedidos, recorremos los id_supply_order buscando el contenido de $_SESSION["id_pedido_materiales"], que podría estar vacío. Si coincide alguno se marca selected -->
                  <?php for ($x = 0; $x < count($producto['pedidos_materiales']); $x++) { ?>                    
                    <!-- como value ponemos, id pedido materiales con cantidad esperada de producto y cantidad ya recibida, y cantidad real esperada, que sería la diferencia entre esas dos, o 0 si es negativo, para meter la cantidad esperada al input de debajo con javascript si se cambia el select. -->
                    <!-- Ya no hace falta marcar selected a ningún pedido, ya que si solo hay uno no habrá  más options, si hay más estará selected el mensaje de Selecciona..., quitamos esto: < ?php if ($x == 0) { ?> selected< ?php } ?> -->
                    <option 
                      value="<?= $producto['pedidos_materiales'][$x]['id_supply_order'].'_'.$producto['pedidos_materiales'][$x]['quantity_expected'].'_'.$producto['pedidos_materiales'][$x]['unidades_ya_recibidas'].'_'.$producto['pedidos_materiales'][$x]['unidades_esperadas_reales'] ?>" 
                      <?php if ($_SESSION["id_pedido_materiales"] == $producto['pedidos_materiales'][$x]['id_supply_order']) { //El id de pedido de materiales coincide, marcamos selected a este option?>                        
                        <?php 
                          $selected = 1; 
                          $posicion_array = $x; 
                          $id_supply_order = $producto['pedidos_materiales'][$x]['id_supply_order'];
                        ?>
                        selected
                      <?php } ?>
                      >
                      <?= $producto['pedidos_materiales'][$x]['supply_order'] ?> - 
                      <?= $producto['pedidos_materiales'][$x]['supplier'] ?> -
                      <?= $producto['pedidos_materiales'][$x]['state'] ?> -
                      <?= $producto['pedidos_materiales'][$x]['date_add'] ?>                     
                    </option>
                  <?php  }?> <!-- Fin for() -->
                <?php } ?>  <!-- Fin if -->
                <!-- Si $selected = 0, no se ha pre seleccionado ningún pedido, porque hay más de uno, y no coincide ninguno con $_SESSION["id_pedido_materiales"], añadimos option con "SELECCIONA..." -->
                <?php if (!$selected) { ?>
                  <!-- Hay varios pedidos, ponemos la option como Selecciona PEdido y selected, value 0 -->
                  <option value="0" selected>SELECCIONA PEDIDO DE MATERIALES</option>
                <?php } ?> 
              </select>
            </div> 
            <!-- 24/10/2023 Para almacenar los posibles mensajes de pedidod e materiales voy a generar un input hidden por pedido, con su id de pedido y value el mensaje, de modo que podamos cambiar el value del input hidden del mensaje del pedido seleccionado, que será el que se muestre  -->
            <?php for ($x = 0; $x < count($producto['pedidos_materiales']); $x++) { ?>
              <input type="hidden" id="supply_order_message_<?= $producto['pedidos_materiales'][$x]['id_supply_order'] ?>" name="supply_order_message_<?= $producto['pedidos_materiales'][$x]['id_supply_order'] ?>" value="<?= $producto['pedidos_materiales'][$x]['supply_order_message'] ?>">
            <?php } ?>

            <div class="input-group mb-3 input-group-recepciones">
              <span class="input-group-text input_ubicaciones">Esperadas</span>
              <!-- ponemos a / b, siendo b el total esperado del pedido y a si ya se supone que hemos marcado recibida alguna, ya sea aquí o de forma permanente en pedido materiales -->
              <!-- 12/09/2023 Para saber los datos de que pedido mostrar usamos $posicion_array, que será 0 si no se ha preseleccionado ningún pedido o si solo hay uno y por tanto su valor debe ser 0. En caso de no haber nada seleccionado en el select, ponemos, Selecciona pedido! -->
              <span class="input-group-text input_ubicaciones" id="span_esperadas_recibidas">
                <?php if (!$selected) { ?>
                  <!-- El select se ha cargado sin pedido seleccionado -->
                  SELECCIONA PEDIDO
                <?php } else { ?> 
                  <?= $producto['pedidos_materiales'][$posicion_array]['unidades_ya_recibidas'].' / '. $producto['pedidos_materiales'][$posicion_array]['quantity_expected'] ?>
                <?php } ?> 
              </span>
              <!-- cargamos con las unidades esperadas del primer pedido que caerá por defecto como selected en el select. Se actualiza dinámicamente desde back,js al cambiar el select -->
              <input type="number" class="form-control input_ubicaciones" id="input_unidades_esperadas"  name="input_unidades_esperadas" 
                <?php if (!$selected) { ?> 
                  value="0"
                <?php } else { ?>
                  value="<?= $producto['pedidos_materiales'][$posicion_array]['unidades_esperadas_reales'] ?>"
                <?php } ?>  onfocus="this.select()">                       
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
              <input type="text" class="form-control input_ubicacion" name="input_reposicion" value="<?= $producto['reposicion'] ?>"   onfocus="this.select()">
            </div>
            <!-- coloco el id d producto en un input hidden para el proceso de actualización. Va compuesto de id_product e id_product_attribute -->
            <input type="hidden" name="id_producto" value="<?= $producto['id'] ?>">
            <!-- 27/09/2023 Ponemos un input hidden con id "es_recepcion" y value 1 o 0 para saber desde javascript si antes de procesar el formulario estamos procesando ubicacion o recepcion y por tanto no procesar select de pedidos o cantidades a recibir si estamos en ubicacion -->
            <input type="hidden" id="es_recepcion" name="es_recepcion" value="<?php if ($recepciones) { ?>1<?php } else { ?>0<?php } ?>">
          </div>
          <div class="card-body text-center <?php if ($recepciones) { ?> card-recepciones<?php } ?>">
            <div class="btn-group btn-group-lg" role="group">
               
            <?php if ($recepciones) { ?>
              <!-- mostramos botón info solo en recepciones -->
              <button type="button" class="btn btn-info btn-outline-light btn_ubicacion" id="submit_supply_order_message" onclick="muestraMensajePedido()"  data-value="<?php $id_supply_order ?>">Info</button>
            <?php } ?>  
              
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