<?php

include('header.php');

?>
<!-- Cabecera con info sobre cliente y pedido -->
<div class="jumbotron jumbotron_picking">
  <h1 class="display-4"><span style="font-size: 50%;">PICKING</span> <strong><?= $id_order ?></strong> <span style="font-size: 50%;"><?= $fecha_pedido ?></span>  
    
    <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown"><?= $_SESSION["nombre_empleado"] ?>
    <span class="caret"></span></button>
    <ul class="dropdown-menu">
      <li><a class="dropdown-item" href="<?= _MODULE_DIR_.'pickpack/controllers/admin/pickingpacking/pickpackindex.php?cerrar_sesion=1'; ?>">  Cerrar Sesión</a></li>              
    </ul>

  </h1>
  <h5><span style="font-size: 80%;">Estado Pick Pack actual</span> <strong><?= $estado_pickpack ?></strong></h5> 
  <?php if ($_SESSION["varios"]) { ?>
      <h5>ATENCIÓN: Te encuentras realizando el picking de varios pedidos de un mismo cliente</h5>
  <?php } ?>
    
  <div class="datos_cliente">
    <address>          
      <strong><?= $nombre_cliente ?></strong><br>
      <?= $direccion ?><br>
      <?= $codigo_postal ?> <?= $ciudad ?> - <?= $provincia ?> - <?= $pais ?><br>
      <!-- si es pedido amazon lo indico -->
      <?php if ($amazon){ ?>
        <span class="badge badge-pill badge-info" style="font-size: 150%;">AMAZON</span><br>
      <?php }  ?>       
      <span class="badge badge-pill badge-warning" style="font-size: 150%;"><?= $transporte ?></span>
    </address>         
  </div>    
</div>


<!-- 03/11/2020 mostramos mensaje de warning avisando de que este pedido se encontraba en estado Paquete Enviado al abrir el picking y podría estar duplicado
29/11/2022 añado el campo finalizado de lafrips_pick_pack que estará a 1 si se terminó el packing, independientemente del estado actual del pickpack -->
<?php if ($estado_pickpack == 'Paquete Enviado' || $finalizado){ ?>

<div class="container container_mensajes">  
  <div class="panel">    
    <div class="list-group">
      <h3>¡ATENCIÓN!</h3>      
      <div class="list-group-item" style="color:white; background-color:#e34f4f; border:2px solid black;"> 
        <p>Este pedido se encuentra en estado de pickpack "Paquete Enviado" en este momento o su packing ya fue finalizado <strong><?= $fecha_fin_packing ?></strong>.</p> 
        <p>Asegúrate de que no está duplicado o tiene otra incidencia y se puede continuar con el picking.</p>       
      </div>      
    </div>
  </div>
</div>
<br>
<?php } ?>


<!-- mostramos los mensajes sobre el pedido si los hay. -->
<?php if ($todos_mensajes_pedido){ ?>

  <div class="container container_mensajes">  
    <div class="panel">
      <h3>Mensajes privados del pedido:</h3>
      <div class="list-group">
        <?php foreach ($todos_mensajes_pedido AS $cabecera => $mensaje){ ?>
          <div class="list-group-item">        
            <footer class="blockquote-footer"><?= $cabecera ?></footer>
            <blockquote class="blockquote padquote">
              <p class="mb-0"><?= $mensaje ?></p>
            </blockquote>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
<?php } ?>

<!-- necesito saber el número de productos del pedido cuando envíe el formulario, de modo que usamos una variable $numero_productos que se insertará en un input hidden y se recogerá después en la función procesaPicking para sacr bien los id de producto y su estado al hacer picking. 
 También creamos una variable donde guardar los id de pedido que correspondan a cajas sorpresa, si las hay, cada vez que encontremos un producto cuyo id_order no corresponde al general del pedido del que estamos haciendo el picking. Este array irá a un input hidden que procesaremos en picking.php para hacer update en lafrips_pickpack -->

<?php $numero_productos = count($productos_pedido);
      $ids_cajas = array();
?>


<!-- líneas de productos -->

<div class="container container_productos">  
  <h3>Productos - <?= $numero_productos ?></h3>
  <form id="formulario_picking" action="picking.php" method="post">   

  <?php if ($warning_pedido_dropshipping) { ?>
      <h5>ATENCIÓN: Sin productos a preparar, pedido Dropshipping con entrega a cliente</h5>
  <?php } ?>

  <?php foreach ($productos_pedido as $producto) {     
    //stocks
    $stock_online = $producto['stock_online'];
    if (!$stock_online){
      $stock_online = 0;
    }
    $stock_tienda = $producto['stock_tienda'];
    if (!$stock_tienda){
      $stock_tienda = 0;
    }
    //imagen, revisamos campo 'existe_imagen', si no contiene id ponemos logo de tienda. 
    if (empty($producto['existe_imagen'])) {
      $imagen = 'https://lafrikileria.com/img/logo_producto_medium_default.jpg';
    } else {
      $imagen = $producto['imagen'];
    }
?>
    <!-- mostramos imagen, tamaño de imagen fijado, con un 25% menos del tamaño de home_default -->
    
    <div class="row">
      <div class="col-sm-5 col-xs-4">            
        <img src="<?= $imagen ?>"  width="218" height="289"/>    
      </div>
     

    <!-- datos del producto -->    
      <div class="col-sm-5 col-xs-6">
        <p class="h6"><?= $producto['nombre_completo'] ?></p><br>
        REF: <?= $producto['referencia_producto'] ?><br/>
        <!-- Si no tiene ean no lo ponemos -->
        <?php if ($producto['ean'] && $producto['ean'] !== ''){ ?>
          <span>EAN: <?= $producto['ean'] ?></span><br/>
        <?php } ?>
        <!-- 27/01/2023 Si la fecha de última recepción en pedido de materiales es más reciente que la última ubicación ponemos un mensaje -->
        <?php if ($producto['area_recepcion']){  ?>
          <span style="font-size: 17px;" class="badge badge-pill badge-warning">Área de Recepción</span><br>
        <?php } ?>
        <!-- Si no tiene localización no la ponemos -->
        <?php if ($producto['localizacion'] && $producto['localizacion'] !== ''){  ?>
          <span style="font-size:17px; font-weight:bold;"><?= $producto['localizacion'] ?></span><br/>
        <?php } ?>
        <!-- Si no tiene localización de repo no la  ponemos -->
        <?php if ($producto['localizacion_repo'] && $producto['localizacion_repo'] !== ''){  ?>
          <span style="font-size:14px;">Repo: <?= $producto['localizacion_repo'] ?></span><br/>
        <?php } ?>
        <!-- Stock en ambos almacenes -->
        <span style="font-size:14px;">Stock: Online ( <?= $stock_online ?> ) - Tienda ( <?= $stock_tienda ?> )</span>  
        <!-- 25/04/2022 metemos un mensaje indicando que el producto es dropshipping si lo es -->
        <?php if ($producto['dropshipping']) { ?>
        <br><span style="font-size: 17px;" class="badge badge-pill badge-warning">Dropshipping</span>
        <?php } ?>
        <!-- Si el producto es de una caja sorpresa, su id_order no coincidirá con $id_order, que es el pedido base, mostramos mensaje e imagen de caja sopresa, y guardaremos el id_order en array -->
        <?php if ($producto['id_order'] != $id_order){  
            $ids_cajas[] = $producto['id_order'];
        ?>
        <br><br>
        <div class="row">
            <div class="col-sm-4 col-xs-5">
                <img src="https://lafrikileria.com/img/cajasorpresa.jpg"  width="80" height="80"/>
            </div>
            <div class="col-sm-5 col-xs-6">
                <br>
                <span style="font-size:25px; font-weight:bold; color:red;">   <?= $producto['id_order'] ?></span>
            </div>  
        </div>
        <?php } ?>
      </div>
        
      <!-- Unidades de producto en pedido, si son más de una se señala -->
      <div class="col-sm-1 col-xs-1">
      <h4 class="alert-heading">Ud:</h4>
        <?php if ($producto['cantidad'] != 1) {  ?>        
          <div class="alert alert-danger">          
            <p class="h2"><?= $producto['cantidad'] ?></p>
          </div> 
          <?php }else{ ?>
          <div class="alert alert-light">          
            <?= $producto['cantidad'] ?>
          </div>
          <?php }  ?>  
      </div>
      
      <!-- botones de ok para cada producto, ponemos id producto, atributo y pedido ya que si tienen el mismo id no se pueden pulsar a la vez -->
      <div class="col-sm-1 col-xs-1" style="padding-top:5%;">
        <div class="btn-group" data-toggle="buttons">            
          <label class="btn btn-success active">
            <input type="radio" name="<?= $producto['id_producto'] ?>_<?= $producto['id_atributo'] ?>_<?= $producto['id_order'] ?>" id="<?= $producto['id_producto'] ?>_<?= $producto['id_atributo'] ?>_<?= $producto['id_order'] ?>" value="1" autocomplete="off" required>
            <span class="fa fa-check"></span>
          </label>		

          <label class="btn btn-danger">
          <input type="radio" name="<?= $producto['id_producto'] ?>_<?= $producto['id_atributo'] ?>_<?= $producto['id_order'] ?>" id="<?= $producto['id_producto'] ?>_<?= $producto['id_atributo'] ?>_<?= $producto['id_order'] ?>" value="0" autocomplete="off" >
            <span class="fa fa-times"></span>
          </label>          
        </div>     
      </div>
    </div> <!-- Fin row de producto-->
    <hr>


<?php  } //fin foreach producto  ?>

  <!-- Si el cliente lleva 5 o más pedidos ponemos un check para marcar si se coge el regalo, salvo que el pedido vaya en vuelto para regalo, en cuyo caso suponemos que no se le envía al comprador y no queremos meter obsequio. Si tenemos el warning de pedido todo dropshipping tampoco mostramos -->
<?php if (($numero_pedidos > 4) && (!$regalo) && !$warning_pedido_dropshipping){ ?>    
  <div class="row">
    <div class="form-group form-check" style="padding-left:30%;">
      <label class="btn btn-lg btn-warning">Obsequio: 
        <input type="checkbox" name="obsequio" id="obsequio" required>
      </label>
    </div>
    </div>
    <hr>      
 <?php } ?>

  <!-- ponemos un textarea para comentarios sobre el picking -->
  <div class="row"> 
    <div class="form-group col-sm-12  col-xs-12">
      <label for="comentario">Comentarios:</label>
      <textarea class="form-control" rows="5" id="comentario" name="comentario"></textarea>
    </div>
  </div>
</div> <!--div final de container -->

  <!-- coloco el número de productos en un input hidden para el proceso de Picking -->
  <input type="hidden" name="numero_productos" value="<?= $numero_productos ?>">
  <!-- coloco el número de pedido en un input hidden para el proceso de Picking -->
  <input type="hidden" name="id_pedido" value="<?= $id_order ?>">
  <!-- coloco los ids de pedido que correspondan a pedidos de caja sorpresa, si los hay, en un input hidden para el proceso de Picking. Primero hacemos array_unique del array donde se han guardado, y para enviar el array por post hay que serializarlo aquí y unserializarlo en el destino, usamos comillas simples porque serialize añade comillas dobles -->
  <?php  $ids_cajas = array_unique($ids_cajas);  ?>
  <input type="hidden" name="ids_cajas" value='<?= serialize($ids_cajas) ?>'>

  <!-- Botón de submit para el formulario. formnovalidate en el botón de cancelar permite hacer submit sin marcar los required etc. Les ponemos a ambos clase submit_boton para en js comprobar cual es el pulsado y usarlo a la hora de mostrar mensaje de confirmación de envío si hay productos no ok -->
  <div class="row" style="padding-left:30%; margin-bottom:30px;"> 
    <input class="btn btn-lg btn-success submit_boton_picking" type="submit" id="submit_finpicking" name="submit_finpicking" value="Finalizar" />
    <button type="submit" name="submit_volver" class="btn btn-danger submit_boton_picking"  formnovalidate  style="margin-left:10px;">Cancelar</button>
  </div>
</form>

</body>
</html>